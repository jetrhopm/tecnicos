<div class="glass-card">
    <h2 class="h5" data-icon="&#128737;">Garantias activas</h2>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Folio</th><th>Cliente</th><th>Inicio</th><th>Fin</th><th>Estado</th></tr></thead>
            <tbody>
            <?php foreach ($garantias as $garantia): ?>
                <tr>
                    <td><?= e($garantia['folio']) ?></td>
                    <td><?= e($garantia['cliente_nombre']) ?></td>
                    <td><?= e($garantia['fecha_inicio']) ?></td>
                    <td><?= e($garantia['fecha_fin']) ?></td>
                    <td><span class="badge text-bg-success"><?= e($garantia['estado']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
