<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CajaService;
use App\Services\ConfiguracionService;

final class CajaController
{
    public function index(): void
    {
        Auth::requirePermission('caja', 'ver');
        View::render('caja/index', array_merge(['title' => 'Caja y corte'], (new CajaService())->pantalla()));
    }

    public function abrir(Request $request): void
    {
        Auth::requirePermission('caja', 'administrar');
        try {
            (new CajaService())->abrir($request->all());
            Session::flash('success', 'Caja iniciada correctamente.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect('/caja');
    }

    public function retirar(Request $request): void
    {
        Auth::requirePermission('caja', 'administrar');
        try {
            (new CajaService())->retirar($request->all());
            Session::flash('success', 'Retiro de caja registrado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect('/caja');
    }

    public function cerrar(Request $request): void
    {
        Auth::requirePermission('caja', 'editar');
        try {
            $turnoId = (new CajaService())->cerrar($request->all());
            Session::flash('success', 'Caja cerrada correctamente.');
            Response::redirect('/caja/' . $turnoId . '/imprimir');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::redirect('/caja');
        }
    }

    public function imprimir(Request $request, string $id): void
    {
        Auth::requirePermission('caja', 'imprimir');
        try {
            $corte = (new CajaService())->corte((int) $id);
        } catch (\Throwable) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Corte no encontrado']);
            return;
        }

        $cfg = new ConfiguracionService();
        View::render('print/corte_caja', [
            'title' => 'Corte de caja',
            'corte' => $corte,
            'negocio' => [
                'nombre' => $cfg->get('negocio.nombre', $cfg->get('sistema.nombre', 'Servicio Tecnico')),
                'telefono' => $cfg->get('negocio.telefono', ''),
                'email' => $cfg->get('negocio.email', ''),
            ],
        ], 'layouts/print');
    }
}
