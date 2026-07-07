<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\AuthService;

final class AuthController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect('/');
        }

        View::render('auth/login', ['title' => 'Iniciar sesion'], 'layouts/guest');
    }

    public function login(Request $request): void
    {
        $email = trim((string) $request->input('email'));
        $password = (string) $request->input('password');
        $recordar = (bool) $request->input('recordarme');

        try {
            if ((new AuthService())->attempt($email, $password, $recordar)) {
                Response::redirect('/');
            }
            Session::flash('error', 'Credenciales incorrectas o usuario inactivo.');
        } catch (\RuntimeException $exception) {
            // Bloqueo temporal por demasiados intentos fallidos.
            Session::flash('error', $exception->getMessage());
        }

        Response::redirect('/login');
    }

    public function logout(): void
    {
        (new AuthService())->logout();
        Response::redirect('/login');
    }
}
