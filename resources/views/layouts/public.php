<?php
use App\Core\Session;
$success = Session::flash('success');
$error = Session::flash('error');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Consulta') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260614-table-search2') ?>" rel="stylesheet">
</head>
<body>
<main class="public-wrap">
    <section class="glass-card w-100" style="max-width: 980px;">
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?= $content ?>
    </section>
</main>
<script src="<?= e(asset('js/app.js') . '?v=20260614-table-search2') ?>"></script>
</body>
</html>
