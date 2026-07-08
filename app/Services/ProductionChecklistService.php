<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class ProductionChecklistService
{
    public function items(): array
    {
        return [
            $this->itemDebug(),
            $this->itemEntorno(),
            $this->itemUrl(),
            $this->itemHttps(),
            $this->itemDemoUsers(),
            $this->itemWritable('storage', BASE_PATH . '/storage'),
            $this->itemWritable('storage/uploads', BASE_PATH . '/storage/uploads'),
            $this->itemWritable('storage/logs', BASE_PATH . '/storage/logs'),
            $this->itemHtaccess('Proteccion raiz', BASE_PATH . '/.htaccess'),
            $this->itemHtaccess('Proteccion storage', BASE_PATH . '/storage/.htaccess'),
            $this->itemBackups(),
        ];
    }

    private function itemDebug(): array
    {
        $debug = filter_var(env_value('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
        return [
            'estado' => $debug ? 'danger' : 'ok',
            'titulo' => 'APP_DEBUG',
            'detalle' => $debug ? 'Desactivalo en hosting para no exponer errores internos.' : 'No se muestran trazas sensibles al usuario.',
        ];
    }

    private function itemEntorno(): array
    {
        $env = (string) env_value('APP_ENV', 'production');
        return [
            'estado' => $env === 'production' ? 'ok' : 'warning',
            'titulo' => 'APP_ENV',
            'detalle' => $env === 'production' ? 'Configurado como produccion.' : "Actualmente esta en '{$env}'. En hosting usa production.",
        ];
    }

    private function itemUrl(): array
    {
        $url = (string) env_value('APP_URL', 'auto');
        $esLocal = preg_match('/localhost|127\.0\.0\.1|192\.168\./i', $url) === 1;
        return [
            'estado' => ($url === 'auto' || $esLocal) ? 'warning' : 'ok',
            'titulo' => 'APP_URL',
            'detalle' => ($url === 'auto' || $esLocal)
                ? 'Para produccion conviene usar el dominio HTTPS definitivo.'
                : 'Tiene una URL fija para ligas, QR y PDF.',
        ];
    }

    private function itemHttps(): array
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
        return [
            'estado' => $https ? 'ok' : 'warning',
            'titulo' => 'HTTPS',
            'detalle' => $https ? 'La peticion actual usa HTTPS.' : 'En produccion activa SSL para proteger sesiones y enlaces.',
        ];
    }

    private function itemDemoUsers(): array
    {
        $emails = [
            'admin@local.test',
            'superadmin@local.test',
            'administrador@local.test',
            'recepcion@local.test',
            'tecnico@local.test',
            'tecnico_senior@local.test',
            'almacen@local.test',
            'caja@local.test',
            'cliente_consulta@local.test',
        ];

        $placeholders = implode(',', array_fill(0, count($emails), '?'));
        try {
            $stmt = Database::connection()->prepare("SELECT COUNT(*) total FROM users WHERE status = 'activo' AND email IN ({$placeholders})");
            $stmt->execute($emails);
            $total = (int) ($stmt->fetch()['total'] ?? 0);
        } catch (\Throwable) {
            $total = 0;
        }

        return [
            'estado' => $total > 0 ? 'danger' : 'ok',
            'titulo' => 'Usuarios demo activos',
            'detalle' => $total > 0 ? "Hay {$total} cuenta(s) demo activas. Cambia contrasenas o bloquealas antes de produccion." : 'No se detectaron cuentas demo activas.',
        ];
    }

    private function itemWritable(string $titulo, string $path): array
    {
        $ok = is_dir($path) && is_writable($path);
        return [
            'estado' => $ok ? 'ok' : 'danger',
            'titulo' => $titulo,
            'detalle' => $ok ? 'Carpeta disponible para escritura.' : 'Debe existir y tener permisos de escritura.',
        ];
    }

    private function itemHtaccess(string $titulo, string $path): array
    {
        $ok = is_file($path);
        return [
            'estado' => $ok ? 'ok' : 'danger',
            'titulo' => $titulo,
            'detalle' => $ok ? 'Archivo .htaccess presente.' : 'Falta .htaccess de proteccion para Apache.',
        ];
    }

    private function itemBackups(): array
    {
        $dir = BASE_PATH . '/storage/backups';
        $files = array_merge(
            glob($dir . '/*.sql') ?: [],
            glob($dir . '/*.sql.gz') ?: [],
            glob($dir . '/*.zip') ?: []
        );
        $reciente = false;
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) >= strtotime('-7 days')) {
                $reciente = true;
                break;
            }
        }

        return [
            'estado' => $reciente ? 'ok' : 'warning',
            'titulo' => 'Respaldos',
            'detalle' => $reciente ? 'Existe al menos un respaldo reciente.' : 'No se detecto respaldo SQL reciente en storage/backups.',
        ];
    }
}
