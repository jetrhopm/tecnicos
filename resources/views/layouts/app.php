<?php
use App\Core\Auth;
use App\Core\Session;
use App\Services\ConfiguracionService;
use App\Services\NotificacionService;

$user = Auth::user();
$success = Session::flash('success');
$error = Session::flash('error');
$appConfig = require BASE_PATH . '/config/app.php';
$systemName = (string) ($appConfig['name'] ?? 'Servicio Tecnico');
$businessLogo = '';
try {
    $cfg = new ConfiguracionService();
    $systemName = trim((string) $cfg->get('sistema.nombre', $systemName)) ?: $systemName;
    $businessLogo = config_asset_src((string) $cfg->get('negocio.logo_url', ''));
} catch (Throwable) {
    $businessLogo = '';
}

$notifNoLeidas = 0;
$notifItems = [];
if ($user) {
    $notifSvc = new NotificacionService();
    $notifNoLeidas = $notifSvc->contarNoLeidas((int) $user['id']);
    $notifItems = $notifSvc->recientes((int) $user['id']);
}
$notifIconos = [
    'orden_nueva' => "\u{1F4CB}",
    'cotizacion_autorizada' => "\u{2705}",
    'stock_bajo' => "\u{1F4E6}",
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e(($title ?? '') !== '' ? ($title . ' | ' . $systemName) : $systemName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260708-pos-refacciones') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/crystal.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/dark.css') . '?v=20260614-selects') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/live.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/blueprint.css') . '?v=20260707-btns') ?>" rel="stylesheet">
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
            <?php if ($businessLogo !== ''): ?>
                <img class="brand-logo" src="<?= e($businessLogo) ?>" alt="<?= e($systemName) ?>">
            <?php else: ?>
                <div class="brand-mark"></div>
            <?php endif; ?>
            <div>
                <div><?= e($systemName) ?></div>
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
            <a class="nav-link <?= e(is_active('/punto-venta')) ?>" href="<?= e(url('/punto-venta')) ?>">Punto de venta</a>
            <a class="nav-link <?= e(is_active('/agenda')) ?>" href="<?= e(url('/agenda')) ?>">Agenda</a>
            <a class="nav-link <?= e(is_active('/inventario')) ?>" href="<?= e(url('/inventario')) ?>">Inventario</a>
            <a class="nav-link <?= e(is_active('/proveedores')) ?>" href="<?= e(url('/proveedores')) ?>">Proveedores</a>
            <a class="nav-link <?= e(is_active('/garantias')) ?>" href="<?= e(url('/garantias')) ?>">Garantias</a>
            <a class="nav-link <?= e(is_active('/reportes')) ?>" href="<?= e(url('/reportes')) ?>">Reportes</a>
            <a class="nav-link <?= e(is_active('/configuracion')) ?>" href="<?= e(url('/configuracion')) ?>">Configuracion</a>
            <a class="nav-link <?= e(is_active('/usuarios')) ?>" href="<?= e(url('/usuarios')) ?>">Usuarios y roles</a>
        </nav>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="mobile-menu-toggle btn btn-outline-dark btn-sm" type="button" aria-controls="app-sidebar" aria-expanded="false" data-sidebar-toggle>Menu</button>
            <div class="topbar-title">
                <h1 class="h3 mb-1" data-icon="&#9671;"><?= e($title ?? '') ?></h1>
                <div class="text-muted">Operacion diaria del taller</div>
            </div>
            <div class="d-flex align-items-center gap-2 topbar-actions">
                <form class="d-none d-md-block" action="<?= e(url('/ordenes')) ?>">
                    <input class="form-control" name="q" placeholder="Buscar folio, cliente o telefono">
                </form>
                <span class="badge text-bg-light topbar-user"><?= e($user['name'] ?? 'Usuario') ?></span>
                <div class="dropdown">
                    <button class="btn btn-outline-secondary btn-sm notif-bell" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones">
                        &#128276;
                        <?php if ($notifNoLeidas > 0): ?>
                            <span class="notif-badge"><?= e($notifNoLeidas > 9 ? '9+' : (string) $notifNoLeidas) ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 300px;">
                        <div class="d-flex justify-content-between align-items-center px-1 mb-1">
                            <strong class="small">Notificaciones</strong>
                            <?php if ($notifNoLeidas > 0): ?>
                                <form method="post" action="<?= e(url('/notificaciones/leer-todas')) ?>" class="m-0">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-link btn-sm p-0" type="submit">Marcar leidas</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if (empty($notifItems)): ?>
                            <div class="text-muted small px-1 py-2">Sin notificaciones.</div>
                        <?php else: ?>
                            <?php foreach ($notifItems as $n): ?>
                                <a class="dropdown-item notif-item<?= $n['leida'] ? '' : ' is-unread' ?>" href="<?= e(url('/notificaciones/' . $n['id'])) ?>">
                                    <div class="small fw-bold"><?= e(($notifIconos[$n['tipo']] ?? "\u{1F514}") . ' ' . $n['titulo']) ?></div>
                                    <?php if (!empty($n['mensaje'])): ?><div class="small text-muted"><?= e($n['mensaje']) ?></div><?php endif; ?>
                                    <div class="small text-muted"><?= e(fechaHumana($n['created_at'])) ?></div>
                                </a>
                            <?php endforeach; ?>
                            <div class="text-center mt-1"><a class="small" href="<?= e(url('/notificaciones')) ?>">Ver todas</a></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dropdown theme-switcher">
                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Tema" data-icon="&#9681;"><span class="btn-label">Tema</span></button>
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
                        <label class="dropdown-item d-flex align-items-center gap-2">
                            <input type="radio" name="theme-choice" value="blueprint" class="form-check-input m-0"> Blueprint neon
                        </label>
                    </div>
                </div>
                <form method="post" action="<?= e(url('/logout')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-dark btn-sm" aria-label="Salir" data-icon="&#128274;"><span class="btn-label">Salir</span></button>
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
<script src="<?= e(asset('js/app.js') . '?v=20260708-pos-refacciones') ?>"></script>
<script src="<?= e(asset('js/theme-switcher.js') . '?v=20260707-theme-sync') ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
