<?php if (!$equipo): ?>
    <div class="alert alert-warning">Equipo no encontrado.</div>
<?php else: ?>
<div class="glass-card">
    <div class="d-flex justify-content-between align-items-start">
        <div>
            <h2 class="h5" data-icon="&#128421;"><?= e(trim($equipo['marca'] . ' ' . $equipo['modelo']) ?: $equipo['tipo']) ?></h2>
            <p class="text-muted"><?= e($equipo['cliente_nombre']) ?></p>
        </div>
        <a class="btn btn-outline-dark btn-sm" data-icon="&#9998;" href="<?= e(url('/equipos/' . $equipo['id'] . '/edit')) ?>">Editar</a>
    </div>
    <div class="row g-3">
        <div class="col-md-3"><strong>Tipo</strong><br><?= e($equipo['tipo']) ?></div>
        <div class="col-md-3"><strong>Serie</strong><br><?= e($equipo['numero_serie'] ?: '-') ?></div>
        <div class="col-md-3"><strong>IMEI</strong><br><?= e($equipo['imei'] ?: '-') ?></div>
        <div class="col-md-3"><strong>Color</strong><br><?= e($equipo['color'] ?: '-') ?></div>
        <div class="col-md-4"><strong>Accesorios</strong><p><?= e($equipo['accesorios_recibidos'] ?: '-') ?></p></div>
        <div class="col-md-4"><strong>Estado fisico</strong><p><?= e($equipo['estado_fisico'] ?: '-') ?></p></div>
        <div class="col-md-4"><strong>Observaciones</strong><p><?= e($equipo['observaciones'] ?: '-') ?></p></div>
    </div>
</div>
<?php endif; ?>
