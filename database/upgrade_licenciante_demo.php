<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$db->exec("
    INSERT INTO roles (name, label)
    VALUES ('licenciante', 'Administrador de licencias')
    ON DUPLICATE KEY UPDATE label = VALUES(label)
");

$db->exec("
    INSERT INTO permissions (module, action, label)
    SELECT m.module, a.action, CONCAT(a.action, ' ', m.module)
    FROM (SELECT 'licencias' module UNION SELECT 'usuarios' UNION SELECT 'dashboard') m
    CROSS JOIN (
        SELECT 'ver' action UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'eliminar'
        UNION SELECT 'administrar'
    ) a
    WHERE 1 = 1
    ON DUPLICATE KEY UPDATE label = VALUES(label)
");

$db->exec("
    INSERT INTO role_permissions (role_id, permission_id)
    SELECT r.id, p.id
    FROM roles r
    JOIN permissions p ON (
        (p.module = 'dashboard' AND p.action = 'ver')
        OR (p.module = 'usuarios' AND p.action IN ('ver','crear','editar','eliminar','administrar'))
        OR (p.module = 'licencias' AND p.action IN ('ver','administrar'))
    )
    WHERE r.name = 'licenciante'
    ON DUPLICATE KEY UPDATE role_id = role_id
");

$db->exec("
    DELETE rp
    FROM role_permissions rp
    JOIN roles r ON r.id = rp.role_id
    JOIN permissions p ON p.id = rp.permission_id
    WHERE r.name = 'superadmin' AND p.module = 'licencias'
");

$userStmt = $db->prepare("
    INSERT INTO users (name, email, password, status)
    VALUES (:name, :email, :password, 'activo')
    ON DUPLICATE KEY UPDATE name = VALUES(name), password = VALUES(password), status = 'activo', deleted_at = NULL
");
$userStmt->execute([
    'name' => 'Administrador de Licencias',
    'email' => 'admin@gocentersuplementos.com.mx',
    'password' => '$2y$10$CNak65snzPhbj0O4AceIBeTwjAetn/jczciOaRWo8DX8EbeLT5GDW',
]);

$db->exec("
    INSERT INTO user_roles (user_id, role_id)
    SELECT u.id, r.id
    FROM users u
    JOIN roles r ON r.name = 'licenciante'
    WHERE u.email = 'admin@gocentersuplementos.com.mx'
    ON DUPLICATE KEY UPDATE user_id = user_id
");

$stmt = $db->prepare("
    INSERT INTO configuraciones (clave, valor, tipo, grupo)
    VALUES (:clave, :valor, :tipo, 'licencia')
    ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), grupo = VALUES(grupo)
");

$stmt->execute(['clave' => 'demo.activo', 'valor' => '1', 'tipo' => 'bool']);
$stmt->execute(['clave' => 'demo.dias', 'valor' => '14', 'tipo' => 'number']);
$stmt->execute([
    'clave' => 'demo.mensaje_expirado',
    'valor' => 'La demostración terminó. Si te interesó nuestro sistema y lo ves útil en tu día a día, contáctanos para activar la versión extendida.',
    'tipo' => 'text',
]);

$db->exec("
    INSERT INTO configuraciones (clave, valor, tipo, grupo)
    SELECT 'demo.inicio', CURDATE(), 'date', 'licencia'
    WHERE NOT EXISTS (SELECT 1 FROM configuraciones WHERE clave = 'demo.inicio')
");

echo "Rol licenciante y control demo instalados.\n";
