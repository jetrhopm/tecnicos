<?php

declare(strict_types=1);

return [
    'host' => env_value('DB_HOST', 'localhost'),
    'port' => (int) env_value('DB_PORT', 3306),
    'database' => env_value('DB_DATABASE', 'servicio_tecnico_db'),
    'username' => env_value('DB_USERNAME', 'root'),
    'password' => env_value('DB_PASSWORD', ''),
    'charset' => env_value('DB_CHARSET', 'utf8mb4'),
];
