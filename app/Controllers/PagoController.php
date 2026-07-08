<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\PagoService;
use App\Validators\PagoValidator;

final class PagoController
{
    public function store(Request $request): void
    {
        Auth::requirePermission('pagos', 'crear');
        $errors = PagoValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }
        try {
            (new PagoService())->registrar($request->all());
            Session::flash('success', 'Pago registrado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/ordenes/' . (int) $request->input('orden_id'));
    }

    public function cancelar(Request $request, string $id): void
    {
        Auth::requirePermission('pagos', 'editar');

        $ordenId = (int) $request->input('orden_id');
        try {
            (new PagoService())->cancelar((int) $id, (string) $request->input('motivo_cancelacion', ''));
            Session::flash('success', 'Pago cancelado y saldo recalculado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect($ordenId > 0 ? '/ordenes/' . $ordenId : '/ordenes');
    }
}
