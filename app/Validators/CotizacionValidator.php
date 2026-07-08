<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class CotizacionValidator
{
    public static function validate(array $data): array
    {
        $errors = (new Validator())
            ->required($data, ['orden_id' => 'La orden'])
            ->numeric($data, 'descuento', 'El descuento')
            ->numeric($data, 'iva', 'El IVA')
            ->errors();

        if (($data['descuento'] ?? '') !== '' && is_numeric($data['descuento']) && (float) $data['descuento'] < 0) {
            $errors[] = ['field' => 'descuento', 'message' => 'El descuento no puede ser negativo'];
        }
        if (($data['iva'] ?? '') !== '' && is_numeric($data['iva']) && (float) $data['iva'] < 0) {
            $errors[] = ['field' => 'iva', 'message' => 'El IVA no puede ser negativo'];
        }

        $items = self::itemsParaValidar($data);
        if ($items === []) {
            $errors[] = ['field' => 'items', 'message' => 'Agrega al menos un concepto a la cotizacion'];
            return $errors;
        }

        foreach ($items as $index => $item) {
            $field = 'items.' . $index;
            $descripcion = trim((string) ($item['descripcion'] ?? ''));
            $refaccionId = trim((string) ($item['refaccion_id'] ?? ''));
            $cantidad = $item['cantidad'] ?? '';
            $precio = $item['precio_unitario'] ?? '';

            if ($refaccionId === '' && $descripcion === '') {
                $errors[] = ['field' => $field . '.descripcion', 'message' => 'La descripcion del concepto es obligatoria'];
            }
            if ($cantidad !== '' && !is_numeric($cantidad)) {
                $errors[] = ['field' => $field . '.cantidad', 'message' => 'La cantidad debe ser numerica'];
            }
            if ($cantidad !== '' && is_numeric($cantidad) && (float) $cantidad <= 0) {
                $errors[] = ['field' => $field . '.cantidad', 'message' => 'La cantidad debe ser mayor a cero'];
            }
            if ($precio !== '' && !is_numeric($precio)) {
                $errors[] = ['field' => $field . '.precio_unitario', 'message' => 'El precio debe ser numerico'];
            }
            if ($precio !== '' && is_numeric($precio) && (float) $precio < 0) {
                $errors[] = ['field' => $field . '.precio_unitario', 'message' => 'El precio no puede ser negativo'];
            }
        }

        return $errors;
    }

    private static function itemsParaValidar(array $data): array
    {
        $items = isset($data['items']) && is_array($data['items'])
            ? array_values(array_filter($data['items'], 'is_array'))
            : [[
                'tipo' => $data['tipo'] ?? 'servicio',
                'refaccion_id' => $data['refaccion_id'] ?? null,
                'descripcion' => $data['descripcion'] ?? '',
                'cantidad' => $data['cantidad'] ?? 1,
                'precio_unitario' => $data['precio_unitario'] ?? '',
            ]];

        return array_values(array_filter($items, static function (array $item): bool {
            $descripcion = trim((string) ($item['descripcion'] ?? ''));
            $refaccionId = trim((string) ($item['refaccion_id'] ?? ''));
            $precio = $item['precio_unitario'] ?? '';

            return $refaccionId !== '' || $descripcion !== '' || ((string) $precio !== '' && (float) $precio > 0);
        }));
    }
}
