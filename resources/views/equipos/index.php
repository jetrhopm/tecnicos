<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0">Equipos</h2>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/equipos/create')) ?>">Nuevo equipo</a>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Equipo</th><th>Cliente</th><th>Serie</th><th>Color</th><th>Registro</th></tr></thead>
            <tbody>
            <?php foreach ($equipos as $equipo): ?>
                <tr>
                    <td><a href="<?= e(url('/equipos/' . $equipo['id'])) ?>"><?= e(trim($equipo['marca'] . ' ' . $equipo['modelo']) ?: $equipo['tipo']) ?></a></td>
                    <td><?= e($equipo['cliente_nombre']) ?></td>
                    <td><?= e($equipo['numero_serie'] ?: $equipo['imei']) ?></td>
                    <td><?= e($equipo['color']) ?></td>
                    <td><?= e(fechaHumana($equipo['created_at'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
