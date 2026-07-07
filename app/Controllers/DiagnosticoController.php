<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\DiagnosticoService;

final class DiagnosticoController
{
    public function store(Request $request): void
    {
        Auth::requirePermission('diagnosticos', 'crear');
        try {
            (new DiagnosticoService())->crear($request->all());
            Session::flash('success', 'Diagnostico registrado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/ordenes/' . (int) $request->input('orden_id'));
    }
}
