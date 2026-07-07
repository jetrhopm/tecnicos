<?php

declare(strict_types=1);

use App\Core\Session;

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, strlen($prefix)));
    $file = BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

foreach (glob(BASE_PATH . '/app/Helpers/*.php') ?: [] as $helper) {
    require_once $helper;
}

function env_value(string $key, mixed $default = null): mixed
{
    static $env = null;

    if ($env === null) {
        $env = [];
        $file = BASE_PATH . '/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$name, $value] = explode('=', $line, 2);
                $value = trim($value);
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                $env[trim($name)] = $value;
            }
        }
    }

    return $env[$key] ?? $default;
}

date_default_timezone_set((string) env_value('APP_TIMEZONE', 'America/Mexico_City'));
Session::start();
