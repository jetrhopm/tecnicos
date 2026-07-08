<?php

declare(strict_types=1);

function extensionPermitida(string $filename): bool
{
    return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'pdf'], true);
}

function nombreArchivoSeguro(string $filename): string
{
    $base = pathinfo($filename, PATHINFO_FILENAME);
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return slugSeguro($base) . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
}

function config_asset_src(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    if (str_starts_with($value, 'assets/')) {
        return url('/' . $value);
    }

    if (str_starts_with($value, '/assets/')) {
        return url($value);
    }

    return $value;
}
