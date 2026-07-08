<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Services\NotificacionService;

final class NotificacionController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        View::render('notificaciones/index', [
            'title' => 'Notificaciones',
            'notificaciones' => (new NotificacionService())->todas((int) Auth::id()),
        ]);
    }

    public function abrir(Request $request, string $id): void
    {
        Auth::requireLogin();

        // Marca la notificacion como leida y lleva a la orden relacionada.
        $notif = (new NotificacionService())->abrir((int) $id, (int) Auth::id());
        Response::redirect($notif && !empty($notif['url']) ? (string) $notif['url'] : '/notificaciones');
    }

    public function leerTodas(Request $request): void
    {
        Auth::requireLogin();

        (new NotificacionService())->marcarTodas((int) Auth::id());
        Response::back();
    }
}
