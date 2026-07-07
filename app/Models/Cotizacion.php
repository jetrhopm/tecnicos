<?php

declare(strict_types=1);

namespace App\Models;

final class Cotizacion
{
    public function __construct(public readonly int $id, public readonly int $ordenId, public readonly float $total)
    {
    }
}
