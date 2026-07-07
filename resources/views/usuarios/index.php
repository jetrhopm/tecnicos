<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128100;">Usuarios y roles</h2>
        <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/usuarios/create')) ?>">Nuevo usuario</a>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Nombre</th><th>Email</th><th>Roles</th><th>Estatus</th></tr></thead>
            <tbody>
            <?php foreach ($usuarios as $usuario): ?>
                <tr>
                    <td><?= e($usuario['name']) ?></td>
                    <td><?= e($usuario['email']) ?></td>
                    <td><?= e($usuario['roles']) ?></td>
                    <td><span class="badge text-bg-light"><?= e($usuario['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
