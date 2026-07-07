<?php

declare(strict_types=1);

/*
 * Utilidades de impresion.
 * patronDesbloqueo(): interpreta el campo password_equipo, que puede venir
 *   como "Patron: 1-4-7-8-9" (patron) o como texto libre (clave / PIN).
 * patronSvg(): dibuja el patron en una mini rejilla 3x3 para el ticket.
 * Son funciones puras: no consultan base de datos.
 */

function patronDesbloqueo(?string $value): ?array
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    if (preg_match('/^patr[oó]n\s*[:=]?\s*(.+)$/iu', $value, $m)) {
        $seq = array_values(array_filter(
            array_map('intval', preg_split('/[^0-9]+/', $m[1]) ?: []),
            static fn (int $n): bool => $n >= 1 && $n <= 9
        ));
        if (count($seq) >= 2) {
            return ['tipo' => 'patron', 'secuencia' => $seq];
        }
    }

    return ['tipo' => 'clave', 'valor' => $value];
}

function patronSvg(array $secuencia, int $px = 120): string
{
    // Posicion fija 1..9 tipo teclado: fila superior 1-2-3, etc.
    $pos = [];
    for ($n = 1; $n <= 9; $n++) {
        $col = ($n - 1) % 3;
        $row = intdiv($n - 1, 3);
        $pos[$n] = [18 + $col * 32, 18 + $row * 32];
    }

    $usados = array_flip($secuencia);
    $puntos = [];
    foreach ($secuencia as $n) {
        if (isset($pos[$n])) {
            $puntos[] = $pos[$n][0] . ',' . $pos[$n][1];
        }
    }

    $svg = '<svg class="patron-svg" viewBox="0 0 100 100" width="' . $px . '" height="' . $px . '" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Patron de desbloqueo">';

    if (count($puntos) >= 2) {
        $svg .= '<polyline points="' . implode(' ', $puntos) . '" fill="none" stroke="#111" stroke-width="2.6" stroke-linejoin="round" stroke-linecap="round"/>';
    }

    for ($n = 1; $n <= 9; $n++) {
        [$x, $y] = $pos[$n];
        if (isset($usados[$n])) {
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="8.5" fill="#111"/>';
            $svg .= '<text x="' . $x . '" y="' . ($y + 3.2) . '" text-anchor="middle" font-family="Arial, sans-serif" font-size="9" font-weight="bold" fill="#fff">' . $n . '</text>';
        } else {
            $svg .= '<circle cx="' . $x . '" cy="' . $y . '" r="3.2" fill="none" stroke="#999" stroke-width="1"/>';
        }
    }

    $svg .= '</svg>';
    return $svg;
}
