<div class="glass-card mb-3">
    <form class="row g-2 align-items-end">
        <div class="col-md-5">
            <label class="form-label">Buscar</label>
            <input class="form-control" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Folio, cliente o telefono" data-table-search-source>
        </div>
        <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select class="form-select" name="estado">
                <option value="">Todos</option>
                <?php foreach ($estados as $estado): ?>
                    <option value="<?= e($estado) ?>" <?= (($_GET['estado'] ?? '') === $estado) ? 'selected' : '' ?>><?= e($estado) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Prioridad</label>
            <select class="form-select" name="prioridad">
                <option value="">Todas</option>
                <?php foreach (['baja','normal','alta','urgente'] as $prioridad): ?>
                    <option value="<?= e($prioridad) ?>" <?= (($_GET['prioridad'] ?? '') === $prioridad) ? 'selected' : '' ?>><?= e($prioridad) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-grid">
            <button class="btn btn-outline-dark">Filtrar</button>
        </div>
    </form>
</div>
<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Ordenes de servicio</h2>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/ordenes/create')) ?>">Nueva orden</a>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Folio</th><th>Cliente</th><th>Telefono</th><th>Equipo</th><th>Prioridad</th><th>Estado</th><th>Recepcion</th><th>Saldo</th></tr></thead>
            <tbody>
            <?php if (empty($ordenes)): ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">No se encontraron ordenes con esos filtros.</td>
                </tr>
            <?php endif; ?>
            <?php foreach ($ordenes as $orden): ?>
                <tr>
                    <td><a href="<?= e(url('/ordenes/' . $orden['id'])) ?>"><?= e($orden['folio']) ?></a></td>
                    <td><?= e($orden['cliente_nombre']) ?></td>
                    <td><?= e($orden['cliente_telefono']) ?></td>
                    <td><?= e(trim($orden['equipo_marca'] . ' ' . $orden['equipo_modelo']) ?: $orden['equipo_tipo']) ?></td>
                    <td><span class="badge text-bg-light"><?= e($orden['prioridad']) ?></span></td>
                    <td><span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span></td>
                    <td><?= e(fechaHumana($orden['fecha_recepcion'])) ?></td>
                    <td><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
