<?php

declare(strict_types=1);

namespace App\DTO;

final class OrdenDTO
{
    public function __construct(public readonly int $clienteId, public readonly int $equipoId, public readonly string $fallaReportada)
    {
    }
}
