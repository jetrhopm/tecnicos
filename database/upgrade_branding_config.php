<?php

declare(strict_types=1);

/*
 * Agrega configuracion editable de marca: nombre del sistema y logo del taller.
 *
 * Uso: php database/upgrade_branding_config.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$stmt = $db->prepare(
    "INSERT INTO configuraciones (clave, valor, tipo, grupo)
     VALUES (:clave, :valor, :tipo, :grupo)
     ON DUPLICATE KEY UPDATE tipo = VALUES(tipo), grupo = VALUES(grupo)"
);

$configuraciones = [
    ['sistema.nombre', 'Sistema Web de Gestión de Servicios Técnicos', 'string', 'sistema'],
    ['negocio.logo_url', '', 'string', 'negocio'],
];

foreach ($configuraciones as [$clave, $valor, $tipo, $grupo]) {
    $stmt->execute([
        'clave' => $clave,
        'valor' => $valor,
        'tipo' => $tipo,
        'grupo' => $grupo,
    ]);
    echo "Lista: {$clave}\n";
}

echo "Configuracion de marca lista.\n";
