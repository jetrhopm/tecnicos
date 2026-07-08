<?php

declare(strict_types=1);

/*
 * Migracion: da acceso al modulo de almacen (inventario y proveedores) a los
 * roles almacen, tecnico, tecnico_senior y recepcion, ademas de los admins que
 * ya lo tienen. Idempotente.
 *
 * Uso: php database/upgrade_inventario_roles.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$afectadas = $db->exec(
    "INSERT INTO role_permissions (role_id, permission_id)
     SELECT r.id, p.id
     FROM roles r
     CROSS JOIN permissions p
     WHERE r.name IN ('almacen','tecnico','tecnico_senior','recepcion')
       AND p.module IN ('inventario','proveedores')
       AND p.action IN ('ver','crear','editar')
     ON DUPLICATE KEY UPDATE role_id = role_id"
);

echo "Permisos de almacen otorgados. Filas afectadas: {$afectadas}.\n";
