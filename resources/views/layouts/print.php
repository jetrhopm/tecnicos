<?php
/*
 * La vista puede definir $printSize ('carta'|'80'|'56') para fijar el tamano
 * de papel del documento; por defecto usa carta.
 */
$printSize = $printSize ?? 'carta';
$pageSize = match ((string) $printSize) {
    '80' => '80mm auto',
    '56' => '56mm auto',
    'etiqueta' => '62mm 32mm',
    default => 'letter',
};
$pageMargin = match ((string) $printSize) {
    'carta' => '10mm',
    '56', 'etiqueta' => '2mm',
    default => '3mm',
};

// Ruta actual sin query, para poder cambiar de formato desde la barra.
$printPath = strtok((string) ($_SERVER['REQUEST_URI'] ?? ''), '?');
$printFormatos = ['carta' => 'Carta', '80' => 'Ticket 80 mm', '56' => 'Ticket 56 mm'];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Impresion') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260707-print-mobile') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/print.css') . '?v=20260708-barcode') ?>" rel="stylesheet">
    <style>@page { size: <?= $pageSize ?>; margin: <?= $pageMargin ?>; }</style>
</head>
<body>
<main class="container py-4">
    <div class="print-toolbar no-print">
        <div>
            <strong>Vista de impresion</strong>
            <div class="small text-muted">Elige el formato y toca Imprimir. Para ticket usa impresora termica.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <?php if (isset($printFormatos[(string) $printSize])): ?>
                <div class="btn-group btn-group-sm" role="group" aria-label="Formato de impresion">
                    <?php foreach ($printFormatos as $fmt => $etiqueta): ?>
                        <a class="btn <?= (string) $printSize === (string) $fmt ? 'btn-dark' : 'btn-outline-dark' ?>" href="<?= e($printPath . '?formato=' . $fmt) ?>"><?= e($etiqueta) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <button class="btn btn-primary btn-sm" type="button" data-print-now>Imprimir</button>
            <button class="btn btn-outline-dark btn-sm" type="button" data-print-back>Volver</button>
        </div>
    </div>
    <?= $content ?>
</main>
<script>
    (function () {
        function printNow() {
            if (typeof window.print === 'function') {
                window.print();
            }
        }

        var printButton = document.querySelector('[data-print-now]');
        var backButton = document.querySelector('[data-print-back]');

        if (printButton) {
            printButton.addEventListener('click', printNow);
        }

        if (backButton) {
            backButton.addEventListener('click', function () {
            if (window.history.length > 1) {
                window.history.back();
            }
            });
        }

        window.addEventListener('load', function () {
            window.setTimeout(printNow, 450);
        });
    })();
</script>
</body>
</html>
