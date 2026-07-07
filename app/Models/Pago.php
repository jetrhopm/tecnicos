<?php

declare(strict_types=1);

namespace App\Models;

final class Pago
{
    public function __construct(public readonly int $id, public readonly int $ordenId, public readonly float $monto)
    {
    }
}
