<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h5 mb-0" data-icon="&#128230;">Inventario y refacciones</h2>
        <span class="badge text-bg-warning"><?= count($stockBajo) ?> con stock bajo</span>
    </div>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Refaccion</th><th>SKU</th><th>Stock</th><th>Minimo</th><th>Ubicacion</th></tr></thead>
            <tbody>
            <?php foreach ($stockBajo as $item): ?>
                <tr>
                    <td><?= e($item['nombre']) ?></td>
                    <td><?= e($item['sku']) ?></td>
                    <td><?= e($item['stock_actual']) ?></td>
                    <td><?= e($item['stock_minimo']) ?></td>
                    <td><?= e($item['ubicacion']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p class="text-muted mt-3 mb-0">Modulo preparado para entradas, salidas, ajustes, proveedores y reportes de utilidad.</p>
</div>
