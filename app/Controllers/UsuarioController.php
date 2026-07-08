<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\UserService;

final class UsuarioController
{
    public function index(): void
    {
        Auth::requirePermission('usuarios', 'ver');
        View::render('usuarios/index', ['title' => 'Usuarios y roles', 'usuarios' => (new UserRepository())->all()]);
    }

    public function create(): void
    {
        Auth::requirePermission('usuarios', 'crear');
        View::render('usuarios/form', [
            'title' => 'Nuevo usuario',
            'roles' => (new UserService())->roles(),
            'usuario' => null,
            'roleIds' => [],
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('usuarios', 'crear');

        try {
            (new UserService())->crear($request->all());
            Session::flash('success', 'Usuario creado correctamente.');
            Response::redirect('/usuarios');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function edit(Request $request, string $id): void
    {
        Auth::requirePermission('usuarios', 'editar');
        $repo = new UserRepository();
        $usuario = $repo->find((int) $id);
        if (!$usuario) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Usuario no encontrado']);
            return;
        }

        $rolesUsuario = $repo->rolesForUser((int) $id);
        View::render('usuarios/form', [
            'title' => 'Editar usuario',
            'roles' => (new UserService())->roles(),
            'usuario' => $usuario,
            'roleIds' => array_map('intval', array_column($rolesUsuario, 'id')),
        ]);
    }

    public function update(Request $request, string $id): void
    {
        Auth::requirePermission('usuarios', 'editar');
        try {
            (new UserService())->actualizar((int) $id, $request->all());
            Session::flash('success', 'Usuario actualizado correctamente.');
            Response::redirect('/usuarios');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function status(Request $request, string $id): void
    {
        Auth::requirePermission('usuarios', 'editar');
        try {
            (new UserService())->cambiarStatus((int) $id, (string) $request->input('status', 'activo'));
            Session::flash('success', 'Estatus de usuario actualizado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::back();
    }
}
