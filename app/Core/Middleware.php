<?php

declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function securityHeaders(): void
    {
        /*
         * Cabeceras de proteccion del navegador para toda respuesta del sistema.
         * camera=(self) se mantiene porque el modulo Entregas usa la camara
         * para escanear codigos de barras.
         */
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(self), microphone=(), geolocation=()');

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function enforceSession(): void
    {
        /*
         * Cierre de sesion por inactividad. Por defecto 2 h (SESSION_IDLE_MINUTES).
         * Si el usuario eligio "No cerrar sesion" (_persist), no expira por
         * inactividad; solo cierra con logout explicito o al vencer la cookie.
         */
        if (PHP_SAPI === 'cli' || !Auth::check()) {
            return;
        }

        if (Session::get('_persist')) {
            Session::put('_last_activity', time());
            return;
        }

        $idle = max(1, (int) env_value('SESSION_IDLE_MINUTES', 120)) * 60;
        $last = (int) Session::get('_last_activity', time());

        if (time() - $last > $idle) {
            Session::forget('user_id');
            Session::forget('user_name');
            Session::forget('_persist');
            Session::forget('_last_activity');
            Session::flash('error', 'Tu sesion se cerro por inactividad.');
            return;
        }

        Session::put('_last_activity', time());
    }

    public static function csrf(Request $request): void
    {
        /*
         * Se ejecuta antes del router. Bloquea POST/PUT/PATCH/DELETE sin token
         * valido, incluida la API: mientras la API use la cookie de sesion del
         * panel, tambien es alcanzable desde un sitio atacante. Los clientes
         * JSON pueden mandar el token en el header X-CSRF-TOKEN o en _csrf.
         */
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return;
        }

        $token = (string) ($request->input('_csrf') ?? '');
        if ($token === '') {
            $token = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        }

        if (Csrf::verify($token)) {
            return;
        }

        if (str_starts_with($request->path(), '/api')) {
            JsonResponse::error('Token CSRF invalido o ausente.', [], 403);
        }

        Session::flash('error', 'La sesion expiro o el formulario no es valido.');
        Response::back();
    }
}
