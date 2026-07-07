<?php

declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_field(): string
{
    return \App\Core\Csrf::field();
}

function old(string $key, mixed $default = ''): mixed
{
    return \App\Core\Session::get('_old', [])[$key] ?? $default;
}

function url(string $path = ''): string
{
    return app_url($path);
}

function app_url(string $path = ''): string
{
    $app = require BASE_PATH . '/config/app.php';
    $configured = rtrim((string) ($app['url'] ?? 'auto'), '/');
    $base = ($configured === '' || strtolower($configured) === 'auto') ? request_base_url() : $configured;

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function request_base_url(): string
{
    // Los headers X-Forwarded-* los puede mandar cualquier cliente; solo se
    // aceptan cuando la app declara estar detras de un proxy (APP_TRUST_PROXY=true).
    $trustProxy = filter_var(env_value('APP_TRUST_PROXY', false), FILTER_VALIDATE_BOOL);
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($trustProxy && ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
    if ($trustProxy && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = trim(explode(',', (string) $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $basePath = '';

    if (str_contains($scriptName, '/public/')) {
        $basePath = substr($scriptName, 0, strpos($scriptName, '/public/'));
    } elseif (str_ends_with($scriptName, '/public/index.php')) {
        $basePath = substr($scriptName, 0, -strlen('/public/index.php'));
    } else {
        $dir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        $basePath = str_ends_with($dir, '/public') ? substr($dir, 0, -strlen('/public')) : $dir;
    }

    $basePath = $basePath === '/' ? '' : $basePath;
    return $scheme . '://' . $host . $basePath;
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function is_active(string $prefix): string
{
    $request = new \App\Core\Request();
    return str_starts_with($request->path(), $prefix) ? 'active' : '';
}
