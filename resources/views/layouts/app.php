<?php
use App\Core\Auth;
use App\Core\Session;
use App\Repositories\UserRepository;
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
$roleNames = [];
if ($user) {
    try {
        $roleNames = array_column((new UserRepository())->rolesForUser((int) $user['id']), 'name');
    } catch (Throwable) {
        $roleNames = [];
    }
}
$rolePriority = ['superadmin', 'admin', 'recepcion', 'tecnico_senior', 'tecnico', 'caja', 'almacen', 'cliente_consulta'];
$primaryRole = 'default';
foreach ($rolePriority as $roleName) {
    if (in_array($roleName, $roleNames, true)) {
        $primaryRole = $roleName;
        break;
    }
}
$quickItem = static fn (string $label, string $href, string $icon, string $module, string $action = 'ver'): array => [
    'label' => $label,
    'href' => $href,
    'icon' => $icon,
    'module' => $module,
    'action' => $action,
];
$quickNavByRole = [
    'superadmin' => [
        $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
        $quickItem('Caja', '/caja', "\u{1F4B5}", 'caja', 'ver'),
        $quickItem('Venta', '/punto-venta', "\u{1F6D2}", 'punto_venta', 'ver'),
        $quickItem('Reportes', '/reportes', "\u{1F4CA}", 'reportes', 'ver'),
    ],
    'admin' => [
        $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
        $quickItem('Caja', '/caja', "\u{1F4B5}", 'caja', 'ver'),
        $quickItem('Venta', '/punto-venta', "\u{1F6D2}", 'punto_venta', 'ver'),
        $quickItem('Reportes', '/reportes', "\u{1F4CA}", 'reportes', 'ver'),
    ],
    'recepcion' => [
        $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
        $quickItem('Entregar', '/entregas', "\u{1F4E6}", 'ordenes', 'ver'),
        $quickItem('Clientes', '/clientes', "\u{1F464}", 'clientes', 'ver'),
        $quickItem('Ordenes', '/ordenes', "\u{1F4CB}", 'ordenes', 'ver'),
    ],
    'tecnico_senior' => [
        $quickItem('Ordenes', '/ordenes', "\u{1F527}", 'ordenes', 'ver'),
        $quickItem('Agenda', '/agenda', "\u{1F4C5}", 'agenda', 'ver'),
        $quickItem('Invent.', '/inventario', "\u{1F9F0}", 'inventario', 'ver'),
        $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
    ],
    'tecnico' => [
        $quickItem('Ordenes', '/ordenes', "\u{1F527}", 'ordenes', 'ver'),
        $quickItem('Agenda', '/agenda', "\u{1F4C5}", 'agenda', 'ver'),
        $quickItem('Invent.', '/inventario', "\u{1F9F0}", 'inventario', 'ver'),
        $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
    ],
    'caja' => [
        $quickItem('Corte', '/caja', "\u{1F4B5}", 'caja', 'ver'),
        $quickItem('Venta', '/punto-venta', "\u{1F6D2}", 'punto_venta', 'ver'),
        $quickItem('Entregar', '/entregas', "\u{1F4E6}", 'ordenes', 'ver'),
        $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
    ],
    'almacen' => [
        $quickItem('Invent.', '/inventario', "\u{1F9F0}", 'inventario', 'ver'),
        $quickItem('Proveed.', '/proveedores', "\u{1F69A}", 'proveedores', 'ver'),
        $quickItem('Ordenes', '/ordenes', "\u{1F4CB}", 'ordenes', 'ver'),
        $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
    ],
    'cliente_consulta' => [
        $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
        $quickItem('Ordenes', '/ordenes', "\u{1F4CB}", 'ordenes', 'ver'),
    ],
    'default' => [
        $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
        $quickItem('Ordenes', '/ordenes', "\u{1F4CB}", 'ordenes', 'ver'),
        $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
        $quickItem('Entregar', '/entregas', "\u{1F4E6}", 'ordenes', 'ver'),
    ],
];
$fallbackQuickItems = [
    $quickItem('Dash', '/', "\u{2302}", 'dashboard', 'ver'),
    $quickItem('Ordenes', '/ordenes', "\u{1F4CB}", 'ordenes', 'ver'),
    $quickItem('Orden', '/ordenes/create', "\u{2795}", 'ordenes', 'crear'),
    $quickItem('Entregar', '/entregas', "\u{1F4E6}", 'ordenes', 'ver'),
    $quickItem('Venta', '/punto-venta', "\u{1F6D2}", 'punto_venta', 'ver'),
    $quickItem('Caja', '/caja', "\u{1F4B5}", 'caja', 'ver'),
];
$mobileQuickNav = [];
foreach (array_merge($quickNavByRole[$primaryRole] ?? $quickNavByRole['default'], $fallbackQuickItems) as $item) {
    $key = $item['href'];
    if (isset($mobileQuickNav[$key]) || !Auth::can($item['module'], $item['action'])) {
        continue;
    }
    $mobileQuickNav[$key] = $item;
    if (count($mobileQuickNav) >= 4) {
        break;
    }
}
$mobileQuickNav = array_values($mobileQuickNav);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(\App\Core\Csrf::token()) ?>">
    <title><?= e(($title ?? '') !== '' ? ($title . ' | ' . $systemName) : $systemName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/app.css') . '?v=20260708-mobile-quick-nav') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/crystal.css') . '?v=20260708-mobile-quick-nav') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/dark.css') . '?v=20260708-mobile-quick-nav') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/live.css') . '?v=20260708-mobile-quick-nav') ?>" rel="stylesheet">
    <link href="<?= e(asset('css/themes/blueprint.css') . '?v=20260708-mobile-quick-nav') ?>" rel="stylesheet">
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
            <?php foreach ([
                ['Dashboard', '/', 'dashboard'],
                ['Clientes', '/clientes', 'clientes'],
                ['Equipos', '/equipos', 'equipos'],
                ['Ordenes', '/ordenes', 'ordenes'],
                ['Entregas', '/entregas', 'ordenes'],
                ['Punto de venta', '/punto-venta', 'punto_venta'],
                ['Caja', '/caja', 'caja'],
                ['Agenda', '/agenda', 'agenda'],
                ['Inventario', '/inventario', 'inventario'],
                ['Proveedores', '/proveedores', 'proveedores'],
                ['Garantias', '/garantias', 'garantias'],
                ['Reportes', '/reportes', 'reportes'],
                ['Configuracion', '/configuracion', 'configuracion'],
                ['Usuarios y roles', '/usuarios', 'usuarios'],
            ] as [$label, $href, $module]): ?>
                <?php if (Auth::can($module, 'ver')): ?>
                    <a class="nav-link <?= e(is_active($href)) ?>" href="<?= e(url($href)) ?>"><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
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
<?php if (!empty($mobileQuickNav)): ?>
    <nav class="mobile-quick-nav" aria-label="Accesos rapidos por rol">
        <?php foreach ($mobileQuickNav as $item): ?>
            <a class="mobile-quick-nav__item <?= e(is_active($item['href'])) ?>" href="<?= e(url($item['href'])) ?>">
                <span class="mobile-quick-nav__icon" aria-hidden="true"><?= e($item['icon']) ?></span>
                <span class="mobile-quick-nav__label"><?= e($item['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/app.js') . '?v=20260708-pos-ticket-draft') ?>"></script>
<script src="<?= e(asset('js/theme-switcher.js') . '?v=20260707-theme-sync') ?>"></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
    <script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
