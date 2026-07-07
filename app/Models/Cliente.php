<?php

declare(strict_types=1);

namespace App\Models;

final class Cliente
{
    public function __construct(
        public readonly int $id,
        public readonly string $nombreCompleto,
        public readonly string $telefono
    ) {
    }
}
