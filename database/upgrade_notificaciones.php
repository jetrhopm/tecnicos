<?php

declare(strict_types=1);

/*
 * Migracion: tabla de notificaciones in-app por usuario.
 * Idempotente: crea la tabla solo si no existe.
 *
 * Uso: php database/upgrade_notificaciones.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$db->exec(
    "CREATE TABLE IF NOT EXISTS notificaciones (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        tipo VARCHAR(60) NOT NULL,
        titulo VARCHAR(160) NOT NULL,
        mensaje VARCHAR(255) NULL,
        url VARCHAR(255) NULL,
        leida TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notif_user (user_id, leida),
        INDEX idx_notif_fecha (created_at),
        CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

echo "Tabla notificaciones lista.\n";
