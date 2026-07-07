<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name = (string) env_value('SESSION_NAME', 'servicio_tecnico_session');
        // Rechaza IDs de sesion no generados por el servidor (anti session fixation).
        ini_set('session.use_strict_mode', '1');

        // Carpeta de sesiones propia, fuera del C:\xampp\tmp compartido, para que
        // la limpieza de otros proyectos no cierre las sesiones de este sistema.
        $dir = BASE_PATH . '/storage/sessions';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            session_save_path($dir);
        }

        // El servidor conserva el archivo de sesion hasta el maximo de "recordarme".
        // El cierre por inactividad (2 h por defecto) lo aplica la app en
        // Middleware::enforceSession(), no la recoleccion de basura de PHP.
        $maxDias = max(1, (int) env_value('SESSION_REMEMBER_DAYS', 30));
        ini_set('session.gc_maxlifetime', (string) ($maxDias * 86400));

        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
        session_start();
    }

    public static function persistCookie(int $dias): void
    {
        // Convierte la cookie de sesion en persistente (sobrevive al cierre del
        // navegador) para la opcion "No cerrar sesion".
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        setcookie(session_name(), session_id(), [
            'expires' => time() + max(1, $dias) * 86400,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        ]);
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
