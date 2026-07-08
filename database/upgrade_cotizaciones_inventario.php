<?php

declare(strict_types=1);

/*
 * Une cotizaciones con inventario:
 * - cotizacion_items puede apuntar a una refaccion y guardar costo snapshot.
 * - refacciones_ordenes puede recordar de que item cotizado salio.
 *
 * Uso: php database/upgrade_cotizaciones_inventario.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$schema = (string) env_value('DB_DATABASE', 'servicio_tecnico_db');

$columnExists = static function (string $table, string $column) use ($db, $schema): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND COLUMN_NAME = :column'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
};

$indexExists = static function (string $table, string $index) use ($db, $schema): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = :table AND INDEX_NAME = :index_name'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'index_name' => $index]);
    return (int) $stmt->fetchColumn() > 0;
};

$constraintExists = static function (string $table, string $constraint) use ($db, $schema): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = :schema AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint_name'
    );
    $stmt->execute(['schema' => $schema, 'table' => $table, 'constraint_name' => $constraint]);
    return (int) $stmt->fetchColumn() > 0;
};

if (!$columnExists('cotizacion_items', 'refaccion_id')) {
    $db->exec('ALTER TABLE cotizacion_items ADD refaccion_id INT UNSIGNED NULL AFTER tipo');
}
if (!$columnExists('cotizacion_items', 'costo_unitario')) {
    $db->exec('ALTER TABLE cotizacion_items ADD costo_unitario DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cantidad');
}
if (!$indexExists('cotizacion_items', 'idx_cot_item_refaccion')) {
    $db->exec('ALTER TABLE cotizacion_items ADD INDEX idx_cot_item_refaccion (refaccion_id)');
}
if (!$constraintExists('cotizacion_items', 'fk_cot_item_refaccion')) {
    $db->exec('ALTER TABLE cotizacion_items ADD CONSTRAINT fk_cot_item_refaccion FOREIGN KEY (refaccion_id) REFERENCES refacciones(id) ON DELETE SET NULL');
}

if (!$columnExists('refacciones_ordenes', 'cotizacion_item_id')) {
    $db->exec('ALTER TABLE refacciones_ordenes ADD cotizacion_item_id INT UNSIGNED NULL AFTER refaccion_id');
}
if (!$indexExists('refacciones_ordenes', 'uq_ro_cot_item')) {
    $db->exec('ALTER TABLE refacciones_ordenes ADD UNIQUE KEY uq_ro_cot_item (cotizacion_item_id)');
}
if (!$constraintExists('refacciones_ordenes', 'fk_ro_cot_item')) {
    $db->exec('ALTER TABLE refacciones_ordenes ADD CONSTRAINT fk_ro_cot_item FOREIGN KEY (cotizacion_item_id) REFERENCES cotizacion_items(id) ON DELETE SET NULL');
}

echo "Cotizaciones e inventario enlazados.\n";
