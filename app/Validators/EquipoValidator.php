<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class EquipoValidator
{
    public static function validate(array $data): array
    {
        return (new Validator())
            ->required($data, ['cliente_id' => 'El cliente', 'tipo' => 'El tipo de equipo'])
            ->errors();
    }
}
