<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    private const MAX_INTENTOS = 5;
    private const VENTANA_MINUTOS = 15;

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function attempt(string $email, string $password, bool $recordar = false): bool
    {
        /*
         * Login.
         * Fuente: email/password del formulario.
         * Revisa: usuario activo y password_verify contra hash guardado.
         * Destino: sesion del navegador. No envia password a ningun tercero.
         */
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        if ($this->auditoria->intentosLoginFallidos($email, $ip, self::VENTANA_MINUTOS) >= self::MAX_INTENTOS) {
            // Freno de fuerza bruta: tras varios fallos recientes se exige esperar.
            $this->auditoria->registrar('login_bloqueado', 'auth', null, null, ['email' => $email]);
            throw new RuntimeException(sprintf(
                'Demasiados intentos fallidos. Espera %d minutos e intenta de nuevo.',
                self::VENTANA_MINUTOS
            ));
        }

        $user = $this->users->findByEmail($email);
        if (!$user || $user['status'] !== 'activo' || !password_verify($password, $user['password'])) {
            $this->auditoria->registrar('login_fallido', 'auth', null, null, ['email' => $email]);
            return false;
        }

        Session::regenerate();
        // Regenerar ID reduce session fixation despues de autenticacion correcta.
        Session::put('user_id', (int) $user['id']);
        Session::put('user_name', $user['name']);
        Session::put('_persist', $recordar);
        Session::put('_last_activity', time());

        if ($recordar) {
            // "No cerrar sesion": cookie persistente que sobrevive al navegador.
            Session::persistCookie((int) env_value('SESSION_REMEMBER_DAYS', 30));
        }

        $this->users->markLogin((int) $user['id']);
        $this->auditoria->registrar('login_correcto', 'auth', (int) $user['id'], null, ['recordar' => $recordar]);
        return true;
    }

    public function logout(): void
    {
        $userId = Session::get('user_id');
        $this->auditoria->registrar('logout', 'auth', $userId);
        Session::destroy();
    }
}
