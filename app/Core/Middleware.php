<?php

declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function csrf(Request $request): void
    {
        /*
         * Se ejecuta antes del router. Bloquea POST/PUT/PATCH/DELETE de formularios
         * sin token valido. La API queda fuera aqui porque debe manejar otro esquema
         * de autenticacion/token cuando se endurezca para apps externas.
         */
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !str_starts_with($request->path(), '/api')) {
            if (!Csrf::verify((string) $request->input('_csrf', ''))) {
                Session::flash('error', 'La sesion expiro o el formulario no es valido.');
                Response::back();
            }
        }
    }
}
