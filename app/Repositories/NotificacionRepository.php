<?php

declare(strict_types=1);

namespace App\Repositories;

final class NotificacionRepository extends BaseRepository
{
    public function create(array $data): int
    {
        return $this->insert(
            'INSERT INTO notificaciones (user_id, tipo, titulo, mensaje, url)
             VALUES (:user_id, :tipo, :titulo, :mensaje, :url)',
            $data
        );
    }

    public function recientes(int $userId, int $limite = 8): array
    {
        $limite = max(1, min(50, $limite));
        return $this->fetchAll(
            "SELECT id, tipo, titulo, mensaje, url, leida, created_at
             FROM notificaciones
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT {$limite}",
            ['user_id' => $userId]
        );
    }

    public function todas(int $userId, int $limite = 100): array
    {
        $limite = max(1, min(300, $limite));
        return $this->fetchAll(
            "SELECT id, tipo, titulo, mensaje, url, leida, created_at
             FROM notificaciones
             WHERE user_id = :user_id
             ORDER BY id DESC
             LIMIT {$limite}",
            ['user_id' => $userId]
        );
    }

    public function contarNoLeidas(int $userId): int
    {
        $row = $this->fetch(
            'SELECT COUNT(*) total FROM notificaciones WHERE user_id = :user_id AND leida = 0',
            ['user_id' => $userId]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function find(int $id, int $userId): ?array
    {
        return $this->fetch(
            'SELECT * FROM notificaciones WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function marcarLeida(int $id, int $userId): void
    {
        $this->execute(
            'UPDATE notificaciones SET leida = 1 WHERE id = :id AND user_id = :user_id',
            ['id' => $id, 'user_id' => $userId]
        );
    }

    public function marcarTodas(int $userId): void
    {
        $this->execute(
            'UPDATE notificaciones SET leida = 1 WHERE user_id = :user_id AND leida = 0',
            ['user_id' => $userId]
        );
    }
}
