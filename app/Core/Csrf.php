<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
    /*
     * Proteccion contra CSRF en formularios HTML.
     * Fuente: token aleatorio guardado en sesion.
     * Destino: input oculto _csrf que Middleware valida antes de modificar datos.
     */
    public static function token(): string
    {
        $token = Session::get('_csrf_token');
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::put('_csrf_token', $token);
        }

        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(?string $token): bool
    {
        // hash_equals evita comparaciones vulnerables a timing attacks.
        return is_string($token) && hash_equals((string) Session::get('_csrf_token', ''), $token);
    }
}
