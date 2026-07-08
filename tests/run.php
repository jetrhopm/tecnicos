<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$tests = [
    'calcularSubtotal' => calcularSubtotal(2, 150.50) === 301.00,
    'calcularIVA' => calcularIVA(100, 16) === 16.00,
    'calcularTotal' => calcularTotal(100, 10, 16) === 106.00,
    'calcularSaldo' => calcularSaldo(500, 125) === 375.00,
    'generarFolio' => generarFolio('ST', 7, '2026-06-14') === 'ST-20260614-00007',
    'normalizarTelefono' => normalizarTelefono('+52 (999) 123-4567') === '529991234567',
    'crearMensajeWhatsapp' => crearMensajeWhatsapp('Hola {cliente}', ['cliente' => 'Ana']) === 'Hola Ana',
    'validarEmail' => filter_var('admin@local.test', FILTER_VALIDATE_EMAIL) !== false,
    'calcularDiasGarantia' => calcularDiasGarantia('2026-06-01', '2026-07-01') === 30,
    'cotizacionCantidadCero' => array_filter(
        \App\Validators\CotizacionValidator::validate(['orden_id' => 1, 'descripcion' => 'Revision', 'cantidad' => 0, 'precio_unitario' => 100]),
        static fn (array $error): bool => $error['field'] === 'cantidad'
    ) !== [],
    'cotizacionPrecioNegativo' => array_filter(
        \App\Validators\CotizacionValidator::validate(['orden_id' => 1, 'descripcion' => 'Revision', 'cantidad' => 1, 'precio_unitario' => -1]),
        static fn (array $error): bool => $error['field'] === 'precio_unitario'
    ) !== [],
];

$failed = array_filter($tests, static fn (bool $ok): bool => !$ok);
foreach ($tests as $name => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $name . PHP_EOL;
}

exit($failed ? 1 : 0);
