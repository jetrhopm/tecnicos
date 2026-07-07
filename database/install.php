<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$config = require BASE_PATH . '/config/database.php';
$dsn = sprintf('mysql:host=%s;port=%d;charset=%s', $config['host'], $config['port'], $config['charset']);
$pdo = new PDO($dsn, $config['username'], $config['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));
$pdo->exec(file_get_contents(__DIR__ . '/seed.sql'));

echo 'Base de datos instalada correctamente.' . PHP_EOL;
