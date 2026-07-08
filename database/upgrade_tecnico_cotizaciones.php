<?php

declare(strict_types=1);

/*
 * Da permiso al rol tecnico para ver y crear cotizaciones.
 * No otorga autorizar: la autorizacion manual queda para tecnico_senior,
 * admin o superadmin segun permisos.
 *
 * Uso: php database/upgrade_tecnico_cotizaciones.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$db->exec(
    "INSERT INTO permissions (module, action, label)
     SELECT 'cotizaciones', a.action, CONCAT(a.action, ' cotizaciones')
     FROM (
        SELECT 'ver' action UNION SELECT 'crear'
     ) a
     ON DUPLICATE KEY UPDATE label = VALUES(label)"
);

$afectadas = $db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     JOIN permissions p ON p.module = 'cotizaciones'
     WHERE r.name = 'tecnico'
       AND p.action IN ('ver','crear')
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

echo "Permisos de cotizaciones para tecnico actualizados. Filas afectadas: {$afectadas}.\n";
