<?php $isEdit = !empty($cliente); ?>
<form class="glass-card" method="post" action="<?= e($isEdit ? url('/clientes/' . $cliente['id']) : url('/clientes')) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1" data-icon="&#128100;"><?= $isEdit ? 'Editar cliente' : 'Nuevo cliente' ?></h2>
            <p class="text-muted mb-0">Datos de contacto e informacion administrativa del cliente.</p>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" data-icon="&#128100;">Nombre completo</label>
            <input class="form-control" name="nombre_completo" value="<?= e($cliente['nombre_completo'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" data-icon="&#9742;">Telefono</label>
            <input class="form-control" name="telefono" value="<?= e($cliente['telefono'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label" data-icon="&#128241;">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="<?= e($cliente['whatsapp'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" data-icon="&#9993;">Email</label>
            <input class="form-control" type="email" name="email" value="<?= e($cliente['email'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label" data-icon="&#8962;">Domicilio</label>
            <input class="form-control" name="domicilio" value="<?= e($cliente['domicilio'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" data-icon="&#9679;">Ciudad</label>
            <input class="form-control" name="ciudad" value="<?= e($cliente['ciudad'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" data-icon="&#9679;">Estado</label>
            <input class="form-control" name="estado" value="<?= e($cliente['estado'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" data-icon="&#35;">CP</label>
            <input class="form-control" name="codigo_postal" value="<?= e($cliente['codigo_postal'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" data-icon="&#35;">RFC</label>
            <input class="form-control" name="rfc" value="<?= e($cliente['rfc'] ?? '') ?>">
        </div>
        <div class="col-md-12">
            <label class="form-label" data-icon="&#9998;">Notas internas</label>
            <textarea class="form-control" name="notas_internas" rows="3"><?= e($cliente['notas_internas'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label" data-icon="&#9679;">Estatus</label>
            <select class="form-select" name="estatus">
                <?php foreach (['activo','inactivo'] as $estatus): ?>
                    <option value="<?= e($estatus) ?>" <?= (($cliente['estatus'] ?? 'activo') === $estatus) ? 'selected' : '' ?>><?= e($estatus) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar</button>
        <a class="btn btn-outline-dark" data-icon="&#10005;" href="<?= e(url('/clientes')) ?>">Cancelar</a>
    </div>
</form>
