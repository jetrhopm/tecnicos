<?php

declare(strict_types=1);

function crearMensajeWhatsapp(string $plantilla, array $datos): string
{
    foreach ($datos as $clave => $valor) {
        $plantilla = str_replace('{' . $clave . '}', (string) $valor, $plantilla);
    }

    return $plantilla;
}

function codigoPaisWhatsapp(string $default = '52'): string
{
    static $codigo = null;
    if ($codigo !== null) {
        return $codigo;
    }

    try {
        $valor = (string) (new \App\Services\ConfiguracionService())->get('whatsapp.codigo_pais', $default);
        $valor = preg_replace('/\D+/', '', $valor) ?? '';
        $codigo = $valor !== '' ? substr($valor, 0, 4) : $default;
    } catch (\Throwable) {
        $codigo = $default;
    }

    return $codigo;
}

function linkWhatsapp(string $telefono, string $mensaje, ?string $codigoPais = null): string
{
    $codigoPais = preg_replace('/\D+/', '', (string) ($codigoPais ?? codigoPaisWhatsapp())) ?? '52';
    $codigoPais = $codigoPais !== '' ? $codigoPais : '52';
    $telefono = normalizarTelefono($telefono);
    if (!str_starts_with($telefono, $codigoPais)) {
        $telefono = $codigoPais . $telefono;
    }

    return 'https://wa.me/' . $telefono . '?text=' . rawurlencode($mensaje);
}
