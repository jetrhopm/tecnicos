<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\AgendaService;

final class AgendaController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('agenda', 'ver');

        $service = new AgendaService();
        $vista = (string) $request->input('vista', 'dia');
        $vista = in_array($vista, ['dia', 'semana'], true) ? $vista : 'dia';
        $fecha = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->input('fecha', ''))
            ? (string) $request->input('fecha')
            : date('Y-m-d');
        [$desde, $hasta] = $service->rangoDesdeVista($vista, $fecha);

        $filtros = [
            'vista' => $vista,
            'fecha' => $fecha,
            'desde' => $desde->format('Y-m-d H:i:s'),
            'hasta' => $hasta->format('Y-m-d H:i:s'),
            'tecnico_id' => $request->input('tecnico_id'),
            'estado' => $request->input('estado'),
            'tipo' => $request->input('tipo'),
            'q' => trim((string) $request->input('q', '')),
        ];

        View::render('agenda/index', [
            'title' => 'Agenda',
            'eventos' => $service->listar($filtros),
            'filtros' => $filtros,
            'desde' => $desde,
            'hasta' => $hasta,
            'tipos' => AgendaService::TIPOS,
            'estados' => AgendaService::ESTADOS,
            'tecnicos' => (new UserRepository())->activeTechnicians(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('agenda', 'crear');
        try {
            (new AgendaService())->crear($request->all());
            Session::flash('success', 'Evento programado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::back();
    }

    public function estado(Request $request, string $id): void
    {
        Auth::requirePermission('agenda', 'editar');
        try {
            (new AgendaService())->cambiarEstado((int) $id, (string) $request->input('estado', 'programado'));
            Session::flash('success', 'Evento actualizado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::back();
    }
}
