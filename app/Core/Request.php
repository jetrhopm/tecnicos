<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /*
     * Entrada principal de datos HTTP.
     * Lee metodo, URL, query string, formularios POST, JSON y archivos.
     * Importante: esta clase NO confia en los datos; solo los agrupa.
     * La validacion, permisos y saneamiento se hacen en controllers/services.
     */
    public function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // El override _method solo aplica desde un POST real y solo hacia
        // verbos de modificacion; asi no se puede degradar a GET para
        // esquivar la validacion CSRF.
        $override = strtoupper((string) ($_POST['_method'] ?? ''));
        if ($method === 'POST' && in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $override;
        }

        return in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], true) ? $method : 'GET';
    }

    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $app = require BASE_PATH . '/config/app.php';
        $configuredUrl = (string) ($app['url'] ?? 'auto');
        $baseForPath = strtolower(trim($configuredUrl)) === 'auto' ? request_base_url() : $configuredUrl;
        $basePath = parse_url($baseForPath, PHP_URL_PATH) ?: '';
        $basePath = '/' . trim($basePath, '/');
        if ($basePath !== '/' && ($path === $basePath || str_starts_with($path, $basePath . '/'))) {
            $path = substr($path, strlen($basePath));
        }
        if ($path === '/public') {
            $path = '/';
        }
        if (str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public'));
        }

        $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        if ($scriptDir !== '/' && str_starts_with($path, $scriptDir)) {
            $path = substr($path, strlen($scriptDir));
        }
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function all(): array
    {
        $json = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $json = json_decode(file_get_contents('php://input') ?: '{}', true) ?: [];
        }

        return array_merge($_GET, $_POST, $json);
    }

    public function only(array $keys): array
    {
        $data = $this->all();
        return array_intersect_key($data, array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? 'cli';
    }

    public function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'cli';
    }
}
