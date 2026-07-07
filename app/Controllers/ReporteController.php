<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\PagoService;
use App\Services\ReporteService;

final class ReporteController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('reportes', 'ver');
        View::render('reportes/index', [
            'title' => 'Reportes',
            'dashboard' => (new ReporteService())->dashboard(),
            'pagos' => (new PagoService())->porPeriodo($request->input('inicio'), $request->input('fin')),
        ]);
    }
}
