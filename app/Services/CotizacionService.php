<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CotizacionRepository;
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

    public function crear(array $data): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $items = $data['items'] ?? [];
            if ($items === []) {
                $items = [[
                    'tipo' => $data['tipo'] ?? 'servicio',
                    'descripcion' => $data['descripcion'] ?? 'Servicio tecnico',
                    'cantidad' => $data['cantidad'] ?? 1,
                    'precio_unitario' => $data['precio_unitario'] ?? 0,
                ]];
            }

            $subtotal = 0.0;
            foreach ($items as &$item) {
                $item['subtotal'] = calcularSubtotal((float) $item['cantidad'], (float) $item['precio_unitario']);
                $subtotal += $item['subtotal'];
            }
            unset($item);

            $descuento = (float) ($data['descuento'] ?? 0);
            $iva = (float) ($data['iva'] ?? 0);
            $total = calcularTotal($subtotal, $descuento, $iva);
            $ordenId = (int) $data['orden_id'];

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
                    'descripcion' => $item['descripcion'],
                    'cantidad' => (float) $item['cantidad'],
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
        $this->cotizaciones->changeStatus($cotizacionId, $estado, $motivo);

        $cotizacion = $this->buscarPorId($cotizacionId);
        if ($cotizacion) {
            (new DiagnosticoService())->obtenerPorOrden((int) $cotizacion['orden_id']);
            (new OrdenService())->cambiarEstado((int) $cotizacion['orden_id'], $estado === 'aceptada' ? 'autorizada' : 'rechazada', true);
        }
        $this->auditoria->registrar('autorizar', 'cotizaciones', $cotizacionId, null, ['estado' => $estado, 'motivo' => $motivo]);
    }

    private function buscarPorId(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM cotizaciones WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
