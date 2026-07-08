<?php

declare(strict_types=1);

/*
 * Agrega control de estado/cancelacion a refacciones aplicadas en ordenes.
 *
 * Uso: php database/upgrade_refacciones_ordenes_estado.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$database = (string) (require BASE_PATH . '/config/database.php')['database'];

$columnExists = static function (string $column) use ($db, $database): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'refacciones_ordenes' AND COLUMN_NAME = :column"
    );
    $stmt->execute(['db' => $database, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$indexExists = static function (string $index) use ($db, $database): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'refacciones_ordenes' AND INDEX_NAME = :index"
    );
    $stmt->execute(['db' => $database, 'index' => $index]);
    return (int) $stmt->fetchColumn() > 0;
};

$fkExists = static function (string $constraint) use ($db, $database): bool {
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
         WHERE CONSTRAINT_SCHEMA = :db AND TABLE_NAME = 'refacciones_ordenes' AND CONSTRAINT_NAME = :constraint"
    );
    $stmt->execute(['db' => $database, 'constraint' => $constraint]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$columnExists('estado')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD COLUMN estado ENUM('activa','cancelada') NOT NULL DEFAULT 'activa' AFTER precio_unitario");
    echo "Columna agregada: estado\n";
}
if (!$columnExists('motivo_cancelacion')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD COLUMN motivo_cancelacion TEXT NULL AFTER estado");
    echo "Columna agregada: motivo_cancelacion\n";
}
if (!$columnExists('cancelado_por')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD COLUMN cancelado_por INT UNSIGNED NULL AFTER motivo_cancelacion");
    echo "Columna agregada: cancelado_por\n";
}
if (!$columnExists('cancelado_at')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD COLUMN cancelado_at DATETIME NULL AFTER cancelado_por");
    echo "Columna agregada: cancelado_at\n";
}
if (!$indexExists('idx_ro_estado')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD INDEX idx_ro_estado (estado)");
    echo "Indice agregado: idx_ro_estado\n";
}
if (!$fkExists('fk_ro_cancel_user')) {
    $db->exec("ALTER TABLE refacciones_ordenes ADD CONSTRAINT fk_ro_cancel_user FOREIGN KEY (cancelado_por) REFERENCES users(id) ON DELETE SET NULL");
    echo "Llave foranea agregada: fk_ro_cancel_user\n";
}

echo "Refacciones de orden actualizadas.\n";
