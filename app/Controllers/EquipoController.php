<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\ClienteService;
use App\Services\EquipoService;
use App\Validators\EquipoValidator;

final class EquipoController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('equipos', 'ver');
        View::render('equipos/index', [
            'title' => 'Equipos',
            'equipos' => (new EquipoService())->listar($request->input('cliente_id') ? (int) $request->input('cliente_id') : null),
        ]);
    }

    public function create(Request $request): void
    {
        Auth::requirePermission('equipos', 'crear');
        View::render('equipos/form', [
            'title' => 'Nuevo equipo',
            'equipo' => null,
            'clientes' => (new ClienteService())->listar(),
            'cliente_id' => $request->input('cliente_id'),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('equipos', 'crear');
        $errors = EquipoValidator::validate($request->all());
        if ($errors) {
            JsonResponse::error('Datos invalidos', $errors);
        }

        $id = (new EquipoService())->guardar($request->all());
        JsonResponse::success('Equipo creado correctamente', ['id' => $id]);
    }

    public function storeWeb(Request $request): void
    {
        Auth::requirePermission('equipos', 'crear');
        $errors = EquipoValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }
        $id = (new EquipoService())->guardar($request->all());
        Session::flash('success', 'Equipo guardado.');
        Response::redirect('/equipos/' . $id);
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission('equipos', 'ver');
        View::render('equipos/show', [
            'title' => 'Equipo',
            'equipo' => (new EquipoService())->obtener((int) $id),
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission('equipos', 'editar');
        View::render('equipos/form', [
            'title' => 'Editar equipo',
            'equipo' => (new EquipoService())->obtener((int) $id),
            'clientes' => (new ClienteService())->listar(),
            'cliente_id' => null,
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission('equipos', 'editar');
        $errors = EquipoValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }
        (new EquipoService())->guardar($request->all(), (int) $id);
        Session::flash('success', 'Equipo actualizado.');
        Response::redirect('/equipos/' . $id);
    }
}
