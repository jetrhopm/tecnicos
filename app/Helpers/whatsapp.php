<?php

declare(strict_types=1);

function crearMensajeWhatsapp(string $plantilla, array $datos): string
{
    foreach ($datos as $clave => $valor) {
        $plantilla = str_replace('{' . $clave . '}', (string) $valor, $plantilla);
    }

    return $plantilla;
}

function linkWhatsapp(string $telefono, string $mensaje, string $codigoPais = '52'): string
{
    $telefono = normalizarTelefono($telefono);
    if (!str_starts_with($telefono, $codigoPais)) {
        $telefono = $codigoPais . $telefono;
    }

    return 'https://wa.me/' . $telefono . '?text=' . rawurlencode($mensaje);
}
