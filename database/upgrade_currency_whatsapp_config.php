<?php

declare(strict_types=1);

/*
 * Agrega configuracion editable de moneda, zona horaria y codigo de pais para WhatsApp.
 *
 * Uso: php database/upgrade_currency_whatsapp_config.php
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
    ['sistema.moneda', 'MXN', 'string', 'sistema'],
    ['sistema.zona_horaria', 'America/Mexico_City', 'string', 'sistema'],
    ['whatsapp.codigo_pais', '52', 'string', 'mensajes'],
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

echo "Configuracion de moneda, zona horaria y WhatsApp lista.\n";
