<?php $isEdit = !empty($cliente); ?>
<form class="glass-card" method="post" action="<?= e($isEdit ? url('/clientes/' . $cliente['id']) : url('/clientes')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Nombre completo</label>
            <input class="form-control" name="nombre_completo" value="<?= e($cliente['nombre_completo'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">Telefono</label>
            <input class="form-control" name="telefono" value="<?= e($cliente['telefono'] ?? '') ?>" required>
        </div>
        <div class="col-md-3">
            <label class="form-label">WhatsApp</label>
            <input class="form-control" name="whatsapp" value="<?= e($cliente['whatsapp'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Email</label>
            <input class="form-control" type="email" name="email" value="<?= e($cliente['email'] ?? '') ?>">
        </div>
        <div class="col-md-8">
            <label class="form-label">Domicilio</label>
            <input class="form-control" name="domicilio" value="<?= e($cliente['domicilio'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Ciudad</label>
            <input class="form-control" name="ciudad" value="<?= e($cliente['ciudad'] ?? '') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label">Estado</label>
            <input class="form-control" name="estado" value="<?= e($cliente['estado'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">CP</label>
            <input class="form-control" name="codigo_postal" value="<?= e($cliente['codigo_postal'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label">RFC</label>
            <input class="form-control" name="rfc" value="<?= e($cliente['rfc'] ?? '') ?>">
        </div>
        <div class="col-md-12">
            <label class="form-label">Notas internas</label>
            <textarea class="form-control" name="notas_internas" rows="3"><?= e($cliente['notas_internas'] ?? '') ?></textarea>
        </div>
        <div class="col-md-3">
            <label class="form-label">Estatus</label>
            <select class="form-select" name="estatus">
                <?php foreach (['activo','inactivo'] as $estatus): ?>
                    <option value="<?= e($estatus) ?>" <?= (($cliente['estatus'] ?? 'activo') === $estatus) ? 'selected' : '' ?>><?= e($estatus) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-dark" href="<?= e(url('/clientes')) ?>">Cancelar</a>
    </div>
</form>
