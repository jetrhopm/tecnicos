<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\InventarioService;

final class InventarioController
{
    public function index(): void
    {
        Auth::requirePermission('inventario', 'ver');
        View::render('inventario/index', ['title' => 'Inventario', 'stockBajo' => (new InventarioService())->stockBajo()]);
    }
}
