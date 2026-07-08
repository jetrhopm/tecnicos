<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CotizacionRepository;
use App\Repositories\InventarioRepository;
use App\Repositories\OrdenRepository;
use RuntimeException;

final class CotizacionService
{
    public function __construct(
        private readonly CotizacionRepository $cotizaciones = new CotizacionRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function obtenerPorOrden(int $ordenId): ?array
    {
        $cotizacion = $this->cotizaciones->latestForOrder($ordenId);
        if ($cotizacion) {
            $cotizacion['items'] = $this->cotizaciones->items((int) $cotizacion['id']);
        }

        return $cotizacion;
    }

    public function refaccionesPendientesPorOrden(int $ordenId): array
    {
        return $this->cotizaciones->refaccionesCotizadasPendientes($ordenId);
    }

    public function crear(array $data): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $ordenId = (int) ($data['orden_id'] ?? 0);
            $orden = (new OrdenRepository())->find($ordenId);
            if (!$orden) {
                throw new RuntimeException('Orden no encontrada.');
            }
            if (in_array((string) $orden['estado'], ['entregada', 'cancelada'], true)) {
                throw new RuntimeException('No se puede cotizar una orden entregada o cancelada.');
            }

            $ultima = $this->cotizaciones->latestForOrderForUpdate($ordenId);
            if ($ultima && $ultima['estado'] === 'pendiente') {
                throw new RuntimeException('Ya existe una cotizacion pendiente. Debe aceptarse, rechazarse o vencerse antes de crear otra version.');
            }

            $items = $data['items'] ?? [];
            if ($items === []) {
                $items = [[
                    'tipo' => $data['tipo'] ?? 'servicio',
                    'refaccion_id' => $data['refaccion_id'] ?? null,
                    'descripcion' => $data['descripcion'] ?? 'Servicio tecnico',
                    'cantidad' => $data['cantidad'] ?? 1,
                    'precio_unitario' => $data['precio_unitario'] ?? 0,
                ]];
            }

            $subtotal = 0.0;
            $inventario = new InventarioRepository();
            foreach ($items as &$item) {
                $cantidad = (float) ($item['cantidad'] ?? 0);
                $precio = (float) ($item['precio_unitario'] ?? 0);
                $descripcion = trim((string) ($item['descripcion'] ?? ''));
                $refaccionId = !empty($item['refaccion_id']) ? (int) $item['refaccion_id'] : null;
                $costoUnitario = 0.0;

                if ($refaccionId !== null) {
                    $refaccion = $inventario->find($refaccionId);
                    if (!$refaccion || $refaccion['estatus'] !== 'activo') {
                        throw new RuntimeException('La refaccion seleccionada no existe o esta inactiva.');
                    }
                    $item['tipo'] = 'refaccion';
                    $descripcion = $descripcion !== ''
                        ? $descripcion
                        : trim((string) $refaccion['nombre'] . ' ' . (string) $refaccion['sku']);
                    if ($precio <= 0) {
                        $precio = (float) $refaccion['precio_venta'];
                    }
                    $costoUnitario = (float) $refaccion['costo'];
                }

                if ($descripcion === '') {
                    throw new RuntimeException('Cada concepto de cotizacion debe tener descripcion.');
                }
                if ($cantidad <= 0) {
                    throw new RuntimeException('La cantidad de cada concepto debe ser mayor a cero.');
                }
                if ($precio < 0) {
                    throw new RuntimeException('El precio de cada concepto no puede ser negativo.');
                }
                if (!in_array((string) ($item['tipo'] ?? 'servicio'), ['mano_obra', 'refaccion', 'servicio', 'otro'], true)) {
                    $item['tipo'] = 'servicio';
                }
                $item['descripcion'] = $descripcion;
                $item['cantidad'] = $cantidad;
                $item['precio_unitario'] = $precio;
                $item['refaccion_id'] = $refaccionId;
                $item['costo_unitario'] = $costoUnitario;
                $item['subtotal'] = calcularSubtotal((float) $item['cantidad'], (float) $item['precio_unitario']);
                $subtotal += $item['subtotal'];
            }
            unset($item);

            $descuento = (float) ($data['descuento'] ?? 0);
            $iva = (float) ($data['iva'] ?? 0);
            if ($descuento < 0 || $iva < 0) {
                throw new RuntimeException('Descuento e IVA no pueden ser negativos.');
            }
            if ($descuento > $subtotal) {
                throw new RuntimeException('El descuento no puede superar el subtotal.');
            }
            $total = calcularTotal($subtotal, $descuento, $iva);

            $cotizacionId = $this->cotizaciones->create([
                'orden_id' => $ordenId,
                'version' => $this->cotizaciones->nextVersion($ordenId),
                'subtotal' => $subtotal,
                'descuento' => $descuento,
                'iva' => $iva,
                'total' => $total,
                'vigencia' => $data['vigencia'] ?: null,
                'terminos' => trim((string) ($data['terminos'] ?? '')) ?: null,
                'estado' => 'pendiente',
                'created_by' => Auth::id() ?? 1,
            ]);

            foreach ($items as $item) {
                $this->cotizaciones->addItem([
                    'cotizacion_id' => $cotizacionId,
                    'tipo' => $item['tipo'],
                    'refaccion_id' => $item['refaccion_id'],
                    'descripcion' => $item['descripcion'],
                    'cantidad' => (float) $item['cantidad'],
                    'costo_unitario' => (float) $item['costo_unitario'],
                    'precio_unitario' => (float) $item['precio_unitario'],
                    'subtotal' => (float) $item['subtotal'],
                ]);
            }

            (new OrdenService())->actualizarTotales($ordenId, $total);
            (new OrdenService())->cambiarEstado($ordenId, 'esperando_autorizacion', true);
            $this->auditoria->registrar('crear', 'cotizaciones', $cotizacionId, null, ['orden_id' => $ordenId, 'total' => $total]);
            $db->commit();
            return $cotizacionId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function autorizar(int $cotizacionId, string $estado, ?string $motivo = null): void
    {
        if (!in_array($estado, ['aceptada', 'rechazada'], true)) {
            throw new RuntimeException('Estado de cotizacion no valido.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $cotizacion = $this->cotizaciones->findForUpdate($cotizacionId);
            if (!$cotizacion) {
                throw new RuntimeException('Cotizacion no encontrada.');
            }
            if ($cotizacion['estado'] !== 'pendiente') {
                throw new RuntimeException('Esta cotizacion ya fue atendida y no puede cambiarse.');
            }
            if ($this->estaVencida($cotizacion)) {
                $this->cotizaciones->expirePending($cotizacionId);
                $this->auditoria->registrar('vencer', 'cotizaciones', $cotizacionId, ['estado' => 'pendiente'], ['estado' => 'vencida']);
                $db->commit();
                throw new RuntimeException('La cotizacion ya vencio. Genera una nueva version para solicitar autorizacion.');
            }
            if ($estado === 'rechazada' && trim((string) $motivo) === '') {
                $motivo = 'Rechazada sin motivo especificado.';
            }

            $actualizada = $this->cotizaciones->changePendingStatus($cotizacionId, $estado, $motivo);
            if (!$actualizada) {
                throw new RuntimeException('No se pudo actualizar la cotizacion porque ya no esta pendiente.');
            }

            (new DiagnosticoService())->obtenerPorOrden((int) $cotizacion['orden_id']);
            (new OrdenService())->cambiarEstado((int) $cotizacion['orden_id'], $estado === 'aceptada' ? 'autorizada' : 'rechazada', true);
            if ($estado === 'aceptada') {
                // Avisa al tecnico que ya puede iniciar la reparacion.
                (new NotificacionService())->cotizacionAutorizada((int) $cotizacion['orden_id']);
            }

            $this->auditoria->registrar('autorizar', 'cotizaciones', $cotizacionId, ['estado' => 'pendiente'], ['estado' => $estado, 'motivo' => $motivo]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function buscarPorId(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM cotizaciones WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function estaVencida(array $cotizacion): bool
    {
        $vigencia = trim((string) ($cotizacion['vigencia'] ?? ''));
        return $vigencia !== '' && $vigencia < date('Y-m-d');
    }
}
