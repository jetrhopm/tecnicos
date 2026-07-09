<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\CajaRepository;
use RuntimeException;

final class CajaService
{
    public function __construct(
        private readonly CajaRepository $caja = new CajaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function pantalla(): array
    {
        $turno = $this->caja->turnoAbierto();
        return [
            'turno' => $turno,
            'corte' => $turno ? $this->corte((int) $turno['id']) : null,
            'turnos' => $this->caja->recientes(),
        ];
    }

    public function corte(int $turnoId): array
    {
        $turno = $this->caja->find($turnoId);
        if (!$turno) {
            throw new RuntimeException('Turno de caja no encontrado.');
        }

        $hasta = ($turno['estado'] ?? '') === 'cerrado' ? (string) $turno['closed_at'] : null;
        $resumen = $this->normalizarResumen($this->caja->resumenIngresos((string) $turno['opened_at'], $hasta));
        $retiros = $this->caja->totalRetiros($turnoId);
        $esperado = [
            'efectivo' => round((float) $turno['fondo_inicial'] + $resumen['efectivo']['total'] - $retiros, 2),
            'transferencia' => round($resumen['transferencia']['total'], 2),
            'tarjeta' => round($resumen['tarjeta']['total'], 2),
            'otro' => round($resumen['otro']['total'], 2),
        ];

        return [
            'turno' => $turno,
            'resumen' => $resumen,
            'esperado' => $esperado,
            'total_esperado' => round(array_sum($esperado), 2),
            'retiros' => $retiros,
            'movimientos' => $this->caja->movimientos($turnoId),
            'operaciones' => $this->caja->operaciones((string) $turno['opened_at'], $hasta),
        ];
    }

    public function abrir(array $data): int
    {
        if (!Auth::can('caja', 'administrar')) {
            throw new RuntimeException('Solo admin o superadmin pueden iniciar caja.');
        }

        $fondo = round((float) ($data['fondo_inicial'] ?? 0), 2);
        if ($fondo < 0) {
            throw new RuntimeException('El fondo inicial no puede ser negativo.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            if ($this->caja->turnoAbiertoForUpdate()) {
                throw new RuntimeException('Ya existe una caja abierta.');
            }
            $folio = $this->generarFolio();
            $id = $this->caja->crearTurno([
                'folio' => $folio,
                'fondo_inicial' => $fondo,
                'abierto_por' => Auth::id() ?? 1,
            ]);
            $this->auditoria->registrar('abrir', 'caja', $id, null, [
                'folio' => $folio,
                'fondo_inicial' => $fondo,
                'usuario_id' => Auth::id(),
            ]);
            $db->commit();
            return $id;
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function retirar(array $data): void
    {
        if (!Auth::can('caja', 'administrar')) {
            throw new RuntimeException('Solo admin o superadmin pueden registrar retiros de caja.');
        }

        $monto = round((float) ($data['monto'] ?? 0), 2);
        $concepto = trim((string) ($data['concepto'] ?? ''));
        if ($monto <= 0) {
            throw new RuntimeException('El retiro debe ser mayor a cero.');
        }
        if ($concepto === '') {
            throw new RuntimeException('Indica el motivo del retiro.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $turno = $this->caja->turnoAbiertoForUpdate();
            if (!$turno) {
                throw new RuntimeException('No hay caja abierta para registrar retiro.');
            }
            $corte = $this->corte((int) $turno['id']);
            if ($monto > $corte['esperado']['efectivo'] + 0.009) {
                throw new RuntimeException('El retiro supera el efectivo esperado en caja.');
            }

            $movimientoId = $this->caja->registrarMovimiento([
                'turno_id' => (int) $turno['id'],
                'tipo' => 'retiro',
                'monto' => $monto,
                'metodo' => 'efectivo',
                'concepto' => mb_substr($concepto, 0, 255),
                'usuario_id' => Auth::id() ?? 1,
            ]);
            $this->auditoria->registrar('retiro', 'caja', (int) $turno['id'], null, [
                'movimiento_id' => $movimientoId,
                'monto' => $monto,
                'concepto' => $concepto,
                'usuario_id' => Auth::id(),
            ]);
            $db->commit();
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    public function cerrar(array $data): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $turno = $this->caja->turnoAbiertoForUpdate();
            if (!$turno) {
                throw new RuntimeException('No hay caja abierta para cerrar.');
            }

            $contado = [
                'efectivo' => $this->montoContado($data, 'efectivo_contado'),
                'transferencia' => $this->montoContado($data, 'transferencia_contado'),
                'tarjeta' => $this->montoContado($data, 'tarjeta_contado'),
                'otro' => $this->montoContado($data, 'otro_contado'),
            ];
            $corte = $this->corte((int) $turno['id']);
            $totalContado = round(array_sum($contado), 2);
            $totalEsperado = (float) $corte['total_esperado'];
            $diferencia = round($totalContado - $totalEsperado, 2);

            $this->caja->cerrarTurno((int) $turno['id'], [
                'efectivo_contado' => $contado['efectivo'],
                'transferencia_contado' => $contado['transferencia'],
                'tarjeta_contado' => $contado['tarjeta'],
                'otro_contado' => $contado['otro'],
                'total_esperado' => $totalEsperado,
                'total_contado' => $totalContado,
                'diferencia' => $diferencia,
                'cerrado_por' => Auth::id() ?? 1,
                'observaciones' => trim((string) ($data['observaciones'] ?? '')) ?: null,
            ]);
            $this->auditoria->registrar('cerrar', 'caja', (int) $turno['id'], [
                'estado' => 'abierto',
                'total_esperado' => $totalEsperado,
            ], [
                'estado' => 'cerrado',
                'total_contado' => $totalContado,
                'diferencia' => $diferencia,
                'usuario_id' => Auth::id(),
            ]);
            $db->commit();
            return (int) $turno['id'];
        } catch (\Throwable $exception) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $exception;
        }
    }

    private function montoContado(array $data, string $key): float
    {
        $value = $data[$key] ?? 0;
        if ($value !== '' && !is_numeric($value)) {
            throw new RuntimeException('Los montos contados deben ser numericos.');
        }
        $monto = round((float) $value, 2);
        if ($monto < 0) {
            throw new RuntimeException('Los montos contados no pueden ser negativos.');
        }

        return $monto;
    }

    private function normalizarResumen(array $rows): array
    {
        $resumen = [];
        foreach (['efectivo', 'transferencia', 'tarjeta', 'otro'] as $metodo) {
            $resumen[$metodo] = ['metodo' => $metodo, 'operaciones' => 0, 'total' => 0.0];
        }
        foreach ($rows as $row) {
            $metodo = in_array($row['metodo'] ?? '', ['efectivo', 'transferencia', 'tarjeta', 'otro'], true)
                ? (string) $row['metodo']
                : 'otro';
            $resumen[$metodo] = [
                'metodo' => $metodo,
                'operaciones' => (int) ($row['operaciones'] ?? 0),
                'total' => (float) ($row['total'] ?? 0),
            ];
        }

        return $resumen;
    }

    private function generarFolio(): string
    {
        do {
            $folio = 'CJ-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        } while ($this->caja->existsFolio($folio));

        return $folio;
    }
}
