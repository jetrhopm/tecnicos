<?php
$isEdit = !empty($usuario);
$action = $isEdit ? url('/usuarios/' . $usuario['id']) : url('/usuarios');
$val = static fn (string $key, string $default = ''): string => (string) ($usuario[$key] ?? $default);
?>
<form class="glass-card" method="post" action="<?= e($action) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1" data-icon="&#128100;"><?= $isEdit ? 'Editar usuario' : 'Nuevo usuario' ?></h2>
            <p class="text-muted mb-0"><?= $isEdit ? 'Actualiza perfil, estatus, roles o restablece la contrasena.' : 'Crea acceso administrativo y asigna roles operativos.' ?></p>
        </div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label" for="name" data-icon="&#128100;">Nombre</label>
            <input class="form-control" id="name" name="name" value="<?= e($val('name')) ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="email" data-icon="&#9993;">Email</label>
            <input class="form-control" id="email" type="email" name="email" value="<?= e($val('email')) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="password" data-icon="&#128274;"><?= $isEdit ? 'Nueva contrasena' : 'Contrasena' ?></label>
            <input class="form-control" id="password" type="password" name="password" <?= $isEdit ? '' : 'required' ?>>
            <?php if ($isEdit): ?><div class="form-text">Dejala vacia para conservar la actual.</div><?php endif; ?>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="phone" data-icon="&#9742;">Telefono</label>
            <input class="form-control" id="phone" name="phone" value="<?= e($val('phone')) ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label" for="status" data-icon="&#9679;">Estatus</label>
            <select class="form-select" id="status" name="status">
                <?php foreach (['activo','inactivo','bloqueado'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= $val('status', 'activo') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12">
            <label class="form-label" data-icon="&#128737;">Roles</label>
            <div class="row g-2">
                <?php foreach ($roles as $role): ?>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-check glass-card py-2 mb-0">
                            <input class="form-check-input" type="checkbox" name="roles[]" value="<?= e($role['id']) ?>" <?= in_array((int) $role['id'], $roleIds ?? [], true) ? 'checked' : '' ?>>
                            <span class="form-check-label"><?= e($role['label']) ?></span>
                            <small class="d-block text-muted"><?= e($role['name']) ?></small>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" data-icon="&#128190;"><?= $isEdit ? 'Guardar cambios' : 'Crear usuario' ?></button>
        <a class="btn btn-outline-dark" data-icon="&#10005;" href="<?= e(url('/usuarios')) ?>">Cancelar</a>
    </div>
</form>
