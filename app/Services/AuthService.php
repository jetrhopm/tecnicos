<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function attempt(string $email, string $password): bool
    {
        /*
         * Login.
         * Fuente: email/password del formulario.
         * Revisa: usuario activo y password_verify contra hash guardado.
         * Destino: sesion del navegador. No envia password a ningun tercero.
         */
        $user = $this->users->findByEmail($email);
        if (!$user || $user['status'] !== 'activo' || !password_verify($password, $user['password'])) {
            $this->auditoria->registrar('login_fallido', 'auth', null, null, ['email' => $email]);
            return false;
        }

        Session::regenerate();
        // Regenerar ID reduce session fixation despues de autenticacion correcta.
        Session::put('user_id', (int) $user['id']);
        Session::put('user_name', $user['name']);
        $this->users->markLogin((int) $user['id']);
        $this->auditoria->registrar('login_correcto', 'auth', (int) $user['id']);
        return true;
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');
        $this->auditoria->registrar('logout', 'auth', $userId);
        Session::destroy();
    }
}
