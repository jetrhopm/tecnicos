<?php

declare(strict_types=1);

function codigoBarras39Svg(string $texto, int $alto = 58, int $modulo = 2): string
{
    $texto = strtoupper(trim($texto));
    $texto = preg_replace('/[^A-Z0-9 \-\.\$\/\+%]/', '', $texto) ?? '';
    if ($texto === '') {
        return '';
    }

    $texto = '*' . $texto . '*';

    $patrones = [
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

    $ancho = 20;
    foreach (str_split($texto) as $char) {
        $patron = $patrones[$char] ?? $patrones['-'];
        foreach (str_split($patron) as $parte) {
            $ancho += ($parte === 'w' ? $modulo * 3 : $modulo);
        }
        $ancho += $modulo;
    }

    $x = 10;
    $barras = '';
    foreach (str_split($texto) as $char) {
        $patron = $patrones[$char] ?? $patrones['-'];
        foreach (str_split($patron) as $i => $parte) {
            $w = $parte === 'w' ? $modulo * 3 : $modulo;
            if ($i % 2 === 0) {
                $barras .= '<rect x="' . $x . '" y="8" width="' . $w . '" height="' . $alto . '" fill="#000"/>';
            }
            $x += $w;
        }
        $x += $modulo;
    }

    $humano = e(trim($texto, '*'));
    return '<svg class="barcode" xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . ($alto + 28) . '" viewBox="0 0 ' . $ancho . ' ' . ($alto + 28) . '" role="img" aria-label="' . $humano . '">' .
        '<rect width="100%" height="100%" fill="#fff"/>' .
        $barras .
        '<text x="50%" y="' . ($alto + 22) . '" text-anchor="middle" font-family="monospace" font-size="13" fill="#000">' . $humano . '</text>' .
        '</svg>';
}
