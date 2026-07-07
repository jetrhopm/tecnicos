<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\GarantiaService;

final class GarantiaController
{
    public function index(): void
    {
        Auth::requirePermission('garantias', 'ver');
        View::render('garantias/index', ['title' => 'Garantias', 'garantias' => (new GarantiaService())->activas()]);
    }
}
