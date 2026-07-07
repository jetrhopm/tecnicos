<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class CotizacionValidator
{
    public static function validate(array $data): array
    {
        return (new Validator())
            ->required($data, ['orden_id' => 'La orden', 'descripcion' => 'La descripcion', 'precio_unitario' => 'El precio'])
            ->numeric($data, 'cantidad', 'La cantidad')
            ->numeric($data, 'precio_unitario', 'El precio')
            ->errors();
    }
}
