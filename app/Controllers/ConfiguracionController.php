<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\ConfiguracionService;

final class ConfiguracionController
{
    public function index(): void
    {
        Auth::requirePermission('configuracion', 'ver');
        View::render('configuracion/index', [
            'title' => 'Configuracion',
            'grupos' => (new ConfiguracionService())->allGrouped(),
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requirePermission('configuracion', 'editar');

        try {
            (new ConfiguracionService())->actualizar((array) $request->input('config', []), $request->file('logo_taller'));
            Session::flash('success', 'Configuracion actualizada.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect('/configuracion');
    }
}
