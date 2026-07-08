<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\ProveedorService;

final class ProveedorController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('proveedores', 'ver');
        View::render('proveedores/index', [
            'title' => 'Proveedores',
            'proveedores' => (new ProveedorService())->listar((string) $request->input('q', '')),
            'q' => (string) $request->input('q', ''),
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('proveedores', 'crear');
        View::render('proveedores/form', ['title' => 'Nuevo proveedor', 'proveedor' => null]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('proveedores', 'crear');
        try {
            $id = (new ProveedorService())->guardar($request->all());
            Session::flash('success', 'Proveedor guardado.');
            Response::redirect('/proveedores');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission('proveedores', 'editar');
        $proveedor = (new ProveedorService())->obtener((int) $id);
        if (!$proveedor) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Proveedor no encontrado']);
            return;
        }

        View::render('proveedores/form', ['title' => 'Editar proveedor', 'proveedor' => $proveedor]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission('proveedores', 'editar');
        try {
            (new ProveedorService())->guardar($request->all(), (int) $id);
            Session::flash('success', 'Proveedor actualizado.');
            Response::redirect('/proveedores');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }
}
