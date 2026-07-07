<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();
$admin = $db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@local.test'")->fetchColumn();
$hash = $db->query("SELECT password FROM users WHERE email = 'admin@local.test'")->fetchColumn();
$tables = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'servicio_tecnico_db'")->fetchColumn();

echo "ADMIN={$admin}" . PHP_EOL;
echo "TABLES={$tables}" . PHP_EOL;
echo 'PASSWORD_OK=' . (password_verify('password', (string) $hash) ? '1' : '0') . PHP_EOL;
