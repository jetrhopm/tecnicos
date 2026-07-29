<div class="glass-card">
    <?php $puedeAdministrarLicencia = (new \App\Services\LicenciaService())->usuarioActualEsLicenciante(); ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128100;">Usuarios y roles</h2>
        <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/usuarios/create')) ?>">Nuevo usuario</a>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Nombre</th><th>Email</th><th>Telefono</th><th>Roles</th><th>Estatus</th><th>Ultimo acceso</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <?php $esLicenciante = str_contains(',' . (string) ($usuario['role_names'] ?? '') . ',', ',licenciante,'); ?>
                <tr>
                    <td><?= e($usuario['name']) ?></td>
                    <td><?= e($usuario['email']) ?></td>
                    <td><?= e($usuario['phone'] ?: '-') ?></td>
                    <td><?= e($usuario['roles']) ?></td>
                    <td><span class="badge text-bg-light"><?= e($usuario['status']) ?></span></td>
                    <td><?= e($usuario['last_login_at'] ? fechaHumana($usuario['last_login_at']) : '-') ?></td>
                    <td class="text-end">
                        <div class="d-flex gap-2 justify-content-end flex-wrap">
                            <?php if (!$esLicenciante || $puedeAdministrarLicencia): ?>
                                <a class="btn btn-outline-dark btn-sm" data-icon="&#9998;" href="<?= e(url('/usuarios/' . $usuario['id'] . '/edit')) ?>">Editar</a>
                            <?php endif; ?>
                            <?php if (!$esLicenciante || $puedeAdministrarLicencia): ?>
                                <form method="post" action="<?= e(url('/usuarios/' . $usuario['id'] . '/status')) ?>">
                                    <?= csrf_field() ?>
                                    <div class="input-group input-group-sm">
                                        <select class="form-select" name="status">
                                            <?php foreach (['activo','inactivo','bloqueado'] as $status): ?>
                                                <option value="<?= e($status) ?>" <?= $usuario['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-outline-dark" data-icon="&#10003;">OK</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Protegido</span>
                            <?php endif; ?>
                            <?php if ($puedeAdministrarLicencia && !$esLicenciante): ?>
                                <form method="post" action="<?= e(url('/usuarios/' . $usuario['id'] . '/delete')) ?>" onsubmit="return confirm('¿Eliminar este usuario?');">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-outline-danger btn-sm" data-icon="&#128465;">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
