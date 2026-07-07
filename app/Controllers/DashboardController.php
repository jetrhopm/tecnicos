<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\View;
use App\Services\ReporteService;

final class DashboardController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('dashboard', 'ver');
        View::render('dashboard/index', [
            'title' => 'Dashboard',
            'dashboard' => (new ReporteService())->dashboard(),
        ]);
    }
}
