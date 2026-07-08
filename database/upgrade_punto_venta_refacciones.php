<?php

declare(strict_types=1);

/*
 * Agrega punto de venta para refacciones de mostrador.
 *
 * Uso: php database/upgrade_punto_venta_refacciones.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$schema = (string) env_value('DB_DATABASE', 'servicio_tecnico_db');

$tableExists = static function (string $table) use ($db, $schema): bool {
    $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table');
    $stmt->execute(['schema' => $schema, 'table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
};

$columnExists = static function (string $table, string $column) use ($db, $schema): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$constraintExists = static function (string $table, string $constraint) use ($db, $schema): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = :schema AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint_name'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'constraint_name' => $constraint]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$tableExists('ventas_refacciones')) {
    $db->exec(
        "CREATE TABLE ventas_refacciones (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            folio VARCHAR(60) NOT NULL UNIQUE,
            cliente_nombre VARCHAR(190) NULL,
            cliente_telefono VARCHAR(40) NULL,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            descuento DECIMAL(12,2) NOT NULL DEFAULT 0,
            total DECIMAL(12,2) NOT NULL DEFAULT 0,
            metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
            referencia VARCHAR(160) NULL,
            notas TEXT NULL,
            usuario_id INT UNSIGNED NOT NULL,
            estado ENUM('activa','cancelada') NOT NULL DEFAULT 'activa',
            motivo_cancelacion TEXT NULL,
            cancelado_por INT UNSIGNED NULL,
            cancelado_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT fk_vr_user FOREIGN KEY (usuario_id) REFERENCES users(id),
            CONSTRAINT fk_vr_cancel_user FOREIGN KEY (cancelado_por) REFERENCES users(id) ON DELETE SET NULL,
            INDEX idx_vr_fecha (created_at),
            INDEX idx_vr_usuario (usuario_id),
            INDEX idx_vr_estado (estado)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$tableExists('venta_refaccion_items')) {
    $db->exec(
        "CREATE TABLE venta_refaccion_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            venta_id INT UNSIGNED NOT NULL,
            refaccion_id INT UNSIGNED NOT NULL,
            descripcion VARCHAR(255) NOT NULL,
            sku VARCHAR(100) NOT NULL,
            cantidad INT NOT NULL DEFAULT 1,
            costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            precio_unitario DECIMAL(12,2) NOT NULL DEFAULT 0,
            subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_vri_venta FOREIGN KEY (venta_id) REFERENCES ventas_refacciones(id) ON DELETE CASCADE,
            CONSTRAINT fk_vri_ref FOREIGN KEY (refaccion_id) REFERENCES refacciones(id),
            INDEX idx_vri_refaccion (refaccion_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!$columnExists('inventario_movimientos', 'venta_refaccion_id')) {
    $db->exec('ALTER TABLE inventario_movimientos ADD venta_refaccion_id INT UNSIGNED NULL AFTER orden_id');
}
if (!$constraintExists('inventario_movimientos', 'fk_mov_venta_ref')) {
    $db->exec('ALTER TABLE inventario_movimientos ADD CONSTRAINT fk_mov_venta_ref FOREIGN KEY (venta_refaccion_id) REFERENCES ventas_refacciones(id) ON DELETE SET NULL');
}

$db->exec(
    "INSERT INTO permissions (module, action, label)
     SELECT 'punto_venta', a.action, CONCAT(a.action, ' punto_venta')
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
     JOIN permissions p ON p.module = 'punto_venta'
     WHERE r.name = 'superadmin'
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'punto_venta'
     WHERE r.name = 'admin' AND p.action <> 'eliminar'
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'punto_venta'
     WHERE r.name IN ('caja','recepcion') AND p.action IN ('ver','crear','imprimir')
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

echo "Punto de venta de refacciones actualizado.\n";
