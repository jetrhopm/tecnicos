<?php

declare(strict_types=1);

function calcularSubtotal(float|int $cantidad, float|int $precioUnitario): float
{
    return round(max(0, (float) $cantidad) * max(0, (float) $precioUnitario), 2);
}

function calcularIVA(float|int $subtotal, float|int $porcentajeIVA): float
{
    return round(max(0, (float) $subtotal) * (max(0, (float) $porcentajeIVA) / 100), 2);
}

function calcularTotal(float|int $subtotal, float|int $descuento, float|int $iva): float
{
    return round(max(0, (float) $subtotal - max(0, (float) $descuento) + max(0, (float) $iva)), 2);
}

function calcularSaldo(float|int $total, float|int $pagosRealizados): float
{
    return round(max(0, (float) $total - max(0, (float) $pagosRealizados)), 2);
}

function monedaSistema(string $default = 'MXN'): string
{
    static $moneda = null;
    if ($moneda !== null) {
        return $moneda;
    }

    try {
        $valor = (string) (new \App\Services\ConfiguracionService())->get('sistema.moneda', $default);
        $valor = strtoupper(preg_replace('/[^A-Za-z]/', '', $valor) ?? '');
        $moneda = strlen($valor) === 3 ? $valor : $default;
    } catch (\Throwable) {
        $moneda = $default;
    }

    return $moneda;
}

function formatearMoneda(float|int $monto, ?string $moneda = null): string
{
    $moneda = $moneda !== null ? strtoupper($moneda) : monedaSistema();
    return '$' . number_format((float) $monto, 2) . ' ' . $moneda;
}
