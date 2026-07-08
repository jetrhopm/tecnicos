<?php
$isEdit = !empty($refaccion);
$action = $isEdit ? url('/inventario/' . $refaccion['id']) : url('/inventario');
$val = fn (string $k, $def = '') => e((string) ($refaccion[$k] ?? $def));
?>
<form class="glass-card" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128230;"><?= e($title) ?></h2>
        <a class="btn btn-outline-dark btn-sm" data-icon="&#10005;" href="<?= e(url('/inventario')) ?>">Cancelar</a>
    </div>

    <div class="row g-3">
        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Nombre</label><input class="form-control" name="nombre" value="<?= $val('nombre') ?>" required></div>
        <div class="col-md-3"><label class="form-label" data-icon="&#35;">SKU</label><input class="form-control" name="sku" value="<?= $val('sku') ?>" required></div>
        <div class="col-md-3">
            <label class="form-label" data-icon="&#9679;">Estatus</label>
            <select class="form-select" name="estatus">
                <?php foreach (['activo', 'inactivo'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= ($refaccion['estatus'] ?? 'activo') === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-4"><label class="form-label">Categoria</label><input class="form-control" name="categoria" value="<?= $val('categoria') ?>" placeholder="Pantallas, baterias, herramienta..."></div>
        <div class="col-md-4"><label class="form-label">Marca</label><input class="form-control" name="marca" value="<?= $val('marca') ?>"></div>
        <div class="col-md-4"><label class="form-label">Modelo compatible</label><input class="form-control" name="modelo_compatible" value="<?= $val('modelo_compatible') ?>"></div>

        <div class="col-md-4">
            <label class="form-label">Proveedor</label>
            <select class="form-select" name="proveedor_id">
                <option value="">Sin proveedor</option>
                <?php foreach ($proveedores as $prov): ?>
                    <option value="<?= e($prov['id']) ?>" <?= (int) ($refaccion['proveedor_id'] ?? 0) === (int) $prov['id'] ? 'selected' : '' ?>><?= e($prov['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label" data-icon="&#36;">Costo</label><input class="form-control" type="number" step="0.01" name="costo" value="<?= $val('costo', '0') ?>" data-money></div>
        <div class="col-md-4"><label class="form-label" data-icon="&#36;">Precio venta</label><input class="form-control" type="number" step="0.01" name="precio_venta" value="<?= $val('precio_venta', '0') ?>" data-money></div>

        <?php if (!$isEdit): ?>
            <div class="col-md-4"><label class="form-label">Stock inicial</label><input class="form-control" type="number" name="stock_actual" value="0"></div>
        <?php else: ?>
            <div class="col-md-4"><label class="form-label">Stock actual</label><input class="form-control" value="<?= $val('stock_actual') ?>" disabled><div class="form-text">Se ajusta con entradas/salidas.</div></div>
        <?php endif; ?>
        <div class="col-md-4"><label class="form-label">Stock minimo</label><input class="form-control" type="number" name="stock_minimo" value="<?= $val('stock_minimo', '0') ?>"></div>
        <div class="col-md-4"><label class="form-label">Ubicacion</label><input class="form-control" name="ubicacion" value="<?= $val('ubicacion') ?>" placeholder="Estante, cajon..."></div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar refaccion</button>
        <a class="btn btn-outline-dark" href="<?= e(url('/inventario')) ?>">Cancelar</a>
    </div>
</form>
