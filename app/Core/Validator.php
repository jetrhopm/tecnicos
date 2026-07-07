<?php

declare(strict_types=1);

namespace App\Core;

final class Validator
{
    private array $errors = [];

    public function required(array $data, array $fields): self
    {
        foreach ($fields as $field => $label) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
                $this->errors[] = ['field' => (string) $field, 'message' => "{$label} es obligatorio"];
            }
        }

        return $this;
    }

    public function email(array $data, string $field, string $label): self
    {
        $value = trim((string) ($data[$field] ?? ''));
        if ($value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[] = ['field' => $field, 'message' => "{$label} no es valido"];
        }

        return $this;
    }

    public function numeric(array $data, string $field, string $label): self
    {
        $value = $data[$field] ?? null;
        if ($value !== null && $value !== '' && !is_numeric($value)) {
            $this->errors[] = ['field' => $field, 'message' => "{$label} debe ser numerico"];
        }

        return $this;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }
}
