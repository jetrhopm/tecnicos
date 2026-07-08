<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\NotificacionRepository;
use App\Repositories\OrdenRepository;
use App\Repositories\UserRepository;

final class NotificacionService
{
    // Roles que atienden reparaciones (destinatarios de avisos tecnicos).
    private const ROLES_TECNICOS = ['tecnico', 'tecnico_senior'];

    public function __construct(
        private readonly NotificacionRepository $notificaciones = new NotificacionRepository(),
        private readonly UserRepository $users = new UserRepository()
    ) {
    }

    public function crear(int $userId, string $tipo, string $titulo, ?string $mensaje = null, ?string $url = null): void
    {
        try {
            $this->notificaciones->create([
                'user_id' => $userId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje !== null ? mb_substr($mensaje, 0, 255) : null,
                'url' => $url,
            ]);
        } catch (\Throwable) {
            // Una notificacion nunca debe romper la operacion principal.
        }
    }

    public function notificarRoles(array $slugs, string $tipo, string $titulo, ?string $mensaje = null, ?string $url = null, ?int $exceptoUserId = null): void
    {
        try {
            foreach ($this->users->idsPorRoles($slugs) as $userId) {
                if ($exceptoUserId !== null && $userId === $exceptoUserId) {
                    continue;
                }
                $this->crear($userId, $tipo, $titulo, $mensaje, $url);
            }
        } catch (\Throwable) {
            // Silencioso: no interrumpe el flujo que dispara el aviso.
        }
    }

    public function ordenNueva(int $ordenId, string $folio, ?int $exceptoUserId = null): void
    {
        // Aviso a los tecnicos: hay una orden nueva por atender.
        $this->notificarRoles(
            self::ROLES_TECNICOS,
            'orden_nueva',
            'Nueva orden por atender',
            'Orden ' . $folio,
            '/ordenes/' . $ordenId,
            $exceptoUserId
        );
    }

    public function cotizacionAutorizada(int $ordenId): void
    {
        // Aviso al tecnico asignado (o a todos los tecnicos si no hay asignado)
        // de que el cliente autorizo la cotizacion y puede iniciar la reparacion.
        $orden = (new OrdenRepository())->find($ordenId);
        if (!$orden) {
            return;
        }

        $titulo = 'Cotizacion autorizada';
        $mensaje = 'Orden ' . ($orden['folio'] ?? '') . ': ya puedes iniciar la reparacion';
        $url = '/ordenes/' . $ordenId;

        $tecnicoId = (int) ($orden['tecnico_id'] ?? 0);
        if ($tecnicoId > 0) {
            $this->crear($tecnicoId, 'cotizacion_autorizada', $titulo, $mensaje, $url);
            return;
        }

        $this->notificarRoles(self::ROLES_TECNICOS, 'cotizacion_autorizada', $titulo, $mensaje, $url);
    }

    public function recientes(int $userId): array
    {
        try {
            return $this->notificaciones->recientes($userId);
        } catch (\Throwable) {
            return [];
        }
    }

    public function todas(int $userId): array
    {
        try {
            return $this->notificaciones->todas($userId);
        } catch (\Throwable) {
            return [];
        }
    }

    public function contarNoLeidas(int $userId): int
    {
        try {
            return $this->notificaciones->contarNoLeidas($userId);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function abrir(int $id, int $userId): ?array
    {
        $notif = $this->notificaciones->find($id, $userId);
        if (!$notif) {
            return null;
        }
        $this->notificaciones->marcarLeida($id, $userId);
        return $notif;
    }

    public function marcarTodas(int $userId): void
    {
        try {
            $this->notificaciones->marcarTodas($userId);
        } catch (\Throwable) {
        }
    }
}
