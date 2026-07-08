<?php
$isEdit = !empty($proveedor);
$action = $isEdit ? url('/proveedores/' . $proveedor['id']) : url('/proveedores');
$val = fn (string $k, $def = '') => e((string) ($proveedor[$k] ?? $def));
?>
<form class="glass-card" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#127981;"><?= e($title) ?></h2>
        <a class="btn btn-outline-dark btn-sm" data-icon="&#10005;" href="<?= e(url('/proveedores')) ?>">Cancelar</a>
    </div>

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Nombre</label><input class="form-control" name="nombre" value="<?= $val('nombre') ?>" required></div>
        <div class="col-md-6"><label class="form-label">Contacto</label><input class="form-control" name="contacto" value="<?= $val('contacto') ?>"></div>
        <div class="col-md-4"><label class="form-label" data-icon="&#9742;">Telefono</label><input class="form-control" name="telefono" value="<?= $val('telefono') ?>"></div>
        <div class="col-md-4"><label class="form-label" data-icon="&#9993;">Email</label><input class="form-control" type="email" name="email" value="<?= $val('email') ?>"></div>
        <div class="col-md-4"><label class="form-label">Sitio web</label><input class="form-control" name="sitio_web" value="<?= $val('sitio_web') ?>"></div>
        <div class="col-md-8"><label class="form-label">Domicilio</label><input class="form-control" name="domicilio" value="<?= $val('domicilio') ?>"></div>
        <div class="col-md-4">
            <label class="form-label" data-icon="&#9679;">Estatus</label>
            <select class="form-select" name="estatus">
                <?php foreach (['activo', 'inactivo'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= ($proveedor['estatus'] ?? 'activo') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="3"><?= $val('notas') ?></textarea></div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar proveedor</button>
        <a class="btn btn-outline-dark" href="<?= e(url('/proveedores')) ?>">Cancelar</a>
    </div>
</form>
