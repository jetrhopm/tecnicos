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
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? url('/')));
        exit;
    }

    public static function status(int $code): void
    {
        http_response_code($code);
    }
}
