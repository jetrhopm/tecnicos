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

$appDebug = filter_var(env_value('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');

set_exception_handler(function (\Throwable $exception) use ($appDebug): void {
    /*
     * Ultima barrera ante excepciones no controladas.
     * Registra el detalle en storage/logs y responde sin exponer rutas,
     * SQL ni trazas al navegador salvo que APP_DEBUG=true.
     */
    \App\Core\Logger::error('Excepcion no controlada', [
        'error' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
    ]);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        exit(1);
    }

    // Las RuntimeException de los servicios traen mensajes de negocio aptos
    // para el usuario; el resto (PDO, errores de codigo) se responde generico.
    $esErrorDeNegocio = $exception instanceof \RuntimeException;

    if (!headers_sent()) {
        http_response_code($esErrorDeNegocio ? 422 : 500);
    }

    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if (str_contains($path, '/api/')) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => ($esErrorDeNegocio || $appDebug) ? $exception->getMessage() : 'Error interno del servidor.',
            'data' => null,
            'errors' => [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $detalle = $appDebug
        ? '<pre style="text-align:left;white-space:pre-wrap;">' . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8') . '</pre>'
        : '<p>Intenta de nuevo. Si el problema continua, contacta al administrador.</p>';

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Error interno</title></head>'
        . '<body style="font-family:sans-serif;text-align:center;padding:3rem;">'
        . '<h1>Ocurrio un error interno</h1>' . $detalle . '</body></html>';
    exit;
});

Session::start();
