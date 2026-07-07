<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UserRepository;

final class Auth
{
    /*
     * Identidad del usuario autenticado.
     * Fuente: Session::get('user_id'), creada despues de login correcto.
     * Destino: controllers/services que necesitan saber quien ejecuta una accion.
     */
    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id ? (int) $id : null;
    }

    public static function user(): ?array
    {
        $id = self::id();
        return $id ? (new UserRepository())->find($id) : null;
    }

    public static function check(): bool
    {
        return self::id() !== null;
    }

    public static function requireLogin(): void
    {
        // Protege rutas privadas: si no hay sesion, redirige a login o responde JSON en API.
        if (!self::check()) {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            if (str_contains($path, '/api/')) {
                JsonResponse::error('No autenticado', [], 401);
            }
            Session::flash('error', 'Debes iniciar sesion.');
            Response::redirect('/login');
        }
    }

    public static function can(string $module, string $action): bool
    {
        // Consulta permisos efectivos por rol en base de datos; no confia solo en la pantalla visible.
        $userId = self::id();
        if (!$userId) {
            return false;
        }

        return (new UserRepository())->hasPermission($userId, $module, $action);
    }

    public static function requirePermission(string $module, string $action): void
    {
        // Punto de control por accion. Si falla, registra auditoria para detectar abuso o mala configuracion.
        self::requireLogin();
        if (!self::can($module, $action)) {
            (new \App\Services\AuditoriaService())->registrar('acceso_denegado', $module, null, null, ['accion' => $action]);
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            if (str_contains($path, '/api/')) {
                JsonResponse::error('No tienes permiso para esta accion', [], 403);
            }
            Response::status(403);
            View::render('errors/403', ['title' => 'Acceso denegado']);
            exit;
        }
    }
}
