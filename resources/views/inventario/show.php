<?php $bajo = (int) $refaccion['stock_actual'] <= (int) $refaccion['stock_minimo']; ?>
<div class="row g-3">
    <div class="col-xl-7">
        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div>
                    <h2 class="h4 mb-1" data-icon="&#128230;"><?= e($refaccion['nombre']) ?></h2>
                    <p class="text-muted mb-2">SKU <?= e($refaccion['sku']) ?><?= $refaccion['categoria'] ? ' · ' . e($refaccion['categoria']) : '' ?></p>
                    <?php if ($refaccion['estatus'] !== 'activo'): ?><span class="badge text-bg-secondary">inactivo</span><?php endif; ?>
                </div>
                <a class="btn btn-outline-dark btn-sm" data-icon="&#9998;" href="<?= e(url('/inventario/' . $refaccion['id'] . '/edit')) ?>">Editar</a>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-4"><strong>Stock actual</strong><br><span class="h4 <?= $bajo ? 'text-danger' : '' ?>"><?= e($refaccion['stock_actual']) ?></span></div>
                <div class="col-md-4"><strong>Stock minimo</strong><br><?= e($refaccion['stock_minimo']) ?></div>
                <div class="col-md-4"><strong>Ubicacion</strong><br><?= e($refaccion['ubicacion'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Proveedor</strong><br><?= e($refaccion['proveedor_nombre'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Marca</strong><br><?= e($refaccion['marca'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Compatible</strong><br><?= e($refaccion['modelo_compatible'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Costo</strong><br><?= e(formatearMoneda((float) $refaccion['costo'])) ?></div>
                <div class="col-md-4"><strong>Precio venta</strong><br><?= e(formatearMoneda((float) $refaccion['precio_venta'])) ?></div>
            </div>
        </div>

        <div class="glass-card">
            <h2 class="h5" data-icon="&#128220;">Movimientos</h2>
            <?php if (empty($movimientos)): ?>
                <p class="small text-muted mb-0">Sin movimientos registrados.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Stock</th><th>Motivo</th><th>Usuario</th></tr></thead>
                        <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <tr>
                                <td><?= e(fechaHumana($m['created_at'])) ?></td>
                                <td><?= e($m['tipo']) ?></td>
                                <td><?= e($m['cantidad']) ?></td>
                                <td><?= e($m['stock_anterior']) ?> &rarr; <?= e($m['stock_nuevo']) ?></td>
                                <td><?= e($m['motivo']) ?></td>
                                <td><?= e($m['usuario_nombre'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#8635;">Registrar movimiento</h2>
            <form method="post" action="<?= e(url('/inventario/' . $refaccion['id'] . '/movimiento')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#9679;">Tipo</label>
                <select class="form-select mb-2" name="tipo">
                    <option value="entrada">Entrada (sumar stock)</option>
                    <option value="salida">Salida (restar stock)</option>
                    <option value="ajuste">Ajuste (fijar stock a conteo fisico)</option>
                </select>
                <label class="form-label" data-icon="&#35;">Cantidad</label>
                <input class="form-control mb-2" type="number" name="cantidad" min="0" value="1" required>
                <label class="form-label" data-icon="&#36;">Costo unitario (opcional)</label>
                <input class="form-control mb-2" type="number" step="0.01" name="costo_unitario" placeholder="Solo en entradas">
                <label class="form-label" data-icon="&#9998;">Motivo</label>
                <input class="form-control mb-3" name="motivo" placeholder="Compra, uso en orden, merma, conteo...">
                <button class="btn btn-primary w-100" data-icon="&#128190;">Registrar</button>
            </form>
            <a class="btn btn-outline-dark w-100 mt-2" data-icon="&#8592;" href="<?= e(url('/inventario')) ?>">Volver al inventario</a>
        </div>
    </div>
</div>
