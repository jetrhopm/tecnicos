<?php

declare(strict_types=1);

/*
 * Agrega modulo operativo de caja/corte.
 * Uso: php database/upgrade_caja_corte.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db = \App\Core\Database::connection();
$schema = (string) env_value('DB_DATABASE', 'servicio_tecnico_db');

$tableExists = static function (string $table) use ($db, $schema): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table');
    $stmt->execute(['schema' => $schema, 'table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$tableExists('caja_turnos')) {
    $db->exec(
        "CREATE TABLE caja_turnos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(60) NOT NULL UNIQUE,
            estado ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
            fondo_inicial DECIMAL(12,2) NOT NULL DEFAULT 0,
            efectivo_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
            transferencia_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
            tarjeta_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
            otro_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_esperado DECIMAL(12,2) NOT NULL DEFAULT 0,
            total_contado DECIMAL(12,2) NOT NULL DEFAULT 0,
            diferencia DECIMAL(12,2) NOT NULL DEFAULT 0,
            abierto_por INT UNSIGNED NOT NULL,
            cerrado_por INT UNSIGNED NULL,
            opened_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            closed_at DATETIME NULL,
            observaciones TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_caja_turno_abre FOREIGN KEY (abierto_por) REFERENCES users(id),
            CONSTRAINT fk_caja_turno_cierra FOREIGN KEY (cerrado_por) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_caja_turno_estado (estado),
            INDEX idx_caja_turno_opened (opened_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$tableExists('caja_movimientos')) {
    $db->exec(
        "CREATE TABLE caja_movimientos (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            turno_id INT UNSIGNED NOT NULL,
            tipo ENUM('retiro') NOT NULL,
            monto DECIMAL(12,2) NOT NULL,
            metodo ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
            concepto VARCHAR(255) NOT NULL,
            usuario_id INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_caja_mov_turno FOREIGN KEY (turno_id) REFERENCES caja_turnos(id) ON DELETE CASCADE,
            CONSTRAINT fk_caja_mov_user FOREIGN KEY (usuario_id) REFERENCES users(id),
            INDEX idx_caja_mov_turno (turno_id),
            INDEX idx_caja_mov_fecha (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

$db->exec(
    "INSERT INTO permissions (module, action, label)
     SELECT 'caja', a.action, CONCAT(a.action, ' caja')
     FROM (
        SELECT 'ver' action UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'eliminar'
        UNION SELECT 'autorizar' UNION SELECT 'cambiar_estado' UNION SELECT 'exportar'
        UNION SELECT 'imprimir' UNION SELECT 'administrar'
     ) a
     ON DUPLICATE KEY UPDATE label = VALUES(label)"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'caja'
     WHERE r.name = 'superadmin'
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'caja'
     WHERE r.name = 'admin' AND p.action <> 'eliminar'
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'caja'
     WHERE r.name = 'caja' AND p.action IN ('ver','editar','imprimir')
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

$db->exec(
    "DELETE rp
     FROM role_permissions rp
     JOIN roles r ON r.id = rp.role_id
     JOIN permissions p ON p.id = rp.permission_id
     WHERE r.name = 'caja' AND p.module = 'reportes'"
);

echo "Modulo caja/corte actualizado.\n";
