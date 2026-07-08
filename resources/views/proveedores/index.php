<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <h2 class="h5 mb-0" data-icon="&#127981;">Proveedores</h2>
        <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/proveedores/create')) ?>">Nuevo proveedor</a>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= e(url('/proveedores')) ?>">
        <div class="col-md-10"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, contacto o telefono"></div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-dark" data-icon="&#128269;">Buscar</button></div>
    </form>

    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Proveedor</th><th>Contacto</th><th>Telefono</th><th>Email</th><th>Estatus</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($proveedores as $p): ?>
                <tr>
                    <td><?= e($p['nombre']) ?></td>
                    <td><?= e($p['contacto'] ?: '-') ?></td>
                    <td><?= e($p['telefono'] ?: '-') ?></td>
                    <td><?= e($p['email'] ?: '-') ?></td>
                    <td><span class="badge <?= $p['estatus'] === 'activo' ? 'text-bg-light' : 'text-bg-secondary' ?>"><?= e($p['estatus']) ?></span></td>
                    <td class="text-end"><a class="btn btn-outline-dark btn-sm" data-icon="&#9998;" href="<?= e(url('/proveedores/' . $p['id'] . '/edit')) ?>">Editar</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($proveedores)): ?>
                <tr><td colspan="6" class="text-muted">No hay proveedores registrados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
