<?php

declare(strict_types=1);

namespace App\DTO;

final class PagoDTO
{
    public function __construct(public readonly int $ordenId, public readonly float $monto)
    {
    }
}
