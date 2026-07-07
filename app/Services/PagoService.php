<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\PagoRepository;

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
            $payload = [
                'orden_id' => (int) $data['orden_id'],
                'monto' => (float) $data['monto'],
                'metodo' => in_array(($data['metodo'] ?? 'efectivo'), ['efectivo','transferencia','tarjeta','otro'], true) ? $data['metodo'] : 'efectivo',
                'referencia' => trim((string) ($data['referencia'] ?? '')) ?: null,
                'usuario_id' => Auth::id() ?? 1,
                'notas' => trim((string) ($data['notas'] ?? '')) ?: null,
            ];

            $id = $this->pagos->create($payload);
            $orden = (new OrdenService())->obtener((int) $payload['orden_id']);
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

    public function totalHoy(): float
    {
        return $this->pagos->totalHoy();
    }

    public function porPeriodo(?string $inicio, ?string $fin): array
    {
        return $this->pagos->porPeriodo($inicio, $fin);
    }
}
