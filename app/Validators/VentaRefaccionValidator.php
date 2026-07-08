<?php

declare(strict_types=1);

namespace App\Validators;

use App\Core\Validator;

final class VentaRefaccionValidator
{
    public static function validate(array $data): array
    {
        $errors = (new Validator())
            ->numeric($data, 'descuento', 'El descuento')
            ->errors();

        if (($data['descuento'] ?? '') !== '' && is_numeric($data['descuento']) && (float) $data['descuento'] < 0) {
            $errors[] = ['field' => 'descuento', 'message' => 'El descuento no puede ser negativo'];
        }

        $items = isset($data['items']) && is_array($data['items'])
            ? array_values(array_filter($data['items'], 'is_array'))
            : [];
        $items = array_values(array_filter($items, static fn (array $item): bool => !empty($item['refaccion_id']) || trim((string) ($item['sku'] ?? '')) !== ''));

        if ($items === []) {
            $errors[] = ['field' => 'items', 'message' => 'Agrega al menos una refaccion a la venta'];
            return $errors;
        }

        foreach ($items as $index => $item) {
            $field = 'items.' . $index;
            $refaccionId = (int) ($item['refaccion_id'] ?? 0);
            $sku = trim((string) ($item['sku'] ?? ''));
            $cantidad = $item['cantidad'] ?? '';
            $precio = $item['precio_unitario'] ?? '';

            if ($refaccionId <= 0 && $sku === '') {
                $errors[] = ['field' => $field . '.refaccion_id', 'message' => 'La refaccion debe tener ID o SKU'];
            }
            if ($cantidad === '' || !is_numeric($cantidad) || (int) $cantidad <= 0) {
                $errors[] = ['field' => $field . '.cantidad', 'message' => 'La cantidad debe ser mayor a cero'];
            }
            if ($precio !== '' && (!is_numeric($precio) || (float) $precio < 0)) {
                $errors[] = ['field' => $field . '.precio_unitario', 'message' => 'El precio no puede ser negativo'];
            }
        }

        return $errors;
    }
}
