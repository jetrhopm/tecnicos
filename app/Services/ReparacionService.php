<?php

declare(strict_types=1);

namespace App\Services;

final class ReparacionService
{
    public function registrarAvance(int $ordenId, string $descripcion): void
    {
        (new AuditoriaService())->registrar('avance', 'reparaciones', $ordenId, null, ['descripcion' => $descripcion]);
    }
}
