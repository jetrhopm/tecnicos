<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\DiagnosticoRepository;

final class DiagnosticoService
{
    public function __construct(
        private readonly DiagnosticoRepository $diagnosticos = new DiagnosticoRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function obtenerPorOrden(int $ordenId): ?array
    {
        return $this->diagnosticos->latestForOrder($ordenId);
    }

    public function crear(array $data): int
    {
        $manoObra = (float) ($data['costo_mano_obra'] ?? 0);
        $refacciones = (float) ($data['costo_refacciones'] ?? 0);
        $payload = [
            'orden_id' => (int) $data['orden_id'],
            'tecnico_id' => Auth::id() ?? (int) ($data['tecnico_id'] ?? 1),
            'diagnostico_tecnico' => trim((string) $data['diagnostico_tecnico']),
            'diagnostico_cliente' => trim((string) ($data['diagnostico_cliente'] ?? '')) ?: null,
            'causa_probable' => trim((string) ($data['causa_probable'] ?? '')) ?: null,
            'pruebas_realizadas' => trim((string) ($data['pruebas_realizadas'] ?? '')) ?: null,
            'piezas_necesarias' => trim((string) ($data['piezas_necesarias'] ?? '')) ?: null,
            'tiempo_estimado' => trim((string) ($data['tiempo_estimado'] ?? '')) ?: null,
            'costo_mano_obra' => $manoObra,
            'costo_refacciones' => $refacciones,
            'costo_total_sugerido' => calcularTotal($manoObra + $refacciones, 0, 0),
        ];

        $id = $this->diagnosticos->create($payload);
        (new OrdenService())->cambiarEstado((int) $payload['orden_id'], 'diagnosticada', true);
        $this->auditoria->registrar('crear', 'diagnosticos', $id, null, $payload);
        return $id;
    }
}
