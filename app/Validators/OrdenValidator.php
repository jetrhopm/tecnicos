<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class OrdenValidator
{
    public static function validate(array $data): array
    {
        return (new Validator())
            ->required($data, [
                'cliente_id' => 'El cliente',
                'equipo_id' => 'El equipo',
                'tipo_servicio' => 'El tipo de servicio',
                'falla_reportada' => 'La falla reportada',
            ])
            ->numeric($data, 'costo_estimado', 'El costo estimado')
            ->numeric($data, 'anticipo', 'El anticipo')
            ->errors();
    }
}
