<?php

declare(strict_types=1);

return [
    'name' => env_value('APP_NAME', 'Servicio Tecnico'),
    'env' => env_value('APP_ENV', 'production'),
    'debug' => filter_var(env_value('APP_DEBUG', false), FILTER_VALIDATE_BOOL),
    'url' => env_value('APP_URL', 'auto'),
    'timezone' => env_value('APP_TIMEZONE', 'America/Mexico_City'),
    'upload_max_mb' => (int) env_value('UPLOAD_MAX_MB', 8),
];
