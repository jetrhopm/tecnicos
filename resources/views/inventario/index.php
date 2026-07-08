<?php
$pageScripts = [
    asset('vendor/html5-qrcode.min.js') . '?v=20260614',
    asset('js/barcode-scan.js') . '?v=20260707',
];
?>
<div class="glass-card">
    <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
        <h2 class="h5 mb-0" data-icon="&#128230;">Almacen e inventario</h2>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge text-bg-warning align-self-center"><?= count($stockBajo) ?> con stock bajo</span>
            <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/inventario/create')) ?>">Nueva refaccion</a>
        </div>
    </div>

    <form class="row g-2 mb-3" method="get" action="<?= e(url('/inventario')) ?>">
        <div class="col-md-8">
            <div class="input-group">
                <input class="form-control" id="inv-q" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar por nombre, SKU, categoria o marca">
                <button class="btn btn-outline-dark" type="button" data-barcode-scan="inv-q" data-barcode-submit data-icon="&#128247;" aria-label="Escanear codigo de barras"></button>
            </div>
        </div>
        <div class="col-md-2">
            <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" id="solo_bajo" name="solo_bajo" value="1" <?= !empty($filtros['solo_bajo']) ? 'checked' : '' ?>>
                <label class="form-check-label" for="solo_bajo">Solo stock bajo</label>
            </div>
        </div>
        <div class="col-md-2 d-grid"><button class="btn btn-outline-dark" data-icon="&#128269;">Filtrar</button></div>
    </form>

    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Refaccion</th><th>SKU</th><th>Categoria</th><th>Proveedor</th><th>Stock</th><th>Minimo</th><th>Precio</th><th>Ubicacion</th></tr></thead>
            <tbody>
            <?php foreach ($refacciones as $item): ?>
                <?php $bajo = (int) $item['stock_actual'] <= (int) $item['stock_minimo']; ?>
                <tr>
                    <td>
                        <a href="<?= e(url('/inventario/' . $item['id'])) ?>"><?= e($item['nombre']) ?></a>
                        <?php if ($item['estatus'] !== 'activo'): ?><span class="badge text-bg-secondary">inactivo</span><?php endif; ?>
                    </td>
                    <td><?= e($item['sku']) ?></td>
                    <td><?= e($item['categoria'] ?: '-') ?></td>
                    <td><?= e($item['proveedor_nombre'] ?: '-') ?></td>
                    <td><strong class="<?= $bajo ? 'text-danger' : '' ?>"><?= e($item['stock_actual']) ?></strong></td>
                    <td><?= e($item['stock_minimo']) ?></td>
                    <td><?= e(formatearMoneda((float) $item['precio_venta'])) ?></td>
                    <td><?= e($item['ubicacion'] ?: '-') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($refacciones)): ?>
                <tr><td colspan="8" class="text-muted">No hay refacciones que coincidan.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
