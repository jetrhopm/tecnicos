<?php

declare(strict_types=1);

/*
 * Agrega la configuracion real de garantia para instalaciones existentes.
 *
 * Uso: php database/upgrade_garantia_config.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$stmt = $db->prepare(
    "INSERT INTO configuraciones (clave, valor, tipo, grupo)
     VALUES (:clave, :valor, :tipo, :grupo)
     ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), grupo = VALUES(grupo)"
);

$stmt->execute([
    'clave' => 'garantia.dias_default',
    'valor' => '30',
    'tipo' => 'number',
    'grupo' => 'garantia',
]);

echo "Configuracion agregada: garantia.dias_default\n";
