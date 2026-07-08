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
    ['ticket.garantia', "1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n3. Para retirar el equipo es indispensable presentar la orden de servicio.\n4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n8. Las reparaciones o servicios de software no cuentan con garantía.\n9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n10. No se realizan reembolsos bajo ningún concepto.", 'text', 'ticket'],
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
