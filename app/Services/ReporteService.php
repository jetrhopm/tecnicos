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

    public function resumen(?string $inicio, ?string $fin): array
    {
        $repo = new ReporteRepository();

        return [
            'caja_resumen' => $repo->corteCajaResumen($inicio, $fin),
            'saldos_pendientes' => $repo->saldosPendientes($inicio, $fin),
            'refacciones_usadas' => $repo->refaccionesMasUsadas($inicio, $fin),
            'utilidad_estimada' => $repo->utilidadEstimada($inicio, $fin),
        ];
    }

    public function csv(string $tipo, ?string $inicio, ?string $fin): array
    {
        $repo = new ReporteRepository();
        $reportes = [
            'caja' => [
                'archivo' => 'corte-caja.csv',
                'columnas' => ['fecha', 'usuario', 'metodo', 'operaciones', 'total'],
                'filas' => $repo->corteCajaResumen($inicio, $fin),
            ],
            'saldos' => [
                'archivo' => 'saldos-pendientes.csv',
                'columnas' => ['folio', 'cliente', 'telefono', 'estado', 'fecha_recepcion', 'costo_final', 'anticipo', 'saldo_pendiente'],
                'filas' => $repo->saldosPendientes($inicio, $fin),
            ],
            'refacciones' => [
                'archivo' => 'refacciones-mas-usadas.csv',
                'columnas' => ['sku', 'nombre', 'categoria', 'cantidad_usada', 'venta_total', 'costo_total', 'utilidad_estimada'],
                'filas' => $repo->refaccionesMasUsadas($inicio, $fin),
            ],
            'utilidad' => [
                'archivo' => 'utilidad-estimada.csv',
                'columnas' => ['folio', 'cliente', 'estado', 'fecha_recepcion', 'total_orden', 'venta_refacciones', 'costo_refacciones', 'mano_obra_estimada', 'utilidad_estimada'],
                'filas' => $repo->utilidadEstimada($inicio, $fin),
            ],
        ];

        if (!isset($reportes[$tipo])) {
            throw new \RuntimeException('Reporte no valido.');
        }

        $reporte = $reportes[$tipo];
        $csv = $this->crearCsv($reporte['columnas'], $reporte['filas']);

        return ['archivo' => $reporte['archivo'], 'contenido' => $csv];
    }

    private function crearCsv(array $columnas, array $filas): string
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $columnas);

        foreach ($filas as $fila) {
            $linea = [];
            foreach ($columnas as $columna) {
                $linea[] = $fila[$columna] ?? '';
            }
            fputcsv($handle, $linea);
        }

        rewind($handle);
        $contenido = stream_get_contents($handle) ?: '';
        fclose($handle);

        return $contenido;
    }
}
