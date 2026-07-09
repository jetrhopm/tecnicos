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
    /*
     * Enlace interno relativo a la raiz, por ejemplo "/tecnico/clientes".
     * No incluye esquema ni host: la pagina hereda los del navegador, asi
     * evitamos contenido mixto cuando se entra por https y problemas al
     * abrir el sistema desde otra IP o dominio (localhost, LAN, etc.).
     * Para enlaces que salen del sistema usa absolute_url().
     */
    $prefix = rtrim(base_path_prefix(), '/');
    return $prefix . '/' . ltrim($path, '/');
}

function base_path_prefix(): string
{
    // Solo la parte de ruta del sitio (p. ej. "/tecnico" o "" si va en la raiz).
    $app = require BASE_PATH . '/config/app.php';
    $configured = rtrim((string) ($app['url'] ?? 'auto'), '/');
    if ($configured !== '' && strtolower($configured) !== 'auto') {
        return parse_url($configured, PHP_URL_PATH) ?: '';
    }

    return parse_url(request_base_url(), PHP_URL_PATH) ?: '';
}

function absolute_url(string $path = ''): string
{
    /*
     * URL absoluta con esquema y host. Solo para enlaces que se comparten
     * fuera del navegador actual: mensaje de WhatsApp, portal publico, QR.
     */
    $app = require BASE_PATH . '/config/app.php';
    $configured = rtrim((string) ($app['url'] ?? 'auto'), '/');
    $base = ($configured === '' || strtolower($configured) === 'auto') ? request_base_url() : $configured;

    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function app_url(string $path = ''): string
{
    // Alias historico: devuelve URL absoluta (equivale a absolute_url()).
    return absolute_url($path);
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
    $path = $request->path();
    if ($prefix === '/') {
        return $path === '/' ? 'active' : '';
    }

    return str_starts_with($path, $prefix) ? 'active' : '';
}
