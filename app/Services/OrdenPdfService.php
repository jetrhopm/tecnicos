<?php

declare(strict_types=1);

namespace App\Services;

final class OrdenPdfService
{
    private const PAGE_WIDTH = 612;
    private const PAGE_HEIGHT = 792;

    public function recepcion(array $orden, ?array $diagnostico = null, ?array $cotizacion = null): string
    {
        $equipo = trim((string) (($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')))
            ?: (string) ($orden['equipo_tipo'] ?? '');
        $publicUrl = absolute_url('/consulta/' . rawurlencode((string) ($orden['folio'] ?? '')) . '/' . rawurlencode((string) ($orden['token_publico'] ?? '')));
        $pdfUrl = absolute_url('/consulta/' . rawurlencode((string) ($orden['folio'] ?? '')) . '/' . rawurlencode((string) ($orden['token_publico'] ?? '')) . '/pdf');

        $lines = [];
        $this->text($lines, 54, 744, 18, 'Comprobante de recepcion');
        $this->text($lines, 54, 724, 10, 'Sistema Web de Gestion de Servicios Tecnicos y Reparaciones');
        $this->text($lines, 430, 744, 16, 'Folio: ' . (string) ($orden['folio'] ?? ''));

        $y = 690;
        $rows = [
            ['Cliente', (string) ($orden['cliente_nombre'] ?? '')],
            ['Telefono', (string) ($orden['cliente_telefono'] ?? '')],
            ['Equipo', $equipo],
            ['Estado', (string) ($orden['estado'] ?? '')],
            ['Prioridad', (string) ($orden['prioridad'] ?? '')],
            ['Fecha recepcion', fechaHumana($orden['fecha_recepcion'] ?? null)],
            ['Entrega estimada', fechaHumana($orden['fecha_estimada_entrega'] ?? null)],
            ['Tecnico', (string) (($orden['tecnico_nombre'] ?? '') ?: 'Sin asignar')],
            ['Clave entrega', (string) ($orden['codigo_entrega'] ?? '')],
            ['Total', formatearMoneda((float) ($orden['costo_final'] ?? $orden['costo_estimado'] ?? 0))],
            ['Anticipo / pagado', formatearMoneda((float) ($orden['anticipo'] ?? 0))],
            ['Saldo pendiente', formatearMoneda((float) ($orden['saldo_pendiente'] ?? 0))],
        ];

        foreach ($rows as [$label, $value]) {
            $this->text($lines, 54, $y, 9, $label . ':', true);
            $this->text($lines, 170, $y, 9, $value);
            $y -= 18;
        }

        $y -= 8;
        $this->section($lines, 54, $y, 'Falla reportada');
        $y -= 18;
        $y = $this->paragraph($lines, 54, $y, (string) ($orden['falla_reportada'] ?? ''), 96, 5);

        $this->section($lines, 54, $y, 'Diagnostico visible');
        $y -= 18;
        $diagnosticoTexto = (string) ($diagnostico['diagnostico_cliente'] ?? $orden['diagnostico_inicial'] ?? 'Aun no hay diagnostico visible.');
        $y = $this->paragraph($lines, 54, $y, $diagnosticoTexto, 96, 4);

        if ($cotizacion) {
            $this->section($lines, 54, $y, 'Cotizacion');
            $y -= 18;
            $this->text($lines, 54, $y, 9, 'Estado: ' . (string) ($cotizacion['estado'] ?? '-'));
            $this->text($lines, 250, $y, 9, 'Total: ' . formatearMoneda((float) ($cotizacion['total'] ?? 0)));
            $y -= 18;
        }

        $this->section($lines, 54, $y, 'Codigo de barras para entrega');
        $y -= 78;
        $lines[] = $this->barcode39((string) ($orden['codigo_entrega'] ?? ''), 54, $y + 12, 52, 1.35);
        $this->text($lines, 54, $y - 8, 9, 'Presenta esta nota al recoger tu equipo. La clave es necesaria para liberar la entrega.');

        $y -= 34;
        $this->section($lines, 54, $y, 'Consulta del cliente');
        $y -= 17;
        $y = $this->paragraph($lines, 54, $y, $publicUrl, 100, 2, 8);
        $this->text($lines, 54, $y, 8, 'PDF publico: ' . $pdfUrl);

        $this->line($lines, 80, 88, 250, 88);
        $this->line($lines, 360, 88, 530, 88);
        $this->text($lines, 130, 72, 9, 'Recibe');
        $this->text($lines, 415, 72, 9, 'Cliente');

        return $this->render(implode("\n", $lines), 'Orden ' . (string) ($orden['folio'] ?? ''));
    }

    private function section(array &$lines, float $x, float $y, string $title): void
    {
        $this->text($lines, $x, $y, 11, $title, true);
        $this->line($lines, $x, $y - 4, 558, $y - 4);
    }

    private function paragraph(array &$lines, float $x, float $y, string $text, int $chars, int $maxLines, int $size = 9): float
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        $wrapped = array_slice($this->wrap($text !== '' ? $text : '-', $chars), 0, $maxLines);
        foreach ($wrapped as $line) {
            $this->text($lines, $x, $y, $size, $line);
            $y -= 14;
        }

        return $y - 8;
    }

    private function wrap(string $text, int $chars): array
    {
        $parts = explode("\n", wordwrap($text, $chars, "\n", false));
        return array_values(array_filter($parts, static fn (string $part): bool => trim($part) !== ''));
    }

    private function text(array &$lines, float $x, float $y, int $size, string $text, bool $bold = false): void
    {
        $font = $bold ? 'F2' : 'F1';
        $lines[] = sprintf('BT /%s %d Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET', $font, $size, $x, $y, $this->escape($text));
    }

    private function line(array &$lines, float $x1, float $y1, float $x2, float $y2): void
    {
        $lines[] = sprintf('0.75 w %.2F %.2F m %.2F %.2F l S', $x1, $y1, $x2, $y2);
    }

    private function barcode39(string $text, float $x, float $y, float $height, float $module): string
    {
        $text = strtoupper(trim($text));
        $text = preg_replace('/[^A-Z0-9 \-\.\$\/\+%]/', '', $text) ?? '';
        $text = '*' . $text . '*';
        $patterns = $this->barcodePatterns();
        $commands = ['0 g'];
        $cursor = $x;

        foreach (str_split($text) as $char) {
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

        $this->text($commands, $x, $y - 14, 9, trim($text, '*'));
        return implode("\n", $commands);
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
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
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
        $converted = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
        $text = $converted !== false ? $converted : $text;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
