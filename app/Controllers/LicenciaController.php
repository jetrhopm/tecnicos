<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\LicenciaService;

final class LicenciaController
{
    public function index(): void
    {
        Auth::requirePermission('licencias', 'ver');
        if (!$this->esLicenciante()) {
            Response::status(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            return;
        }

        View::render('licencias/index', [
            'title' => 'Licencia demo',
            'estado' => (new LicenciaService())->estado(),
        ]);
    }

    public function update(Request $request): void
    {
        Auth::requirePermission('licencias', 'administrar');
        if (!$this->esLicenciante()) {
            Response::status(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            return;
        }

        try {
            (new LicenciaService())->guardar($request->all());
            Session::flash('success', 'Licencia demo actualizada.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/licencia');
    }

    public function reiniciar(): void
    {
        Auth::requirePermission('licencias', 'administrar');
        if (!$this->esLicenciante()) {
            Response::status(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            return;
        }

        try {
            (new LicenciaService())->reiniciar();
            Session::flash('success', 'Contador de demo reiniciado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/licencia');
    }

    private function esLicenciante(): bool
    {
        return (new LicenciaService())->usuarioActualEsLicenciante();
    }
}
