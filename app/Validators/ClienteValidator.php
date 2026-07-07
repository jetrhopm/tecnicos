<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class ClienteValidator
{
    public static function validate(array $data): array
    {
        $validator = (new Validator())
            ->required($data, ['nombre_completo' => 'El nombre', 'telefono' => 'El telefono'])
            ->email($data, 'email', 'El email');

        return $validator->errors();
    }
}
