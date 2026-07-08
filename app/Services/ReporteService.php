<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GarantiaRepository;
use App\Repositories\InventarioRepository;
use App\Repositories\OrdenRepository;
use App\Repositories\PagoRepository;
use App\Repositories\ReporteRepository;

final class ReporteService
{
    public function dashboard(): array
    {
        $ordenes = new OrdenRepository();
        $agenda = new AgendaService();
        [$agendaDesde, $agendaHasta] = $agenda->rangoDesdeVista('dia', date('Y-m-d'));
        return [
            'ordenes' => $ordenes->dashboardStats(),
            'pagos_hoy' => (new PagoRepository())->totalHoy(),
            'stock_bajo' => count((new InventarioRepository())->stockBajo()),
            'garantias_activas' => count((new GarantiaRepository())->active()),
            'agenda_hoy' => $agenda->listar([
                'desde' => $agendaDesde->format('Y-m-d H:i:s'),
                'hasta' => $agendaHasta->format('Y-m-d H:i:s'),
                'estado' => 'programado',
            ]),
            'por_estado' => (new ReporteRepository())->ordenesPorEstado(),
            'por_tecnico' => (new ReporteRepository())->ordenesPorTecnico(),
            'recientes' => $ordenes->all([]),
        ];
    }
}
