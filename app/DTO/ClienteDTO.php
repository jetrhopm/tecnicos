<?php

declare(strict_types=1);

namespace App\DTO;

final class ClienteDTO
{
    public function __construct(public readonly string $nombreCompleto, public readonly string $telefono, public readonly ?string $email = null)
    {
    }
}
