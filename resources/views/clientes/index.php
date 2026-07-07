<div class="glass-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-9">
            <label class="form-label" data-icon="&#128269;">Buscar cliente</label>
            <input class="form-control" name="q" value="<?= e($q ?? '') ?>" placeholder="Nombre, telefono, email o folio" data-table-search-source>
        </div>
        <div class="col-md-3 d-grid">
            <button class="btn btn-outline-dark" data-icon="&#128269;">Buscar</button>
        </div>
    </form>
</div>
<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128100;">Clientes registrados</h2>
        <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/clientes/create')) ?>">Nuevo cliente</a>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Cliente</th><th>Telefono</th><th>Email</th><th>Ciudad</th><th>Estatus</th></tr></thead>
            <tbody>
            <?php foreach ($clientes as $cliente): ?>
                <tr>
                    <td><a href="<?= e(url('/clientes/' . $cliente['id'])) ?>"><?= e($cliente['nombre_completo']) ?></a></td>
                    <td><?= e($cliente['telefono']) ?></td>
                    <td><?= e($cliente['email']) ?></td>
                    <td><?= e($cliente['ciudad']) ?></td>
                    <td><span class="badge text-bg-light"><?= e($cliente['estatus']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
