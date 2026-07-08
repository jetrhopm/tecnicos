<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class PagoValidator
{
    public static function validate(array $data): array
    {
        $validator = (new Validator())
            ->required($data, ['orden_id' => 'La orden', 'monto' => 'El monto'])
            ->numeric($data, 'monto', 'El monto');

        $errors = $validator->errors();
        if (isset($data['monto']) && is_numeric($data['monto']) && (float) $data['monto'] <= 0) {
            $errors[] = ['field' => 'monto', 'message' => 'El pago debe ser mayor a cero.'];
        }

        return $errors;
    }
}
