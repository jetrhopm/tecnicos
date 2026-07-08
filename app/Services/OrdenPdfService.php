<?php

declare(strict_types=1);

namespace App\Services;

final class OrdenPdfService
{
    private const PAGE_WIDTH = 612;
    private const PAGE_HEIGHT = 792;

    /**
     * Genera el PDF de recepcion con el mismo enfoque visual del comprobante nuevo:
     * cabecera del negocio, ficha en recuadros, patron/clave, importes y clave de entrega.
     * No guarda archivo en disco ni base de datos; el controlador decide si lo muestra/envia.
     */
    public function recepcion(array $orden, ?array $diagnostico = null, ?array $cotizacion = null, array $config = []): string
    {
        $lines = [];

        $folio = (string) ($orden['folio'] ?? '');
        $token = (string) ($orden['token_publico'] ?? '');
        $publicUrl = absolute_url('/consulta/' . rawurlencode($folio) . '/' . rawurlencode($token));
        $pdfUrl = absolute_url('/consulta/' . rawurlencode($folio) . '/' . rawurlencode($token) . '/pdf');

        $tipo = ucfirst((string) ($orden['equipo_tipo'] ?? ''));
        $equipo = trim($tipo . ' ' . (string) ($orden['equipo_marca'] ?? '') . ' ' . (string) ($orden['equipo_modelo'] ?? '')) ?: 'Equipo';
        $imeiSerie = trim((string) ($orden['imei'] ?? '') . (($orden['imei'] ?? '') && ($orden['numero_serie'] ?? '') ? ' / ' : '') . (string) ($orden['numero_serie'] ?? ''));
        $desbloqueo = $this->unlockData((string) ($orden['password_equipo'] ?? ''));

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

        $this->pageBackground($lines);
        $this->header($lines, $negocio, $telefono, $whatsapp, $direccion, $folio);

        $this->text($lines, 44, 681, 14, 'COMPROBANTE DE RECEPCION Y RETIRO', true, [14, 55, 88]);
        $this->text($lines, 44, 664, 8, 'Presenta esta nota para retirar el equipo. La clave de entrega valida la liberacion.', false, [74, 85, 104]);

        $this->fieldBox($lines, 44, 613, 326, 42, 'Cliente', (string) ($orden['cliente_nombre'] ?? '-'));
        $this->fieldBox($lines, 382, 613, 186, 42, 'Telefono / WhatsApp', $this->firstValue($orden['cliente_whatsapp'] ?? '', $orden['cliente_telefono'] ?? ''));
        $this->fieldBox($lines, 44, 565, 326, 42, 'Direccion', (string) (($orden['cliente_domicilio'] ?? '') ?: '-'));
        $this->fieldBox($lines, 382, 565, 186, 42, 'Correo', (string) (($orden['cliente_email'] ?? '') ?: '-'));

        $this->fieldBox($lines, 44, 517, 164, 42, 'Equipo', $tipo ?: '-');
        $this->fieldBox($lines, 220, 517, 164, 42, 'Marca', (string) (($orden['equipo_marca'] ?? '') ?: '-'));
        $this->fieldBox($lines, 396, 517, 172, 42, 'Modelo', (string) (($orden['equipo_modelo'] ?? '') ?: '-'));

        $this->fieldBox($lines, 44, 445, 252, 64, 'IMEI / Serie', $imeiSerie ?: '-');
        $this->patternBox($lines, 308, 445, 260, 64, $desbloqueo);

        $this->fieldBox($lines, 44, 397, 326, 42, 'Servicio rapido / Plantilla', (string) (($orden['tipo_servicio'] ?? '') ?: '-'));
        $this->fieldBox($lines, 382, 397, 186, 42, 'Accesorios', (string) (($orden['accesorios_recibidos'] ?? '') ?: 'Sin accesorios'));

        $this->multilineBox($lines, 44, 343, 524, 46, 'Falla declarada', (string) (($orden['falla_reportada'] ?? '') ?: '-'), 102, 2);
        $this->multilineBox($lines, 44, 289, 524, 46, 'Observaciones / Senas', (string) (($orden['observaciones_cliente'] ?? '') ?: '-'), 102, 2);
        $this->multilineBox($lines, 44, 235, 524, 46, 'Estado fisico al recibir', (string) (($orden['equipo_estado_fisico'] ?? '') ?: '-'), 102, 2);

        $trabajo = trim((string) ($diagnostico['diagnostico_cliente'] ?? $orden['diagnostico_inicial'] ?? $orden['tipo_servicio'] ?? ''));
        $this->multilineBox($lines, 44, 181, 524, 46, 'Reparacion / Trabajo', $trabajo !== '' ? $trabajo : '-', 102, 2);

        $this->fieldBox($lines, 44, 133, 124, 40, 'Fecha entrada', fechaHumana($orden['fecha_recepcion'] ?? null));
        $this->fieldBox($lines, 178, 133, 124, 40, 'Fecha entrega', fechaHumana($orden['fecha_estimada_entrega'] ?? null));
        $this->fieldBox($lines, 312, 133, 122, 40, 'Estado', (string) (($orden['estado'] ?? '') ?: '-'));
        $this->fieldBox($lines, 444, 133, 124, 40, 'Clave entrega', (string) (($orden['codigo_entrega'] ?? '') ?: '-'));

        $this->termsBox($lines, 44, 71, 312, 54, $condiciones);
        $this->totalsBox($lines, 370, 71, 198, 54, $total, $anticipo, $saldo);

        $this->barcodeBlock($lines, (string) ($orden['codigo_entrega'] ?? ''), 44, 31);
        $this->consultaBox($lines, 224, 31, 188, 36, $publicUrl, $pdfUrl);

        $this->line($lines, 438, 38, 558, 38, [74, 85, 104], 0.6);
        $this->text($lines, 474, 24, 8, 'Firma cliente', false, [74, 85, 104]);

        return $this->render(implode("\n", $lines), 'Orden ' . $folio);
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

    private function text(array &$lines, float $x, float $y, int $size, string $text, bool $bold = false, array $color = [0, 0, 0]): void
    {
        $font = $bold ? 'F2' : 'F1';
        $lines[] = sprintf(
            '%.3F %.3F %.3F rg BT /%s %d Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
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

    private function render(string $content, string $title): string
    {
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_WIDTH . ' ' . self::PAGE_HEIGHT . '] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
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
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R /Info 7 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";

        return $pdf;
    }

    private function escape(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        $text = $converted !== false ? $converted : $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
