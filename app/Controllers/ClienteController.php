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
use App\Validators\ClienteValidator;

final class ClienteController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('clientes', 'ver');
        View::render('clientes/index', [
            'title' => 'Clientes',
            'clientes' => (new ClienteService())->listar((string) $request->input('q', '')),
            'q' => (string) $request->input('q', ''),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('clientes', 'crear');
        View::render('clientes/form', ['title' => 'Nuevo cliente', 'cliente' => null]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('clientes', 'crear');
        $errors = ClienteValidator::validate($request->all());
        if ($errors) {
            JsonResponse::error('Datos invalidos', $errors);
        }

        try {
            $id = (new ClienteService())->guardar($request->all());
            JsonResponse::success('Cliente creado correctamente', ['id' => $id]);
        } catch (\Throwable $exception) {
            JsonResponse::error($exception->getMessage(), []);
        }
    }

    public function storeWeb(Request $request): void
    {
        Auth::requirePermission('clientes', 'crear');
        $errors = ClienteValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }

        try {
            $id = (new ClienteService())->guardar($request->all());
            Session::flash('success', 'Cliente guardado.');
            Response::redirect('/clientes/' . $id);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission('clientes', 'ver');
        $service = new ClienteService();
        $cliente = $service->obtener((int) $id);
        if (!$cliente) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Cliente no encontrado']);
            return;
        }

        View::render('clientes/show', [
            'title' => $cliente['nombre_completo'],
            'cliente' => $cliente,
            'historial' => $service->historial((int) $id),
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission('clientes', 'editar');
        View::render('clientes/form', [
            'title' => 'Editar cliente',
            'cliente' => (new ClienteService())->obtener((int) $id),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission('clientes', 'editar');
        $errors = ClienteValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }

        try {
            (new ClienteService())->guardar($request->all(), (int) $id);
            Session::flash('success', 'Cliente actualizado.');
            Response::redirect('/clientes/' . $id);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }
}
