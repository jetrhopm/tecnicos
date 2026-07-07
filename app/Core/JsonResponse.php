<?php

declare(strict_types=1);

namespace App\Core;

final class JsonResponse
{
    /*
     * Salida estandar para endpoints JSON.
     * Destino: navegador, JS interno o futura app movil.
     * No imprime arrays sueltos; todas las respuestas tienen success/message/data/errors.
     */
    public static function success(string $message = 'Operacion realizada correctamente', mixed $data = [], int $status = 200): never
    {
        self::send(true, $message, $data, [], $status);
    }

    public static function error(string $message = 'No se pudo completar la operacion', mixed $errors = [], int $status = 422): never
    {
        self::send(false, $message, null, $errors, $status);
    }

    private static function send(bool $success, string $message, mixed $data, mixed $errors, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data,
            'errors' => $errors,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}
