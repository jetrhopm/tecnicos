<?php

declare(strict_types=1);

/*
 * Migracion: claves de configuracion para los tickets/documentos de orden.
 * Agrega negocio.logo_url y ticket.garantia si no existen. No pisa valores
 * ya configurados por el usuario.
 *
 * Uso: php database/upgrade_ticket_config.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$claves = [
    ['negocio.logo_url', '', 'string', 'negocio'],
    ['ticket.garantia', 'Garantia del ticket: 30 dias por el trabajo realizado. No cubre golpes, humedad, manipulacion de terceros ni fallas ajenas al servicio.', 'text', 'ticket'],
];

$insert = $db->prepare(
    'INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (:clave, :valor, :tipo, :grupo)'
);
$existe = $db->prepare('SELECT 1 FROM configuraciones WHERE clave = :clave LIMIT 1');

$agregadas = 0;
foreach ($claves as [$clave, $valor, $tipo, $grupo]) {
    $existe->execute(['clave' => $clave]);
    if ($existe->fetchColumn()) {
        echo "= ya existe: {$clave}\n";
        continue;
    }
    $insert->execute(['clave' => $clave, 'valor' => $valor, 'tipo' => $tipo, 'grupo' => $grupo]);
    echo "+ agregada: {$clave}\n";
    $agregadas++;
}

echo "Listo. {$agregadas} clave(s) nueva(s).\n";
