<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\InventarioRepository;
use App\Repositories\VentaRefaccionRepository;
use RuntimeException;

final class VentaRefaccionService
{
    public function __construct(
        private readonly VentaRefaccionRepository $ventas = new VentaRefaccionRepository(),
        private readonly InventarioRepository $inventario = new InventarioRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function recientes(): array
    {
        return $this->ventas->recientes();
    }

    public function obtener(int $id): ?array
    {
        $venta = $this->ventas->find($id);
        if (!$venta) {
            return null;
        }
        $venta['items'] = $this->ventas->items($id);
        return $venta;
    }

    public function vender(array $data): int
    {
        $items = isset($data['items']) && is_array($data['items'])
            ? array_values(array_filter($data['items'], 'is_array'))
            : [];
        $items = array_values(array_filter($items, static fn (array $item): bool => !empty($item['refaccion_id'])));
        if ($items === []) {
            throw new RuntimeException('Agrega al menos una refaccion a la venta.');
        }

        $metodo = (string) ($data['metodo_pago'] ?? 'efectivo');
        if (!in_array($metodo, ['efectivo', 'transferencia', 'tarjeta', 'otro'], true)) {
            $metodo = 'efectivo';
        }

        $descuento = max(0, (float) ($data['descuento'] ?? 0));
        $db = Database::connection();
        $db->beginTransaction();
        $notificar = [];

        try {
            $normalizados = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                $refaccionId = (int) ($item['refaccion_id'] ?? 0);
                $cantidad = (int) ($item['cantidad'] ?? 0);
                if ($refaccionId <= 0 || $cantidad <= 0) {
                    throw new RuntimeException('Cada refaccion debe tener cantidad mayor a cero.');
                }

                $refaccion = $this->inventario->findForUpdate($refaccionId);
                if (!$refaccion || $refaccion['estatus'] !== 'activo') {
                    throw new RuntimeException('Una refaccion no existe o esta inactiva.');
                }

                $stockAnterior = (int) $refaccion['stock_actual'];
                $stockNuevo = $stockAnterior - $cantidad;
                if ($stockNuevo < 0) {
                    throw new RuntimeException('No hay stock suficiente para: ' . (string) $refaccion['nombre']);
                }

                $precioCrudo = $item['precio_unitario'] ?? '';
                if ($precioCrudo !== '' && !is_numeric($precioCrudo)) {
                    throw new RuntimeException('El precio debe ser numerico.');
                }
                if ($precioCrudo !== '' && is_numeric($precioCrudo) && (float) $precioCrudo < 0) {
                    throw new RuntimeException('El precio no puede ser negativo.');
                }
                $precio = (float) $precioCrudo;
                if ($precio <= 0) {
                    $precio = (float) $refaccion['precio_venta'];
                }

                $lineaSubtotal = calcularSubtotal($cantidad, $precio);
                $subtotal += $lineaSubtotal;
                $normalizados[] = [
                    'refaccion' => $refaccion,
                    'cantidad' => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal' => $lineaSubtotal,
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                ];
            }

            if ($descuento > $subtotal) {
                throw new RuntimeException('El descuento no puede superar el subtotal.');
            }
            $total = calcularTotal($subtotal, $descuento, 0);
            $folio = $this->generarFolio();

            $ventaId = $this->ventas->create([
                'folio' => $folio,
                'cliente_nombre' => trim((string) ($data['cliente_nombre'] ?? '')) ?: null,
                'cliente_telefono' => trim((string) ($data['cliente_telefono'] ?? '')) ?: null,
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'total' => $total,
                'metodo_pago' => $metodo,
                'referencia' => trim((string) ($data['referencia'] ?? '')) ?: null,
                'notas' => trim((string) ($data['notas'] ?? '')) ?: null,
                'usuario_id' => Auth::id() ?? 1,
            ]);

            foreach ($normalizados as $linea) {
                $refaccion = $linea['refaccion'];
                $refaccionId = (int) $refaccion['id'];
                $this->inventario->setStock($refaccionId, (int) $linea['stock_nuevo']);
                $this->ventas->addItem([
                    'venta_id' => $ventaId,
                    'refaccion_id' => $refaccionId,
                    'descripcion' => (string) $refaccion['nombre'],
                    'sku' => (string) $refaccion['sku'],
                    'cantidad' => (int) $linea['cantidad'],
                    'costo_unitario' => (float) $refaccion['costo'],
                    'precio_unitario' => (float) $linea['precio_unitario'],
                    'subtotal' => (float) $linea['subtotal'],
                ]);
                $this->inventario->registrarMovimiento([
                    'refaccion_id' => $refaccionId,
                    'orden_id' => null,
                    'venta_refaccion_id' => $ventaId,
                    'usuario_id' => Auth::id() ?? 1,
                    'tipo' => 'salida',
                    'cantidad' => (int) $linea['cantidad'],
                    'motivo' => mb_substr('Venta mostrador ' . $folio, 0, 255),
                    'costo_unitario' => (float) $refaccion['costo'],
                    'stock_anterior' => (int) $linea['stock_anterior'],
                    'stock_nuevo' => (int) $linea['stock_nuevo'],
                ]);

                if ((int) $linea['stock_nuevo'] <= (int) $refaccion['stock_minimo'] && (int) $refaccion['stock_minimo'] > 0) {
                    $notificar[] = [$refaccionId, (string) $refaccion['nombre'], (int) $linea['stock_nuevo']];
                }
            }

            $this->auditoria->registrar('crear', 'punto_venta', $ventaId, null, [
                'folio' => $folio,
                'items' => count($normalizados),
                'total' => $total,
            ]);

            $db->commit();

            foreach ($notificar as [$refaccionId, $nombre, $stock]) {
                try {
                    (new NotificacionService())->stockBajo((int) $refaccionId, (string) $nombre, (int) $stock);
                } catch (\Throwable) {
                    // La venta ya quedo registrada; una notificacion fallida no debe revertir caja ni stock.
                }
            }

            return $ventaId;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function generarFolio(): string
    {
        do {
            $folio = 'VTA-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        } while ($this->ventas->existsFolio($folio));

        return $folio;
    }
}
