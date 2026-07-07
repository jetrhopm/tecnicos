<?php
use App\Core\Session;
$error = Session::flash('error');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Acceso') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/crystal.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/dark.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/live.css')) ?>" rel="stylesheet">
    <script>
        (function () {
            var t = localStorage.getItem('tecnico-theme') || '';
            if (t) { document.documentElement.setAttribute('data-theme', t); }
        })();
    </script>
</head>
<body>
<main class="guest-wrap">
    <section class="auth-card">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?= $content ?>
    </section>
</main>
</body>
</html>
