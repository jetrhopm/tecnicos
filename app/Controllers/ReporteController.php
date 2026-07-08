<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\PagoService;
use App\Services\ReporteService;

final class ReporteController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('reportes', 'ver');
        $inicio = (string) $request->input('inicio', '');
        $fin = (string) $request->input('fin', '');
        $service = new ReporteService();

        View::render('reportes/index', [
            'title' => 'Reportes',
            'dashboard' => $service->dashboard(),
            'pagos' => (new PagoService())->porPeriodo($inicio, $fin),
            'reportes' => $service->resumen($inicio, $fin),
            'inicio' => $inicio,
            'fin' => $fin,
        ]);
    }

    public function exportar(Request $request): void
    {
        Auth::requirePermission('reportes', 'exportar');

        try {
            $csv = (new ReporteService())->csv(
                (string) $request->input('tipo', 'caja'),
                (string) $request->input('inicio', ''),
                (string) $request->input('fin', '')
            );
        } catch (\Throwable $exception) {
            Response::status(422);
            echo e($exception->getMessage());
            return;
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $csv['archivo'] . '"');
        header('X-Content-Type-Options: nosniff');
        echo $csv['contenido'];
    }
}
