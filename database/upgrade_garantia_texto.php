<?php

declare(strict_types=1);

/*
 * Actualiza el texto legal de garantia para comprobantes y documentos.
 *
 * Uso: php database/upgrade_garantia_texto.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$textoGarantia = "1. El taller no se responsabiliza por pérdida o extravío de equipos que no sean retirados dentro de los 90 días naturales posteriores a la fecha de ingreso.\n"
    . "2. La garantía es de 30 días naturales a partir de la fecha de reparación y aplica únicamente sobre la falla o servicio realizado.\n"
    . "3. Para retirar el equipo es indispensable presentar la orden de servicio.\n"
    . "4. Al firmar la orden, el cliente acepta las condiciones físicas y de funcionamiento en las que se recibe el equipo.\n"
    . "5. La garantía será válida siempre que el sello de garantía permanezca intacto y el equipo no haya sido manipulado o revisado por terceros.\n"
    . "6. No cuentan con garantía los equipos mojados, golpeados, con pantalla dañada o con falla en flex.\n"
    . "7. No cuentan con garantía los equipos afectados por variaciones de voltaje.\n"
    . "8. Las reparaciones o servicios de software no cuentan con garantía.\n"
    . "9. Todo servicio o actualización de software se realiza bajo autorización y riesgo del cliente.\n"
    . "10. No se realizan reembolsos bajo ningún concepto.";

$db = Database::connection();
$stmt = $db->prepare(
    "INSERT INTO configuraciones (clave, valor, tipo, grupo)
     VALUES (:clave, :valor, :tipo, :grupo)
     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo)"
);

$configuraciones = [
    ['ordenes.garantia_default', '30 días naturales sobre la reparación realizada', 'string', 'ordenes'],
    ['ticket.garantia', $textoGarantia, 'text', 'ticket'],
    ['legal.politica_garantia', $textoGarantia, 'text', 'legal'],
];

foreach ($configuraciones as [$clave, $valor, $tipo, $grupo]) {
    $stmt->execute([
        'clave' => $clave,
        'valor' => $valor,
        'tipo' => $tipo,
        'grupo' => $grupo,
    ]);
    echo "Actualizada: {$clave}\n";
}

echo "Texto de garantia actualizado.\n";
