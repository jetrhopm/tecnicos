<?php

declare(strict_types=1);

namespace App\Services;

final class OrdenPdfService
{
    private const PAGE_WIDTH = 612;
    private const PAGE_HEIGHT = 792;
    private array $annotations = [];

    /**
     * Genera el PDF de recepcion con el mismo enfoque visual del comprobante nuevo:
     * cabecera del negocio, ficha en recuadros, patron/clave, importes y clave de entrega.
     * No guarda archivo en disco ni base de datos; el controlador decide si lo muestra/envia.
     */
    public function recepcion(array $orden, ?array $diagnostico = null, ?array $cotizacion = null, array $config = []): string
    {
        $this->annotations = [];
        $lines = [];

        $folio = (string) ($orden['folio'] ?? '');
        $token = (string) ($orden['token_publico'] ?? '');
        $publicUrl = absolute_url('/consulta/' . rawurlencode($folio) . '/' . rawurlencode($token));
        $pdfUrl = absolute_url('/consulta/' . rawurlencode($folio) . '/' . rawurlencode($token) . '/pdf');

        $total = (float) ($orden['costo_final'] ?? $orden['costo_estimado'] ?? 0);
        if ($cotizacion && isset($cotizacion['total']) && $total <= 0) {
            $total = (float) $cotizacion['total'];
        }
        $anticipo = (float) ($orden['anticipo'] ?? 0);
        $saldo = (float) ($orden['saldo_pendiente'] ?? max(0, $total - $anticipo));

        $negocio = trim((string) ($config['negocio.nombre'] ?? '')) ?: 'Servicio Tecnico';
        $telefono = trim((string) ($config['negocio.telefono'] ?? ''));
        $whatsapp = trim((string) ($config['negocio.whatsapp'] ?? ''));
        $direccion = trim((string) ($config['negocio.direccion'] ?? ''));
        $condiciones = trim((string) ($config['legal.politica_garantia'] ?? $config['ticket.garantia'] ?? ''));
        $trabajo = trim((string) ($diagnostico['diagnostico_cliente'] ?? $orden['diagnostico_inicial'] ?? $orden['tipo_servicio'] ?? ''));

        $data = [
            'folio' => $folio,
            'publicUrl' => $publicUrl,
            'pdfUrl' => $pdfUrl,
            'negocio' => $negocio,
            'telefono' => $telefono,
            'whatsapp' => $whatsapp,
            'direccion' => $direccion,
            'cliente' => (string) (($orden['cliente_nombre'] ?? '') ?: '-'),
            'clienteTelefono' => $this->firstValue($orden['cliente_whatsapp'] ?? '', $orden['cliente_telefono'] ?? ''),
            'clienteEmail' => (string) (($orden['cliente_email'] ?? '') ?: '-'),
            'clienteDomicilio' => (string) (($orden['cliente_domicilio'] ?? '') ?: '-'),
            'equipoTipo' => ucfirst((string) (($orden['equipo_tipo'] ?? '') ?: '-')),
            'marca' => (string) (($orden['equipo_marca'] ?? '') ?: '-'),
            'modelo' => (string) (($orden['equipo_modelo'] ?? '') ?: '-'),
            'imeiSerie' => trim((string) ($orden['imei'] ?? '') . (($orden['imei'] ?? '') && ($orden['numero_serie'] ?? '') ? ' / ' : '') . (string) ($orden['numero_serie'] ?? '')) ?: '-',
            'desbloqueo' => $this->unlockData((string) ($orden['password_equipo'] ?? '')),
            'servicio' => (string) (($orden['tipo_servicio'] ?? '') ?: '-'),
            'accesorios' => (string) (($orden['accesorios_recibidos'] ?? '') ?: 'Sin accesorios'),
            'estadoFisico' => (string) (($orden['equipo_estado_fisico'] ?? '') ?: '-'),
            'falla' => (string) (($orden['falla_reportada'] ?? '') ?: '-'),
            'observaciones' => (string) (($orden['observaciones_cliente'] ?? '') ?: '-'),
            'trabajo' => $trabajo !== '' ? $trabajo : '-',
            'fechaEntrada' => fechaHumana($orden['fecha_recepcion'] ?? null),
            'fechaEntrega' => fechaHumana($orden['fecha_estimada_entrega'] ?? null),
            'estado' => (string) (($orden['estado'] ?? '') ?: '-'),
            'codigoEntrega' => (string) (($orden['codigo_entrega'] ?? '') ?: '-'),
            'total' => $total,
            'anticipo' => $anticipo,
            'saldo' => $saldo,
            'condiciones' => $condiciones,
        ];

        $this->rect($lines, 10, 10, 592, 772, [255, 255, 255], [219, 234, 254], 0.4);
        $this->receiptCopy($lines, $data, 16, 410, 580, 362, 'COPIA CLIENTE');
        $this->receiptCopy($lines, $data, 16, 20, 580, 362, 'COPIA TALLER');

        return $this->render(implode("\n", $lines), 'Orden ' . $folio);
    }

    private function receiptCopy(array &$lines, array $data, float $x, float $y, float $w, float $h, string $copyLabel): void
    {
        $blue = [37, 99, 235];
        $softBlue = [219, 234, 254];
        $ink = [15, 23, 42];
        $muted = [71, 85, 105];
        $top = $y + $h;
        $ix = $x + 8;
        $iw = $w - 16;

        $this->rect($lines, $x, $y, $w, $h, [248, 251, 255], $blue, 0.8);
        $this->rect($lines, $ix, $top - 50, 46, 42, [15, 23, 42], [96, 165, 250], 0.6);
        $this->text($lines, $ix + 11, $top - 28, 15, 'ST', true, [255, 255, 255]);

        $this->text($lines, $ix + 55, $top - 18, 10, (string) $data['negocio'], true, $ink);
        $this->text($lines, $ix + 55, $top - 29, 6, 'Servicio tecnico', true, $ink);
        $contacto = trim('Tel: ' . (string) $data['telefono'] . '   WhatsApp: ' . (string) $data['whatsapp']);
        $this->text($lines, $ix + 55, $top - 39, 6, trim($contacto) !== 'Tel:    WhatsApp:' ? $contacto : 'Comprobante de recepcion y entrega', false, $ink);
        $this->text($lines, $ix + 55, $top - 48, 5, $this->firstValue($data['direccion'] ?? '', $data['clienteEmail'] ?? ''), false, $muted);

        $folioX = $x + $w - 128;
        $this->text($lines, $folioX, $top - 16, 8, 'Orden: ' . (string) $data['folio'], true, $ink);
        $this->text($lines, $folioX, $top - 28, 8, $copyLabel, true, [30, 64, 175]);
        $this->text($lines, $folioX, $top - 39, 6, 'Fecha: ' . (string) $data['fechaEntrada'], false, $ink);
        $this->line($lines, $ix, $top - 56, $x + $w - 8, $top - 56, $blue, 0.7);

        $row = $top - 80;
        $fh = 20;
        $gap = 3;
        $this->compactField($lines, $ix, $row, 210, $fh, 'Cliente', (string) $data['cliente']);
        $this->compactField($lines, $ix + 213, $row, 110, $fh, 'Telefono', (string) $data['clienteTelefono']);
        $this->compactField($lines, $ix + 326, $row, 142, $fh, 'Correo', (string) $data['clienteEmail']);
        $this->compactField($lines, $ix + 471, $row, $iw - 471, $fh, 'Estado', (string) $data['estado']);

        $row -= $fh + $gap;
        $this->compactField($lines, $ix, $row, $iw, $fh, 'Direccion / correo', (string) $data['clienteDomicilio']);

        $row -= $fh + $gap;
        $this->compactField($lines, $ix, $row, 76, $fh, 'Equipo', (string) $data['equipoTipo']);
        $this->compactField($lines, $ix + 79, $row, 110, $fh, 'Marca', (string) $data['marca']);
        $this->compactField($lines, $ix + 192, $row, 136, $fh, 'Modelo', (string) $data['modelo']);
        $this->compactField($lines, $ix + 331, $row, 148, $fh, 'IMEI / Serie', (string) $data['imeiSerie']);
        $this->compactUnlockField($lines, $ix + 482, $row, $iw - 482, $fh, $data['desbloqueo']);

        $row -= $fh + $gap;
        $this->compactField($lines, $ix, $row, 180, $fh, 'Servicio rapido / plantilla', (string) $data['servicio']);
        $this->compactField($lines, $ix + 183, $row, 172, $fh, 'Accesorios', (string) $data['accesorios']);
        $this->compactField($lines, $ix + 358, $row, $iw - 358, $fh, 'Estado fisico al recibir', (string) $data['estadoFisico']);

        $row -= 27;
        $this->compactMultiline($lines, $ix, $row, $iw, 24, 'Falla reportada', (string) $data['falla'], 118, 2);

        $row -= 31;
        $this->compactMultiline($lines, $ix, $row, 278, 28, 'Observaciones / notas', (string) $data['observaciones'], 58, 2);
        $this->compactMultiline($lines, $ix + 281, $row, $iw - 281, 28, 'Reparacion / trabajo', (string) $data['trabajo'], 58, 2);

        $row -= 25;
        $this->compactField($lines, $ix, $row, 92, $fh, 'Fecha entrada', (string) $data['fechaEntrada']);
        $this->compactField($lines, $ix + 95, $row, 92, $fh, 'Fecha entrega', (string) $data['fechaEntrega']);
        $this->compactField($lines, $ix + 190, $row, 92, $fh, 'Forma de pago', '-');
        $this->compactField($lines, $ix + 285, $row, 92, $fh, 'Cotizacion', formatearMoneda((float) $data['total']));
        $this->compactField($lines, $ix + 380, $row, 72, $fh, 'Recargo', formatearMoneda(0));
        $this->compactField($lines, $ix + 455, $row, $iw - 455, $fh, 'Total del servicio', formatearMoneda((float) $data['total']));

        $row -= $fh + $gap;
        $this->compactField($lines, $ix, $row, 186, $fh, 'Cobrado / anticipo', formatearMoneda((float) $data['anticipo']));
        $this->compactField($lines, $ix + 189, $row, 186, $fh, 'Saldo pendiente', formatearMoneda((float) $data['saldo']));
        $this->compactField($lines, $ix + 378, $row, $iw - 378, $fh, 'Clave de entrega', (string) $data['codigoEntrega']);

        $bottom = $y + 11;
        $this->compactPatternBox($lines, $ix, $bottom, 88, 92, $data['desbloqueo']);
        $this->compactTermsBox($lines, $ix + 92, $bottom + 38, 314, 54, (string) $data['condiciones']);
        $this->compactAcceptanceBox($lines, $ix + 92, $bottom, 314, 35);
        $this->compactDeliveryBox($lines, $ix + 410, $bottom, $iw - 410, 92, (string) $data['codigoEntrega'], (string) $data['publicUrl'], (string) $data['pdfUrl']);

        $this->rect($lines, $x + 1, $y + 1, $w - 2, $h - 2, null, $softBlue, 0.35);
    }

    private function compactField(array &$lines, float $x, float $y, float $w, float $h, string $label, string $value): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 3, $y + $h - 7, 4, strtoupper($label), true, [15, 23, 42]);
        $wrapped = array_slice($this->wrap($value !== '' ? $value : '-', max(10, (int) floor($w / 4.2))), 0, 2);
        $lineY = $y + $h - 15;
        foreach ($wrapped as $line) {
            $this->text($lines, $x + 3, $lineY, 5.5, $line, false, [15, 23, 42]);
            $lineY -= 7;
        }
    }

    private function compactMultiline(array &$lines, float $x, float $y, float $w, float $h, string $label, string $value, int $chars, int $maxLines): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 3, $y + $h - 7, 4, strtoupper($label), true, [15, 23, 42]);
        $wrapped = array_slice($this->wrap($value !== '' ? $value : '-', $chars), 0, $maxLines);
        $lineY = $y + $h - 15;
        foreach ($wrapped as $line) {
            $this->text($lines, $x + 3, $lineY, 5.2, $line, false, [15, 23, 42]);
            $lineY -= 7;
        }
    }

    private function compactUnlockField(array &$lines, float $x, float $y, float $w, float $h, array $desbloqueo): void
    {
        $value = '-';
        if (($desbloqueo['tipo'] ?? null) === 'patron') {
            $value = implode('-', $desbloqueo['secuencia']);
        } elseif (($desbloqueo['tipo'] ?? null) === 'clave') {
            $value = (string) $desbloqueo['valor'];
        }

        $this->compactField($lines, $x, $y, $w, $h, 'Clave / PIN', $value);
    }

    private function compactPatternBox(array &$lines, float $x, float $y, float $w, float $h, array $desbloqueo): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 4, $y + $h - 8, 5, 'PATRON', true, [15, 23, 42]);

        if (($desbloqueo['tipo'] ?? null) === 'patron') {
            $this->drawPattern($lines, $x + 19, $y + 25, 46, $desbloqueo['secuencia']);
            $this->text($lines, $x + 4, $y + 12, 4.5, 'Inicio: ' . $desbloqueo['secuencia'][0] . '  Fin: ' . end($desbloqueo['secuencia']), true, [15, 23, 42]);
            $this->text($lines, $x + 4, $y + 5, 4.2, 'Secuencia: ' . implode(' > ', $desbloqueo['secuencia']), false, [15, 23, 42]);
            return;
        }

        if (($desbloqueo['tipo'] ?? null) === 'clave') {
            $this->text($lines, $x + 4, $y + 52, 5, 'CLAVE / PIN', true, [15, 23, 42]);
            $this->text($lines, $x + 4, $y + 40, 8, (string) $desbloqueo['valor'], true, [15, 23, 42]);
            return;
        }

        $this->text($lines, $x + 4, $y + 45, 7, 'Sin clave registrada', false, [71, 85, 105]);
    }

    private function compactTermsBox(array &$lines, float $x, float $y, float $w, float $h, string $terms): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 4, $y + $h - 8, 5, 'GARANTIA Y CONDICIONES', true, [15, 23, 42]);

        $items = array_values(array_filter(array_map('trim', preg_split('/\R+/', $terms) ?: [])));
        if ($items === []) {
            $items = ['La garantia aplica solo sobre la falla reparada y bajo las condiciones del taller.'];
        }

        $columns = [array_slice($items, 0, 5), array_slice($items, 5)];
        foreach ($columns as $index => $columnItems) {
            $cx = $x + 4 + ($index * (($w - 8) / 2));
            $cy = $y + $h - 16;
            foreach ($columnItems as $item) {
                $wrapped = array_slice($this->wrap($item, 58), 0, 2);
                foreach ($wrapped as $line) {
                    $this->text($lines, $cx, $cy, 3.6, $line, false, [15, 23, 42]);
                    $cy -= 4.8;
                }
            }
        }
    }

    private function compactAcceptanceBox(array &$lines, float $x, float $y, float $w, float $h): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 4, $y + $h - 8, 5, 'ACEPTACION DEL CLIENTE', true, [15, 23, 42]);
        $this->text($lines, $x + 4, $y + $h - 16, 4.2, 'El cliente acepta el ingreso del equipo al servicio tecnico y las condiciones del servicio.', false, [15, 23, 42]);
        $this->line($lines, $x + $w - 104, $y + 8, $x + $w - 16, $y + 8, [71, 85, 105], 0.35);
        $this->text($lines, $x + $w - 76, $y + 2, 4.2, 'Firma cliente', false, [15, 23, 42]);
    }

    private function compactDeliveryBox(array &$lines, float $x, float $y, float $w, float $h, string $code, string $publicUrl, string $pdfUrl): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [96, 165, 250], 0.45);
        $this->text($lines, $x + 4, $y + $h - 8, 5, 'ENTREGA / CONSULTA', true, [15, 23, 42]);
        $this->text($lines, $x + 4, $y + $h - 17, 4.4, 'Clave: ' . $code, true, [15, 23, 42]);
        $this->compactBarcode($lines, $code, $x + 5, $y + $h - 43, 16, 0.45);
        $this->text($lines, $x + 4, $y + 31, 4.2, 'Links clicables:', true, [71, 85, 105]);
        $this->text($lines, $x + 4, $y + 24, 4.2, 'Abrir consulta de estado', false, [30, 64, 175]);
        $this->text($lines, $x + 4, $y + 17, 4.2, 'Abrir PDF del comprobante', false, [30, 64, 175]);
        $this->addLink($x + 3, $y + 21, $w - 8, 8, $publicUrl);
        $this->addLink($x + 3, $y + 14, $w - 8, 8, $pdfUrl);
        $this->text($lines, $x + 4, $y + 7, 4.2, 'Presentar esta orden para retirar.', true, [15, 23, 42]);
    }

    private function compactBarcode(array &$lines, string $text, float $x, float $y, float $height, float $module): void
    {
        $text = strtoupper(trim($text));
        $text = preg_replace('/[^A-Z0-9 \-\.\$\/\+%]/', '', $text) ?? '';
        if ($text === '') {
            return;
        }

        $this->rect($lines, $x - 2, $y - 2, 132, $height + 4, [255, 255, 255], [226, 232, 240], 0.25);
        $encoded = '*' . $text . '*';
        $patterns = $this->barcodePatterns();
        $commands = ['0 g'];
        $cursor = $x;

        foreach (str_split($encoded) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $part) {
                $width = $part === 'w' ? $module * 3 : $module;
                if ($index % 2 === 0) {
                    $commands[] = sprintf('%.2F %.2F %.2F %.2F re f', $cursor, $y, $width, $height);
                }
                $cursor += $width;
            }
            $cursor += $module;
        }

        $lines[] = implode("\n", $commands);
    }

    private function pageBackground(array &$lines): void
    {
        $this->rect($lines, 28, 25, 556, 742, [248, 251, 255], [149, 204, 236], 0.8);
        $this->rect($lines, 36, 33, 540, 726, null, [34, 151, 219], 0.6);
    }

    private function header(array &$lines, string $negocio, string $telefono, string $whatsapp, string $direccion, string $folio): void
    {
        $this->rect($lines, 44, 705, 524, 50, [8, 35, 58], [34, 151, 219], 1.0);
        $this->rect($lines, 56, 716, 40, 28, [14, 165, 233], [91, 213, 255], 0.8);
        $this->text($lines, 66, 725, 16, 'ST', true, [255, 255, 255]);

        $this->text($lines, 108, 738, 15, $negocio, true, [255, 255, 255]);
        $this->text($lines, 108, 724, 8, 'Servicio Tecnico', false, [187, 226, 255]);

        $contacto = trim(($telefono !== '' ? 'Tel: ' . $telefono : '') . (($telefono !== '' && $whatsapp !== '') ? '  |  ' : '') . ($whatsapp !== '' ? 'WhatsApp: ' . $whatsapp : ''));
        if ($contacto !== '') {
            $this->text($lines, 108, 713, 7, $contacto, false, [187, 226, 255]);
        }
        if ($direccion !== '') {
            $this->text($lines, 250, 713, 7, $direccion, false, [187, 226, 255]);
        }

        $this->rect($lines, 424, 718, 126, 24, [11, 78, 120], [91, 213, 255], 0.8);
        $this->text($lines, 434, 726, 10, 'ORDEN #' . $folio, true, [255, 255, 255]);
    }

    private function fieldBox(array &$lines, float $x, float $y, float $w, float $h, string $label, string $value): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [121, 190, 231], 0.7);
        $this->text($lines, $x + 9, $y + $h - 13, 7, strtoupper($label), true, [14, 83, 129]);
        $wrapped = array_slice($this->wrap($value !== '' ? $value : '-', max(12, (int) floor($w / 5.1))), 0, 2);
        $lineY = $y + $h - 28;
        foreach ($wrapped as $line) {
            $this->text($lines, $x + 9, $lineY, 9, $line, false, [15, 23, 42]);
            $lineY -= 11;
        }
    }

    private function multilineBox(array &$lines, float $x, float $y, float $w, float $h, string $label, string $value, int $chars, int $maxLines): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [121, 190, 231], 0.7);
        $this->text($lines, $x + 9, $y + $h - 13, 7, strtoupper($label), true, [14, 83, 129]);
        $wrapped = array_slice($this->wrap($value !== '' ? $value : '-', $chars), 0, $maxLines);
        $lineY = $y + $h - 28;
        foreach ($wrapped as $line) {
            $this->text($lines, $x + 9, $lineY, 8, $line, false, [15, 23, 42]);
            $lineY -= 11;
        }
    }

    private function patternBox(array &$lines, float $x, float $y, float $w, float $h, array $desbloqueo): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [121, 190, 231], 0.7);
        $this->text($lines, $x + 9, $y + $h - 13, 7, 'PATRON / CLAVE DE DESBLOQUEO', true, [14, 83, 129]);

        if (($desbloqueo['tipo'] ?? null) === 'patron') {
            $this->drawPattern($lines, $x + 12, $y + 8, 46, $desbloqueo['secuencia']);
            $this->text($lines, $x + 74, $y + 36, 8, 'Inicio: ' . $desbloqueo['secuencia'][0] . '  Fin: ' . end($desbloqueo['secuencia']), true, [15, 23, 42]);
            $this->text($lines, $x + 74, $y + 23, 7, 'Sec: ' . implode(' > ', $desbloqueo['secuencia']), false, [74, 85, 104]);
            return;
        }

        if (($desbloqueo['tipo'] ?? null) === 'clave') {
            $this->text($lines, $x + 9, $y + 23, 13, (string) $desbloqueo['valor'], true, [15, 23, 42]);
            return;
        }

        $this->text($lines, $x + 9, $y + 23, 9, '-', false, [15, 23, 42]);
    }

    private function termsBox(array &$lines, float $x, float $y, float $w, float $h, string $terms): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [121, 190, 231], 0.7);
        $this->text($lines, $x + 9, $y + $h - 13, 7, 'GARANTIA Y CONDICIONES', true, [14, 83, 129]);
        $text = $terms !== '' ? $terms : 'El cliente acepta las condiciones del servicio tecnico. Revisar funcionamiento antes de retirarse.';
        $wrapped = array_slice($this->wrap($text, 66), 0, 3);
        $lineY = $y + $h - 27;
        foreach ($wrapped as $line) {
            $this->text($lines, $x + 9, $lineY, 6, $line, false, [74, 85, 104]);
            $lineY -= 9;
        }
    }

    private function totalsBox(array &$lines, float $x, float $y, float $w, float $h, float $total, float $anticipo, float $saldo): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [121, 190, 231], 0.7);
        $this->text($lines, $x + 9, $y + $h - 13, 7, 'COBROS Y RESUMEN', true, [14, 83, 129]);
        $this->text($lines, $x + 9, $y + 29, 7, 'Total del servicio', false, [74, 85, 104]);
        $this->text($lines, $x + 120, $y + 29, 8, formatearMoneda($total), true, [15, 23, 42]);
        $this->text($lines, $x + 9, $y + 18, 7, 'Cobrado / Sena', false, [74, 85, 104]);
        $this->text($lines, $x + 120, $y + 18, 8, formatearMoneda($anticipo), true, [15, 23, 42]);
        $this->rect($lines, $x + 7, $y + 4, $w - 14, 11, [34, 197, 94], [22, 163, 74], 0.5);
        $this->text($lines, $x + 12, $y + 7, 7, 'Saldo a cobrar', true, [255, 255, 255]);
        $this->text($lines, $x + 120, $y + 7, 8, formatearMoneda($saldo), true, [255, 255, 255]);
    }

    private function barcodeBlock(array &$lines, string $code, float $x, float $y): void
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return;
        }

        // Zona blanca dedicada al codigo: evita que textos cercanos contaminen la lectura.
        $this->rect($lines, $x - 5, $y - 6, 158, 39, [255, 255, 255], [226, 232, 240], 0.4);
        $this->text($lines, $x, $y + 29, 6, 'Clave de entrega', false, [74, 85, 104]);
        $this->barcode39($lines, $code, $x, $y + 6, 20, 0.68);
    }

    private function consultaBox(array &$lines, float $x, float $y, float $w, float $h, string $publicUrl, string $pdfUrl): void
    {
        $this->rect($lines, $x, $y, $w, $h, [255, 255, 255], [226, 232, 240], 0.4);
        $this->text($lines, $x + 7, $y + $h - 11, 6, 'CONSULTA CLIENTE', true, [74, 85, 104]);
        $this->text($lines, $x + 7, $y + $h - 21, 5, $this->shortUrl($publicUrl, 44), false, [14, 55, 88]);
        $this->text($lines, $x + 7, $y + $h - 30, 5, 'PDF: ' . $this->shortUrl($pdfUrl, 39), false, [74, 85, 104]);
    }

    private function shortUrl(string $url, int $max): string
    {
        if (strlen($url) <= $max) {
            return $url;
        }

        return substr($url, 0, max(0, $max - 3)) . '...';
    }

    private function drawPattern(array &$lines, float $x, float $y, float $size, array $sequence): void
    {
        $points = [];
        $gap = $size / 2;
        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $num = $row * 3 + $col + 1;
                $points[$num] = [$x + $col * $gap, $y + $size - $row * $gap];
            }
        }

        $this->rect($lines, $x - 8, $y - 8, $size + 16, $size + 16, [240, 249, 255], [186, 230, 253], 0.4);

        if (count($sequence) > 1) {
            $cmd = ['0.600 0.200 0.900 RG', '2.0 w'];
            $first = true;
            foreach ($sequence as $node) {
                if (!isset($points[(int) $node])) {
                    continue;
                }
                [$px, $py] = $points[(int) $node];
                $cmd[] = $first ? sprintf('%.2F %.2F m', $px, $py) : sprintf('%.2F %.2F l', $px, $py);
                $first = false;
            }
            $cmd[] = 'S';
            $lines[] = implode("\n", $cmd);
        }

        foreach ($points as $num => [$px, $py]) {
            $selected = in_array($num, array_map('intval', $sequence), true);
            $color = $selected ? [14, 165, 233] : [147, 197, 253];
            $this->circle($lines, $px, $py, $selected ? 4.2 : 2.8, $color, [14, 116, 144], 0.4);
            if ($selected) {
                $this->text($lines, $px - 2.2, $py - 2.4, 5, (string) $num, true, [255, 255, 255]);
            }
        }
    }

    private function unlockData(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['tipo' => null];
        }

        if (preg_match('/Patr(?:o|\x{00F3})n:\s*([0-9\-\s>]+)/iu', $value, $matches)) {
            preg_match_all('/[1-9]/', $matches[1], $digits);
            $sequence = array_values(array_unique(array_map('intval', $digits[0] ?? [])));
            if ($sequence !== []) {
                return ['tipo' => 'patron', 'secuencia' => $sequence];
            }
        }

        if (preg_match('/^(PIN|Clave):\s*(.+)$/iu', $value, $matches)) {
            return ['tipo' => 'clave', 'valor' => trim($matches[2])];
        }

        return ['tipo' => 'clave', 'valor' => $value];
    }

    private function firstValue(mixed ...$values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '-';
    }

    private function wrap(string $text, int $chars): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '') {
            return ['-'];
        }

        return explode("\n", wordwrap($text, $chars, "\n", true));
    }

    private function text(array &$lines, float $x, float $y, float $size, string $text, bool $bold = false, array $color = [0, 0, 0]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $lines[] = sprintf(
            '%.3F %.3F %.3F rg BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
            $color[0] / 255,
            $color[1] / 255,
            $color[2] / 255,
            $font,
            $size,
            $x,
            $y,
            $this->escape($text)
        );
    }

    private function line(array &$lines, float $x1, float $y1, float $x2, float $y2, array $color = [0, 0, 0], float $width = 0.75): void
    {
        $lines[] = sprintf('%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S', $color[0] / 255, $color[1] / 255, $color[2] / 255, $width, $x1, $y1, $x2, $y2);
    }

    private function rect(array &$lines, float $x, float $y, float $w, float $h, ?array $fill, ?array $stroke, float $width = 0.75): void
    {
        $commands = [];
        if ($fill !== null) {
            $commands[] = sprintf('%.3F %.3F %.3F rg', $fill[0] / 255, $fill[1] / 255, $fill[2] / 255);
        }
        if ($stroke !== null) {
            $commands[] = sprintf('%.3F %.3F %.3F RG %.2F w', $stroke[0] / 255, $stroke[1] / 255, $stroke[2] / 255, $width);
        }
        $commands[] = sprintf('%.2F %.2F %.2F %.2F re %s', $x, $y, $w, $h, $fill !== null && $stroke !== null ? 'B' : ($fill !== null ? 'f' : 'S'));
        $lines[] = implode("\n", $commands);
    }

    private function circle(array &$lines, float $cx, float $cy, float $r, array $fill, array $stroke, float $width = 0.75): void
    {
        $c = 0.5522847498 * $r;
        $lines[] = sprintf(
            "%.3F %.3F %.3F rg\n%.3F %.3F %.3F RG %.2F w\n%.2F %.2F m\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\n%.2F %.2F %.2F %.2F %.2F %.2F c\nB",
            $fill[0] / 255,
            $fill[1] / 255,
            $fill[2] / 255,
            $stroke[0] / 255,
            $stroke[1] / 255,
            $stroke[2] / 255,
            $width,
            $cx + $r,
            $cy,
            $cx + $r,
            $cy + $c,
            $cx + $c,
            $cy + $r,
            $cx,
            $cy + $r,
            $cx - $c,
            $cy + $r,
            $cx - $r,
            $cy + $c,
            $cx - $r,
            $cy,
            $cx - $r,
            $cy - $c,
            $cx - $c,
            $cy - $r,
            $cx,
            $cy - $r,
            $cx + $c,
            $cy - $r,
            $cx + $r,
            $cy - $c,
            $cx + $r,
            $cy
        );
    }

    private function barcode39(array &$lines, string $text, float $x, float $y, float $height, float $module): void
    {
        $text = strtoupper(trim($text));
        $text = preg_replace('/[^A-Z0-9 \-\.\$\/\+%]/', '', $text) ?? '';
        if ($text === '') {
            return;
        }

        $encoded = '*' . $text . '*';
        $patterns = $this->barcodePatterns();
        $commands = ['0 g'];
        $cursor = $x;

        foreach (str_split($encoded) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];
            foreach (str_split($pattern) as $index => $part) {
                $width = $part === 'w' ? $module * 3 : $module;
                if ($index % 2 === 0) {
                    $commands[] = sprintf('%.2F %.2F %.2F %.2F re f', $cursor, $y, $width, $height);
                }
                $cursor += $width;
            }
            $cursor += $module;
        }

        $lines[] = implode("\n", $commands);
        $this->text($lines, $x, $y - 10, 6, $text, false, [15, 23, 42]);
    }

    private function barcodePatterns(): array
    {
        return [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '*' => 'nwnnwnwnn',
            '$' => 'nwnwnwnnn', '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn',
        ];
    }

    private function addLink(float $x, float $y, float $w, float $h, string $url): void
    {
        $url = trim($url);
        if ($url === '') {
            return;
        }

        $this->annotations[] = [
            'x1' => $x,
            'y1' => $y,
            'x2' => $x + $w,
            'y2' => $y + $h,
            'url' => $url,
        ];
    }

    private function render(string $content, string $title): string
    {
        $annotationObjects = [];
        $annotationRefs = [];
        $annotationStart = 7;
        foreach ($this->annotations as $index => $annotation) {
            $objectNumber = $annotationStart + $index;
            $annotationRefs[] = $objectNumber . ' 0 R';
            $annotationObjects[] = sprintf(
                '<< /Type /Annot /Subtype /Link /Rect [%.2F %.2F %.2F %.2F] /Border [0 0 0] /A << /S /URI /URI (%s) >> >>',
                $annotation['x1'],
                $annotation['y1'],
                $annotation['x2'],
                $annotation['y2'],
                $this->escape((string) $annotation['url'])
            );
        }

        $annots = $annotationRefs !== [] ? ' /Annots [' . implode(' ', $annotationRefs) . ']' : '';
        $infoObjectNumber = 7 + count($annotationObjects);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R' . $annots . ' >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
        foreach ($annotationObjects as $object) {
            $objects[] = $object;
        }
        $objects[] = '<< /Title (' . $this->escape($title) . ') /Producer (Sistema Servicio Tecnico) >>';

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        foreach ($objects as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R /Info {$infoObjectNumber} 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

        return $pdf;
    }

    private function escape(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        $text = $converted !== false ? $converted : $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
