<?php

declare(strict_types=1);

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
