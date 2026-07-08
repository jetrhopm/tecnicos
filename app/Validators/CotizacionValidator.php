<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class CotizacionValidator
{
    public static function validate(array $data): array
    {
        $errors = (new Validator())
            ->required($data, ['orden_id' => 'La orden', 'descripcion' => 'La descripcion', 'precio_unitario' => 'El precio'])
            ->numeric($data, 'cantidad', 'La cantidad')
            ->numeric($data, 'precio_unitario', 'El precio')
            ->numeric($data, 'descuento', 'El descuento')
            ->numeric($data, 'iva', 'El IVA')
            ->errors();

        if (($data['cantidad'] ?? '') !== '' && is_numeric($data['cantidad']) && (float) $data['cantidad'] <= 0) {
            $errors[] = ['field' => 'cantidad', 'message' => 'La cantidad debe ser mayor a cero'];
        }
        if (($data['precio_unitario'] ?? '') !== '' && is_numeric($data['precio_unitario']) && (float) $data['precio_unitario'] < 0) {
            $errors[] = ['field' => 'precio_unitario', 'message' => 'El precio no puede ser negativo'];
        }
        if (($data['descuento'] ?? '') !== '' && is_numeric($data['descuento']) && (float) $data['descuento'] < 0) {
            $errors[] = ['field' => 'descuento', 'message' => 'El descuento no puede ser negativo'];
        }
        if (($data['iva'] ?? '') !== '' && is_numeric($data['iva']) && (float) $data['iva'] < 0) {
            $errors[] = ['field' => 'iva', 'message' => 'El IVA no puede ser negativo'];
        }

        return $errors;
    }
}
