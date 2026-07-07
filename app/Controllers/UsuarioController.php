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
}
