<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        /*
         * Salida HTML del sistema.
         * Fuente: controllers envian datos ya autorizados/filtrados.
         * Destino: vista + layout. Las vistas deben usar e() al pintar datos
         * de usuario para reducir XSS.
         */
        extract($data, EXTR_SKIP);
        $viewFile = BASE_PATH . '/resources/views/' . $view . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException("Vista no encontrada: {$view}");
        }

        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        if ($layout === '') {
            echo $content;
            return;
        }

        require BASE_PATH . '/resources/views/' . $layout . '.php';
    }
}
