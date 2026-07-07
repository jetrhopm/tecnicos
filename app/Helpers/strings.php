<?php

declare(strict_types=1);

function generarFolio(string $prefijo, int $consecutivo, DateTimeInterface|string|null $fecha = null): string
{
    $fecha = $fecha instanceof DateTimeInterface ? $fecha : new DateTimeImmutable((string) ($fecha ?: 'now'));
    return strtoupper($prefijo) . '-' . $fecha->format('Ymd') . '-' . str_pad((string) $consecutivo, 5, '0', STR_PAD_LEFT);
}

function normalizarTelefono(string $telefono): string
{
    return preg_replace('/\D+/', '', $telefono) ?? '';
}

function slugSeguro(string $texto): string
{
    $texto = iconv('UTF-8', 'ASCII//TRANSLIT', $texto) ?: $texto;
    $texto = preg_replace('/[^A-Za-z0-9._-]+/', '-', $texto) ?? '';
    return trim($texto, '-');
}
