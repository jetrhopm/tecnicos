<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Impresion') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260707-print-mobile') ?>" rel="stylesheet">
</head>
<body>
<main class="container py-4">
    <div class="print-toolbar no-print">
        <div>
            <strong>Vista de impresion</strong>
            <div class="small text-muted">Si tu celular no abre el dialogo automaticamente, toca Imprimir.</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
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
