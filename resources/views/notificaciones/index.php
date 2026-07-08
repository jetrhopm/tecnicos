<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128276;">Notificaciones</h2>
        <?php if (!empty($notificaciones)): ?>
            <form method="post" action="<?= e(url('/notificaciones/leer-todas')) ?>" class="m-0">
                <?= csrf_field() ?>
                <button class="btn btn-outline-dark btn-sm" type="submit">Marcar todas como leidas</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (empty($notificaciones)): ?>
        <p class="text-muted mb-0">No tienes notificaciones.</p>
    <?php else: ?>
        <div class="timeline">
            <?php
            $iconos = ['orden_nueva' => "\u{1F4CB}", 'cotizacion_autorizada' => "\u{2705}", 'stock_bajo' => "\u{1F4E6}"];
            ?>
            <?php foreach ($notificaciones as $n): ?>
                <a class="timeline-item notif-item d-block text-decoration-none<?= $n['leida'] ? '' : ' is-unread' ?>" href="<?= e(url('/notificaciones/' . $n['id'])) ?>">
                    <strong><?= e(($iconos[$n['tipo']] ?? "\u{1F514}") . ' ' . $n['titulo']) ?></strong>
                    <?php if (!empty($n['mensaje'])): ?><div class="small"><?= e($n['mensaje']) ?></div><?php endif; ?>
                    <div class="small text-muted"><?= e(fechaHumana($n['created_at'])) ?><?= $n['leida'] ? '' : ' &middot; nueva' ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
