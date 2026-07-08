<?php

declare(strict_types=1);

/*
 * Da acceso al modulo Agenda a roles operativos existentes.
 *
 * Uso: php database/upgrade_agenda_roles.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$db->exec(
    "INSERT INTO permissions (module, action, label)
     SELECT 'agenda', a.action, CONCAT(a.action, ' agenda')
     FROM (
        SELECT 'ver' action UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'imprimir'
     ) a
     ON DUPLICATE KEY UPDATE label = VALUES(label)"
);

$db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'agenda'
     WHERE r.name IN ('admin','recepcion','tecnico','tecnico_senior','almacen','caja')
       AND p.action IN ('ver','crear','editar','imprimir')
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

echo "Permisos de agenda actualizados.\n";
