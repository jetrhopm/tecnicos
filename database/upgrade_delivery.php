<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();

$columns = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll();
$columnNames = array_column($columns, 'Field');

if (!in_array('codigo_entrega', $columnNames, true)) {
    $db->exec("ALTER TABLE ordenes_servicio ADD codigo_entrega VARCHAR(80) NULL UNIQUE AFTER observaciones_cliente");
}

if (!in_array('ubicacion_actual', $columnNames, true)) {
    $db->exec("ALTER TABLE ordenes_servicio ADD ubicacion_actual VARCHAR(120) NOT NULL DEFAULT 'Recepcion' AFTER codigo_entrega");
}

$db->exec("
    CREATE TABLE IF NOT EXISTS entregas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        orden_id INT UNSIGNED NOT NULL,
        codigo_entrega VARCHAR(80) NOT NULL,
        usuario_id INT UNSIGNED NOT NULL,
        recibido_por_nombre VARCHAR(190) NOT NULL,
        recibido_por_identificacion VARCHAR(120) NULL,
        saldo_antes DECIMAL(12,2) NOT NULL DEFAULT 0,
        pago_final DECIMAL(12,2) NOT NULL DEFAULT 0,
        metodo_pago ENUM('efectivo','transferencia','tarjeta','otro') NULL,
        referencia_pago VARCHAR(160) NULL,
        saldo_despues DECIMAL(12,2) NOT NULL DEFAULT 0,
        garantia_id INT UNSIGNED NULL,
        observaciones TEXT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_entrega_codigo (codigo_entrega),
        INDEX idx_entrega_fecha (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

(new App\Repositories\OrdenRepository())->ensureDeliveryCodes();

echo 'Upgrade de entregas aplicado correctamente.' . PHP_EOL;
