<?php
use App\Core\Auth;
use App\Core\Session;

$user = Auth::user();
$success = Session::flash('success');
$error = Session::flash('error');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e($title ?? 'Servicio Tecnico') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260614-table-search2') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/crystal.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/dark.css') . '?v=20260614-selects') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/live.css')) ?>" rel="stylesheet">
    <script>
        (function () {
            var u = <?= json_encode($user['name'] ?? 'guest') ?>;
            var key = 'tecnico-theme:' + u;
            var t = localStorage.getItem(key) || localStorage.getItem('tecnico-theme') || '';
            if (t) { document.documentElement.setAttribute('data-theme', t); }
            window.__themeKey = key;
        })();
    </script>
</head>
<body>
<div class="sidebar-backdrop" data-sidebar-close></div>
<div class="app-shell">
    <aside class="sidebar" id="app-sidebar">
        <div class="brand">
            <div class="brand-mark"></div>
            <div>
                <div>Servicio Tecnico</div>
                <small class="text-white-50">Gestion de reparaciones</small>
            </div>
            <button class="sidebar-close" type="button" aria-label="Cerrar menu" data-sidebar-close>&times;</button>
        </div>
        <nav class="nav flex-column">
            <a class="nav-link <?= e(is_active('/')) ?>" href="<?= e(url('/')) ?>">Dashboard</a>
            <a class="nav-link <?= e(is_active('/clientes')) ?>" href="<?= e(url('/clientes')) ?>">Clientes</a>
            <a class="nav-link <?= e(is_active('/equipos')) ?>" href="<?= e(url('/equipos')) ?>">Equipos</a>
            <a class="nav-link <?= e(is_active('/ordenes')) ?>" href="<?= e(url('/ordenes')) ?>">Ordenes</a>
            <a class="nav-link <?= e(is_active('/entregas')) ?>" href="<?= e(url('/entregas')) ?>">Entregas</a>
            <a class="nav-link <?= e(is_active('/inventario')) ?>" href="<?= e(url('/inventario')) ?>">Inventario</a>
            <a class="nav-link <?= e(is_active('/garantias')) ?>" href="<?= e(url('/garantias')) ?>">Garantias</a>
            <a class="nav-link <?= e(is_active('/reportes')) ?>" href="<?= e(url('/reportes')) ?>">Reportes</a>
            <a class="nav-link <?= e(is_active('/configuracion')) ?>" href="<?= e(url('/configuracion')) ?>">Configuracion</a>
            <a class="nav-link <?= e(is_active('/usuarios')) ?>" href="<?= e(url('/usuarios')) ?>">Usuarios y roles</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="topbar-title">
                <button class="mobile-menu-toggle btn btn-outline-dark btn-sm" type="button" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-toggle>Menu</button>
                <h1 class="h3 mb-1"><?= e($title ?? '') ?></h1>
                <div class="text-muted">Operacion diaria del taller</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <form class="d-none d-md-block" action="<?= e(url('/ordenes')) ?>">
                    <input class="form-control" name="q" placeholder="Buscar folio, cliente o telefono">
                </form>
                <span class="badge text-bg-light"><?= e($user['name'] ?? 'Usuario') ?></span>
                <div class="dropdown theme-switcher">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Tema</button>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 230px;">
                        <div class="fw-bold small text-muted px-1 mb-1">Tema de diseno</div>
                        <label class="dropdown-item d-flex align-items-center gap-2">
                            <input type="radio" name="theme-choice" value="" class="form-check-input m-0"> Original (Aqua glass)
                        </label>
                        <label class="dropdown-item d-flex align-items-center gap-2">
                            <input type="radio" name="theme-choice" value="crystal" class="form-check-input m-0"> Crystal e.liquid
                        </label>
                        <label class="dropdown-item d-flex align-items-center gap-2">
                            <input type="radio" name="theme-choice" value="dark" class="form-check-input m-0"> Dark
                        </label>
                        <label class="dropdown-item d-flex align-items-center gap-2">
                            <input type="radio" name="theme-choice" value="live" class="form-check-input m-0"> Live
                        </label>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-dark btn-sm">Salir</button>
                </form>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= e($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= e($error) ?></div>
        <?php endif; ?>

        <?= $content ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js') . '?v=20260614-table-search2') ?>"></script>
<script src="<?= e(asset('js/theme-switcher.js')) ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
