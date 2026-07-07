<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CotizacionService;
use App\Validators\CotizacionValidator;

final class CotizacionController
{
    public function store(Request $request): void
    {
        Auth::requirePermission('cotizaciones', 'crear');
        $errors = CotizacionValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }

        try {
            (new CotizacionService())->crear($request->all());
            Session::flash('success', 'Cotizacion generada.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/ordenes/' . (int) $request->input('orden_id'));
    }

    public function autorizar(Request $request, string $id): void
    {
        Auth::requirePermission('cotizaciones', 'autorizar');
        (new CotizacionService())->autorizar((int) $id, (string) $request->input('estado'), (string) $request->input('motivo'));
        Session::flash('success', 'Respuesta de cotizacion registrada.');
        Response::back();
    }
}
