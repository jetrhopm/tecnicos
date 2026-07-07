<?php

declare(strict_types=1);

namespace App\Services;

final class MensajeService
{
    public function whatsappOrden(array $orden, string $plantillaClave = 'whatsapp.orden_recibida'): string
    {
        $config = new ConfiguracionService();
        $plantilla = (string) $config->get($plantillaClave, '');
        $equipo = trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: ($orden['equipo_tipo'] ?? 'equipo');
        // Absoluto a proposito: este enlace viaja en el mensaje de WhatsApp y
        // el cliente lo abre desde su telefono, fuera de este navegador.
        $link = absolute_url('/consulta?folio=' . urlencode((string) $orden['folio']) . '&token=' . urlencode((string) $orden['token_publico']));
        $mensaje = crearMensajeWhatsapp($plantilla, [
            'cliente' => $orden['cliente_nombre'] ?? '',
            'folio' => $orden['folio'] ?? '',
            'equipo' => $equipo,
            'link' => $link,
            'saldo' => formatearMoneda((float) ($orden['saldo_pendiente'] ?? 0)),
        ]);

        return linkWhatsapp((string) ($orden['cliente_whatsapp'] ?: $orden['cliente_telefono'] ?? ''), $mensaje);
    }
}
