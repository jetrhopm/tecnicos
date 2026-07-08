<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CotizacionRepository;
use App\Repositories\InventarioRepository;
use App\Repositories\OrdenRepository;
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

    public function buscarParaVenta(string $query): array
    {
        return $this->refacciones->buscarParaVenta($query);
    }

    public function obtener(int $id): ?array
    {
        return $this->refacciones->find($id);
    }

    public function movimientos(int $refaccionId): array
    {
        return $this->refacciones->movimientos($refaccionId);
    }

    public function usosPorOrden(int $ordenId): array
    {
        return $this->refacciones->usosPorOrden($ordenId);
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

            $notificarStockBajo = $stockNuevo <= (int) $refaccion['stock_minimo'] && (int) $refaccion['stock_minimo'] > 0;
            $nombreRefaccion = (string) $refaccion['nombre'];

            $db->commit();

            // Aviso al almacen si el movimiento dejo la refaccion en o por debajo del minimo.
            if ($notificarStockBajo) {
                try {
                    (new NotificacionService())->stockBajo($refaccionId, $nombreRefaccion, $stockNuevo);
                } catch (\Throwable) {
                    // La salida de inventario ya quedo registrada; la notificacion no debe revertirla.
                }
            }
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function aplicarAOrden(int $ordenId, int $refaccionId, int $cantidad, ?float $precioUnitario = null, string $motivo = ''): int
    {
        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $orden = (new OrdenRepository())->find($ordenId);
            if (!$orden) {
                throw new RuntimeException('Orden no encontrada.');
            }
            if (in_array((string) $orden['estado'], ['entregada', 'cancelada'], true)) {
                throw new RuntimeException('No se pueden aplicar refacciones a una orden entregada o cancelada.');
            }

            $refaccion = $this->refacciones->findForUpdate($refaccionId);
            if (!$refaccion || $refaccion['estatus'] !== 'activo') {
                throw new RuntimeException('Refaccion no encontrada o inactiva.');
            }

            $stockAnterior = (int) $refaccion['stock_actual'];
            $stockNuevo = $stockAnterior - $cantidad;
            if ($stockNuevo < 0) {
                throw new RuntimeException('No hay stock suficiente para aplicar esa refaccion.');
            }

            $precioUnitario = $precioUnitario !== null ? max(0, $precioUnitario) : (float) $refaccion['precio_venta'];
            $motivo = trim($motivo) ?: 'Salida por reparacion de orden ' . (string) $orden['folio'];

            $this->refacciones->setStock($refaccionId, $stockNuevo);
            $usoId = $this->refacciones->registrarUsoOrden([
                'orden_id' => $ordenId,
                'refaccion_id' => $refaccionId,
                'cotizacion_item_id' => null,
                'cantidad' => $cantidad,
                'precio_unitario' => $precioUnitario,
            ]);
            $this->refacciones->registrarMovimiento([
                'refaccion_id' => $refaccionId,
                'orden_id' => $ordenId,
                'usuario_id' => Auth::id() ?? 1,
                'tipo' => 'salida',
                'cantidad' => $cantidad,
                'motivo' => mb_substr($motivo, 0, 255),
                'costo_unitario' => (float) $refaccion['costo'],
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ]);

            $this->auditoria->registrar('aplicar_refaccion', 'ordenes', $ordenId, null, [
                'uso_id' => $usoId,
                'refaccion_id' => $refaccionId,
                'cantidad' => $cantidad,
                'stock' => $stockNuevo,
            ]);

            $notificarStockBajo = $stockNuevo <= (int) $refaccion['stock_minimo'] && (int) $refaccion['stock_minimo'] > 0;
            $nombreRefaccion = (string) $refaccion['nombre'];

            $db->commit();

            if ($notificarStockBajo) {
                try {
                    (new NotificacionService())->stockBajo($refaccionId, $nombreRefaccion, $stockNuevo);
                } catch (\Throwable) {
                    // La salida de inventario ya quedo registrada; la notificacion no debe revertirla.
                }
            }

            return $usoId;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function aplicarCotizadas(int $ordenId): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        $notificar = [];

        try {
            $orden = (new OrdenRepository())->find($ordenId);
            if (!$orden) {
                throw new RuntimeException('Orden no encontrada.');
            }
            if (in_array((string) $orden['estado'], ['entregada', 'cancelada'], true)) {
                throw new RuntimeException('No se pueden aplicar refacciones a una orden entregada o cancelada.');
            }

            $items = (new CotizacionRepository())->refaccionesCotizadasPendientes($ordenId);
            if ($items === []) {
                throw new RuntimeException('No hay refacciones cotizadas pendientes por aplicar.');
            }

            $aplicadas = 0;
            foreach ($items as $item) {
                $refaccionId = (int) $item['refaccion_id'];
                $cantidad = (int) ceil((float) $item['cantidad']);
                if ($cantidad <= 0) {
                    throw new RuntimeException('Una refaccion cotizada tiene cantidad invalida.');
                }

                $refaccion = $this->refacciones->findForUpdate($refaccionId);
                if (!$refaccion || $refaccion['estatus'] !== 'activo') {
                    throw new RuntimeException('Una refaccion cotizada ya no existe o esta inactiva.');
                }

                $stockAnterior = (int) $refaccion['stock_actual'];
                $stockNuevo = $stockAnterior - $cantidad;
                if ($stockNuevo < 0) {
                    throw new RuntimeException('No hay stock suficiente para: ' . (string) $refaccion['nombre']);
                }

                $precioUnitario = (float) $item['precio_unitario'];
                $this->refacciones->setStock($refaccionId, $stockNuevo);
                $usoId = $this->refacciones->registrarUsoOrden([
                    'orden_id' => $ordenId,
                    'refaccion_id' => $refaccionId,
                    'cotizacion_item_id' => (int) $item['id'],
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precioUnitario,
                ]);
                $this->refacciones->registrarMovimiento([
                    'refaccion_id' => $refaccionId,
                    'orden_id' => $ordenId,
                    'usuario_id' => Auth::id() ?? 1,
                    'tipo' => 'salida',
                    'cantidad' => $cantidad,
                    'motivo' => mb_substr('Salida por refaccion cotizada en orden ' . (string) $orden['folio'], 0, 255),
                    'costo_unitario' => (float) $item['costo_unitario'],
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                ]);

                $this->auditoria->registrar('aplicar_refaccion_cotizada', 'ordenes', $ordenId, null, [
                    'uso_id' => $usoId,
                    'cotizacion_item_id' => (int) $item['id'],
                    'refaccion_id' => $refaccionId,
                    'cantidad' => $cantidad,
                    'stock' => $stockNuevo,
                ]);

                if ($stockNuevo <= (int) $refaccion['stock_minimo'] && (int) $refaccion['stock_minimo'] > 0) {
                    $notificar[] = [$refaccionId, (string) $refaccion['nombre'], $stockNuevo];
                }
                $aplicadas++;
            }

            $db->commit();

            foreach ($notificar as [$refaccionId, $nombre, $stock]) {
                try {
                    (new NotificacionService())->stockBajo((int) $refaccionId, (string) $nombre, (int) $stock);
                } catch (\Throwable) {
                    // La salida ya quedo registrada; la notificacion no debe revertirla.
                }
            }

            return $aplicadas;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelarUsoOrden(int $ordenId, int $usoId, string $motivo): void
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new RuntimeException('Indica el motivo de cancelacion de la refaccion.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $uso = $this->refacciones->usoOrdenForUpdate($usoId);
            if (!$uso || (int) $uso['orden_id'] !== $ordenId) {
                throw new RuntimeException('La refaccion aplicada no pertenece a esta orden.');
            }
            if ($uso['estado'] !== 'activa') {
                throw new RuntimeException('Esta refaccion aplicada ya fue cancelada.');
            }

            $refaccion = $this->refacciones->findForUpdate((int) $uso['refaccion_id']);
            if (!$refaccion) {
                throw new RuntimeException('Refaccion no encontrada.');
            }

            $cantidad = (int) $uso['cantidad'];
            $stockAnterior = (int) $refaccion['stock_actual'];
            $stockNuevo = $stockAnterior + $cantidad;

            $this->refacciones->setStock((int) $uso['refaccion_id'], $stockNuevo);
            $this->refacciones->cancelarUsoOrden($usoId, $motivo, Auth::id() ?? 1);
            $this->refacciones->registrarMovimiento([
                'refaccion_id' => (int) $uso['refaccion_id'],
                'orden_id' => $ordenId,
                'usuario_id' => Auth::id() ?? 1,
                'tipo' => 'cancelacion',
                'cantidad' => $cantidad,
                'motivo' => mb_substr('Cancelacion de refaccion en orden: ' . $motivo, 0, 255),
                'costo_unitario' => (float) $uso['costo'],
                'stock_anterior' => $stockAnterior,
                'stock_nuevo' => $stockNuevo,
            ]);

            $this->auditoria->registrar('cancelar_refaccion', 'ordenes', $ordenId, [
                'uso_id' => $usoId,
                'estado' => 'activa',
            ], [
                'uso_id' => $usoId,
                'estado' => 'cancelada',
                'motivo' => $motivo,
                'stock' => $stockNuevo,
            ]);

            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }
}
