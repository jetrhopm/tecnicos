<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $path): never
    {
        header('Location: ' . url($path));
        exit;
    }

    public static function back(): never
    {
        // Solo se regresa a paginas del propio sistema; un Referer externo
        // o manipulado no debe convertirse en redireccion (open redirect).
        $target = url('/');
        $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
        if ($referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $ownHost = parse_url(request_base_url(), PHP_URL_HOST);
            if ($refererHost !== null && $refererHost === $ownHost) {
                $target = $referer;
            }
        }

        header('Location: ' . $target);
        exit;
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }
}
