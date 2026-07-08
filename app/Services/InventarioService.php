<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\InventarioRepository;
use RuntimeException;

final class InventarioService
{
    public function __construct(
        private readonly InventarioRepository $refacciones = new InventarioRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function stockBajo(): array
    {
        return $this->refacciones->stockBajo();
    }

    public function listar(array $filtros = []): array
    {
        return $this->refacciones->all($filtros);
    }

    public function obtener(int $id): ?array
    {
        return $this->refacciones->find($id);
    }

    public function movimientos(int $refaccionId): array
    {
        return $this->refacciones->movimientos($refaccionId);
    }

    public function guardar(array $data, ?int $id = null): int
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $sku = strtoupper(trim((string) ($data['sku'] ?? '')));

        if ($nombre === '') {
            throw new RuntimeException('El nombre de la refaccion es obligatorio.');
        }
        if ($sku === '') {
            throw new RuntimeException('El SKU es obligatorio.');
        }
        if ($this->refacciones->skuExists($sku, $id)) {
            throw new RuntimeException('Ya existe una refaccion con ese SKU.');
        }

        $estatus = $data['estatus'] ?? 'activo';
        $payload = [
            'proveedor_id' => !empty($data['proveedor_id']) ? (int) $data['proveedor_id'] : null,
            'nombre' => $nombre,
            'sku' => $sku,
            'categoria' => trim((string) ($data['categoria'] ?? '')) ?: null,
            'marca' => trim((string) ($data['marca'] ?? '')) ?: null,
            'modelo_compatible' => trim((string) ($data['modelo_compatible'] ?? '')) ?: null,
            'costo' => max(0, (float) ($data['costo'] ?? 0)),
            'precio_venta' => max(0, (float) ($data['precio_venta'] ?? 0)),
            'stock_minimo' => max(0, (int) ($data['stock_minimo'] ?? 0)),
            'ubicacion' => trim((string) ($data['ubicacion'] ?? '')) ?: null,
            'estatus' => in_array($estatus, ['activo', 'inactivo'], true) ? $estatus : 'activo',
        ];

        if ($id) {
            if (!$this->refacciones->find($id)) {
                throw new RuntimeException('Refaccion no encontrada.');
            }
            $this->refacciones->update($id, $payload);
            $this->auditoria->registrar('editar', 'inventario', $id, null, $payload);
            return $id;
        }

        // El stock inicial se registra como movimiento de entrada para dejar rastro.
        $stockInicial = max(0, (int) ($data['stock_actual'] ?? 0));
        $payload['stock_actual'] = 0;
        $newId = $this->refacciones->create($payload);
        $this->auditoria->registrar('crear', 'inventario', $newId, null, $payload);

        if ($stockInicial > 0) {
            $this->movimiento($newId, 'entrada', $stockInicial, 'Stock inicial', $payload['costo']);
        }

        return $newId;
    }

    public function movimiento(int $refaccionId, string $tipo, int $cantidad, string $motivo, ?float $costoUnitario = null, ?int $ordenId = null): void
    {
        /*
         * Entrada / salida / ajuste de stock, en una sola transaccion:
         * actualiza stock_actual y deja el movimiento en inventario_movimientos.
         * ajuste = fija el stock al valor "cantidad" (conteo fisico).
         */
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
            throw new RuntimeException('Tipo de movimiento no valido.');
        }
        if ($cantidad < 0) {
            throw new RuntimeException('La cantidad no puede ser negativa.');
        }
        if ($tipo !== 'ajuste' && $cantidad === 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $motivo = trim($motivo) ?: ucfirst($tipo);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $refaccion = $this->refacciones->find($refaccionId);
            if (!$refaccion) {
                throw new RuntimeException('Refaccion no encontrada.');
            }

            $stockAnterior = (int) $refaccion['stock_actual'];
            $stockNuevo = match ($tipo) {
                'entrada' => $stockAnterior + $cantidad,
                'salida' => $stockAnterior - $cantidad,
                'ajuste' => $cantidad,
            };

            if ($stockNuevo < 0) {
                throw new RuntimeException('No hay stock suficiente para esa salida.');
            }

            $this->refacciones->setStock($refaccionId, $stockNuevo);
            $this->refacciones->registrarMovimiento([
                'refaccion_id' => $refaccionId,
                'orden_id' => $ordenId,
                'usuario_id' => Auth::id() ?? 1,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => mb_substr($motivo, 0, 255),
                'costo_unitario' => $costoUnitario,
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ]);
            $this->auditoria->registrar('movimiento', 'inventario', $refaccionId, ['stock' => $stockAnterior], ['tipo' => $tipo, 'cantidad' => $cantidad, 'stock' => $stockNuevo]);

            $db->commit();

            // Aviso al almacen si el movimiento dejo la refaccion en o por debajo del minimo.
            if ($stockNuevo <= (int) $refaccion['stock_minimo'] && (int) $refaccion['stock_minimo'] > 0) {
                (new NotificacionService())->stockBajo($refaccionId, (string) $refaccion['nombre'], $stockNuevo);
            }
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }
}
