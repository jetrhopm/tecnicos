<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;

final class AuditoriaService
{
    public function registrar(string $accion, string $modulo, int|string|null $registroId, mixed $anteriores = null, mixed $nuevos = null): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                "INSERT INTO auditoria (usuario_id, accion, modulo, registro_id, datos_anteriores, datos_nuevos, ip, user_agent)
                 VALUES (:usuario_id, :accion, :modulo, :registro_id, :anteriores, :nuevos, :ip, :user_agent)"
            );
            $stmt->execute([
                'usuario_id' => Auth::id(),
                'accion' => $accion,
                'modulo' => $modulo,
                'registro_id' => $registroId,
                'anteriores' => $anteriores === null ? null : json_encode($anteriores, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'nuevos' => $nuevos === null ? null : json_encode($nuevos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'cli',
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'cli', 0, 255),
            ]);
        } catch (\Throwable) {
            // La auditoria no debe tumbar la operacion principal.
        }
    }
}
