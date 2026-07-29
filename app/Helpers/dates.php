<?php

declare(strict_types=1);

function normalizarZonaHoraria(string $zona, string $default = 'America/Mexico_City'): string
{
    $zona = trim($zona);
    try {
        new DateTimeZone($zona);
        return $zona;
    } catch (Throwable) {
        return $default;
    }
}

function fechaHumana(?string $fecha): string
{
    if (!$fecha) {
        return '-';
    }

    return date('d/m/Y H:i', strtotime($fecha));
}

function calcularDiasGarantia(string $fechaInicio, string $fechaFin): int
{
    $inicio = new DateTimeImmutable($fechaInicio);
    $fin = new DateTimeImmutable($fechaFin);
    return max(0, (int) $inicio->diff($fin)->format('%a'));
}
