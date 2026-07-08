<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\PagoRepository;
use RuntimeException;

final class PagoService
{
    public function __construct(
        private readonly PagoRepository $pagos = new PagoRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listarOrden(int $ordenId): array
    {
        return $this->pagos->forOrder($ordenId);
    }

    public function registrar(array $data, bool $usarTransaccion = true): int
    {
        /*
         * Registro de pagos.
         * Fuente: caja, orden rapida o entrega.
         * Destino: tabla pagos y actualizacion de saldos en ordenes_servicio.
         * Seguridad: valida metodo permitido, recalcula saldo en servidor y audita.
         */
        $db = Database::connection();
        if ($usarTransaccion) {
            $db->beginTransaction();
        }

        try {
            $ordenId = (int) ($data['orden_id'] ?? 0);
            $monto = round((float) ($data['monto'] ?? 0), 2);
            if ($ordenId <= 0) {
                throw new RuntimeException('La orden del pago no es valida.');
            }
            if ($monto <= 0) {
                throw new RuntimeException('El pago debe ser mayor a cero.');
            }

            $orden = (new OrdenService())->obtener($ordenId);
            if (!$orden) {
                throw new RuntimeException('La orden del pago no existe.');
            }

            $saldoDisponible = $this->saldoDisponibleParaPago($ordenId, (float) ($orden['costo_final'] ?? 0));
            if ($monto > $saldoDisponible + 0.009 && !Auth::can('pagos', 'administrar')) {
                throw new RuntimeException('El pago supera el saldo pendiente de la orden.');
            }

            $payload = [
                'orden_id' => $ordenId,
                'monto' => $monto,
                'metodo' => in_array(($data['metodo'] ?? 'efectivo'), ['efectivo','transferencia','tarjeta','otro'], true) ? $data['metodo'] : 'efectivo',
                'referencia' => trim((string) ($data['referencia'] ?? '')) ?: null,
                'usuario_id' => Auth::id() ?? 1,
                'notas' => trim((string) ($data['notas'] ?? '')) ?: null,
            ];

            $id = $this->pagos->create($payload);
            (new OrdenService())->actualizarTotales((int) $payload['orden_id'], (float) ($orden['costo_final'] ?? 0));
            $this->auditoria->registrar('crear', 'pagos', $id, null, $payload);

            if ($usarTransaccion) {
                $db->commit();
            }
            return $id;
        } catch (\Throwable $exception) {
            if ($usarTransaccion) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelar(int $id, string $motivo): void
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new RuntimeException('Indica el motivo de cancelacion del pago.');
        }

        $pago = $this->pagos->find($id);
        if (!$pago) {
            throw new RuntimeException('Pago no encontrado.');
        }
        if (($pago['estado'] ?? '') !== 'activo') {
            throw new RuntimeException('Este pago ya esta cancelado.');
        }
        if (($pago['orden_estado'] ?? '') === 'entregada' && !Auth::can('pagos', 'administrar')) {
            throw new RuntimeException('No se puede cancelar un pago de una orden entregada sin permiso de administracion.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->pagos->cancel($id, Auth::id() ?? 1, mb_substr($motivo, 0, 1000));
            (new OrdenService())->actualizarTotales((int) $pago['orden_id'], (float) ($pago['costo_final'] ?? 0));
            $this->auditoria->registrar('cancelar', 'pagos', $id, $pago, ['motivo' => $motivo, 'cancelado_por' => Auth::id()]);
            $this->auditoria->registrar('pago_cancelado', 'ordenes', (int) $pago['orden_id'], null, [
                'pago_id' => $id,
                'monto' => (float) $pago['monto'],
                'motivo' => $motivo,
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    private function saldoDisponibleParaPago(int $ordenId, float $total): float
    {
        $pagosActivos = (new \App\Repositories\OrdenRepository())->pagosActivos($ordenId);
        return calcularSaldo($total, $pagosActivos);
    }

    public function totalHoy(): float
    {
        return $this->pagos->totalHoy();
    }

    public function porPeriodo(?string $inicio, ?string $fin): array
    {
        return $this->pagos->porPeriodo($inicio, $fin);
    }
}
