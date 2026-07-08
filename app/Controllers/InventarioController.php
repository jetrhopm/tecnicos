<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\InventarioService;
use App\Services\ProveedorService;

final class InventarioController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('inventario', 'ver');

        $filtros = [
            'q' => trim((string) $request->input('q', '')),
            'solo_bajo' => $request->input('solo_bajo') ? 1 : 0,
        ];
        View::render('inventario/index', [
            'title' => 'Almacen e inventario',
            'refacciones' => (new InventarioService())->listar($filtros),
            'stockBajo' => (new InventarioService())->stockBajo(),
            'filtros' => $filtros,
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('inventario', 'crear');
        View::render('inventario/form', [
            'title' => 'Nueva refaccion',
            'refaccion' => null,
            'proveedores' => (new ProveedorService())->activos(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('inventario', 'crear');
        try {
            $id = (new InventarioService())->guardar($request->all());
            Session::flash('success', 'Refaccion guardada.');
            Response::redirect('/inventario/' . $id);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission('inventario', 'ver');
        $refaccion = (new InventarioService())->obtener((int) $id);
        if (!$refaccion) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Refaccion no encontrada']);
            return;
        }

        View::render('inventario/show', [
            'title' => $refaccion['nombre'],
            'refaccion' => $refaccion,
            'movimientos' => (new InventarioService())->movimientos((int) $id),
        ]);
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission('inventario', 'editar');
        $refaccion = (new InventarioService())->obtener((int) $id);
        if (!$refaccion) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Refaccion no encontrada']);
            return;
        }

        View::render('inventario/form', [
            'title' => 'Editar refaccion',
            'refaccion' => $refaccion,
            'proveedores' => (new ProveedorService())->activos(),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission('inventario', 'editar');
        try {
            (new InventarioService())->guardar($request->all(), (int) $id);
            Session::flash('success', 'Refaccion actualizada.');
            Response::redirect('/inventario/' . $id);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function movimiento(Request $request, string $id): void
    {
        Auth::requirePermission('inventario', 'editar');
        try {
            (new InventarioService())->movimiento(
                (int) $id,
                (string) $request->input('tipo', 'entrada'),
                (int) $request->input('cantidad', 0),
                (string) $request->input('motivo', ''),
                $request->input('costo_unitario') !== null && $request->input('costo_unitario') !== ''
                    ? (float) $request->input('costo_unitario')
                    : null
            );
            Session::flash('success', 'Movimiento registrado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/inventario/' . $id);
    }
}
