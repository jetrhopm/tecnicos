<?php

declare(strict_types=1);

namespace App\Models;

final class Equipo
{
    public function __construct(
        public readonly int $id,
        public readonly int $clienteId,
        public readonly string $tipo
    ) {
    }
}
