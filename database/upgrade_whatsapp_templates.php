<?php

declare(strict_types=1);

/*
 * Migracion: mejora las plantillas de WhatsApp de cotizacion y equipo listo.
 * Solo actualiza si el valor sigue siendo el texto original (no pisa textos
 * que el usuario ya haya personalizado en Configuracion).
 *
 * Uso: php database/upgrade_whatsapp_templates.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$cambios = [
    'whatsapp.diagnostico_listo' => [
        'viejo' => 'Hola {cliente}, tu diagnostico de la orden {folio} esta listo. Requiere autorizacion. Consulta aqui: {link}',
        'nuevo' => 'Hola {cliente}, tenemos lista la cotizacion de tu orden {folio} ({equipo}). Necesitamos tu validacion para continuar con la reparacion. Revisala y autorizala aqui: {link}',
    ],
    'whatsapp.equipo_listo' => [
        'viejo' => 'Hola {cliente}, tu equipo de la orden {folio} ya esta listo para entrega. Saldo pendiente: {saldo}. Gracias.',
        'nuevo' => 'Hola {cliente}, tu {equipo} de la orden {folio} ya esta listo para entrega. Saldo por pagar: {saldo}. Puedes pasar a recogerlo. Gracias.',
    ],
];

// Plantillas nuevas (se agregan si faltan; no se pisan si ya existen).
$nuevas = [
    'whatsapp.no_reparable' => 'Hola {cliente}, lamentablemente tu {equipo} de la orden {folio} no pudo ser reparado. Puedes pasar a recogerlo cuando gustes y con gusto te explicamos el diagnostico. Cualquier duda estamos para ayudarte.',
    'whatsapp.demora' => 'Hola {cliente}, te avisamos que tu {equipo} de la orden {folio} esta tomando mas tiempo del estimado (por ejemplo, en espera de una refaccion). Te mantendremos al tanto en cuanto tengamos novedades. Gracias por tu paciencia.',
    'whatsapp.entregado' => 'Hola {cliente}, gracias por tu preferencia. Tu {equipo} de la orden {folio} fue entregado. Conserva tu comprobante para hacer valida la garantia. Estamos para servirte, que tengas excelente dia.',
];

$sel = $db->prepare('SELECT valor FROM configuraciones WHERE clave = :clave LIMIT 1');
$upd = $db->prepare('UPDATE configuraciones SET valor = :valor WHERE clave = :clave');
$ins = $db->prepare('INSERT INTO configuraciones (clave, valor, tipo, grupo) VALUES (:clave, :valor, :tipo, :grupo)');

$actualizadas = 0;
foreach ($cambios as $clave => $textos) {
    $sel->execute(['clave' => $clave]);
    $actual = $sel->fetchColumn();

    if ($actual === false) {
        echo "= no existe: {$clave}\n";
        continue;
    }
    if ((string) $actual !== $textos['viejo']) {
        echo "= personalizado, se respeta: {$clave}\n";
        continue;
    }

    $upd->execute(['valor' => $textos['nuevo'], 'clave' => $clave]);
    echo "+ actualizado: {$clave}\n";
    $actualizadas++;
}

$agregadas = 0;
foreach ($nuevas as $clave => $valor) {
    $sel->execute(['clave' => $clave]);
    if ($sel->fetchColumn() !== false) {
        echo "= ya existe: {$clave}\n";
        continue;
    }
    $ins->execute(['clave' => $clave, 'valor' => $valor, 'tipo' => 'text', 'grupo' => 'plantillas']);
    echo "+ agregada: {$clave}\n";
    $agregadas++;
}

echo "Listo. {$actualizadas} plantilla(s) actualizada(s), {$agregadas} agregada(s).\n";
