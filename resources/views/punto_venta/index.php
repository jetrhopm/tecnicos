<?php
$puedeCrear = \App\Core\Auth::can('punto_venta', 'crear');
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <div>
                    <h2 class="h5 mb-1" data-icon="&#128722;">Venta de refacciones</h2>
                    <div class="text-muted small">Venta de mostrador sin crear orden de reparacion.</div>
                </div>
                <?php if ($puedeCrear): ?>
                    <button class="btn btn-outline-primary btn-sm" type="button" data-pos-add-item data-icon="&#43;">Agregar renglon</button>
                <?php endif; ?>
            </div>

            <?php if ($puedeCrear): ?>
                <form method="post" action="<?= e(url('/punto-venta')) ?>" data-pos-form>
                    <?= csrf_field() ?>
                    <div class="vstack gap-2 mb-3" data-pos-items>
                        <div class="quote-item-row" data-pos-row>
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-5">
                                    <label class="form-label" data-icon="&#128230;">Refaccion</label>
                                    <select class="form-select" name="items[0][refaccion_id]" data-pos-part-select required>
                                        <option value="">Selecciona refaccion...</option>
                                        <?php foreach ($refacciones as $refaccion): ?>
                                            <option value="<?= e($refaccion['id']) ?>" data-price="<?= e((string) $refaccion['precio_venta']) ?>" data-stock="<?= e((string) $refaccion['stock_actual']) ?>">
                                                <?= e($refaccion['nombre']) ?> - <?= e($refaccion['sku']) ?> - Stock <?= e($refaccion['stock_actual']) ?> - <?= e(formatearMoneda((float) $refaccion['precio_venta'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label class="form-label" data-icon="&#35;">Cantidad</label>
                                    <input class="form-control" type="number" min="1" step="1" name="items[0][cantidad]" value="1" data-pos-qty required>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <label class="form-label" data-icon="&#36;">Precio</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="items[0][precio_unitario]" value="0" data-pos-price required>
                                </div>
                                <div class="col-lg-2 col-md-4 d-grid">
                                    <button class="btn btn-outline-danger" type="button" data-pos-remove-item data-icon="&#10005;">Quitar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <template data-pos-item-template>
                        <div class="quote-item-row" data-pos-row>
                            <div class="row g-2 align-items-end">
                                <div class="col-lg-5">
                                    <label class="form-label" data-icon="&#128230;">Refaccion</label>
                                    <select class="form-select" name="items[__INDEX__][refaccion_id]" data-pos-part-select required>
                                        <option value="">Selecciona refaccion...</option>
                                        <?php foreach ($refacciones as $refaccion): ?>
                                            <option value="<?= e($refaccion['id']) ?>" data-price="<?= e((string) $refaccion['precio_venta']) ?>" data-stock="<?= e((string) $refaccion['stock_actual']) ?>">
                                                <?= e($refaccion['nombre']) ?> - <?= e($refaccion['sku']) ?> - Stock <?= e($refaccion['stock_actual']) ?> - <?= e(formatearMoneda((float) $refaccion['precio_venta'])) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-2 col-md-4">
                                    <label class="form-label" data-icon="&#35;">Cantidad</label>
                                    <input class="form-control" type="number" min="1" step="1" name="items[__INDEX__][cantidad]" value="1" data-pos-qty required>
                                </div>
                                <div class="col-lg-3 col-md-4">
                                    <label class="form-label" data-icon="&#36;">Precio</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="items[__INDEX__][precio_unitario]" value="0" data-pos-price required>
                                </div>
                                <div class="col-lg-2 col-md-4 d-grid">
                                    <button class="btn btn-outline-danger" type="button" data-pos-remove-item data-icon="&#10005;">Quitar</button>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#128100;">Cliente opcional</label>
                            <input class="form-control" name="cliente_nombre" placeholder="Venta mostrador">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#128241;">Telefono opcional</label>
                            <input class="form-control" name="cliente_telefono">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#9679;">Metodo</label>
                            <select class="form-select" name="metodo_pago">
                                <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?>
                                    <option value="<?= e($metodo) ?>"><?= e($metodo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#37;">Descuento</label>
                            <input class="form-control" type="number" min="0" step="0.01" name="descuento" value="0" data-pos-discount>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#35;">Referencia</label>
                            <input class="form-control" name="referencia">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#36;">Total estimado</label>
                            <input class="form-control fw-bold" value="$0.00" data-pos-total readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label" data-icon="&#9998;">Notas</label>
                            <input class="form-control" name="notas" placeholder="Garantia, observaciones o referencia interna">
                        </div>
                    </div>
                    <button class="btn btn-primary mt-3" data-icon="&#128179;">Cobrar e imprimir ticket</button>
                </form>
            <?php else: ?>
                <div class="alert alert-warning mb-0">Tu rol no puede crear ventas de mostrador.</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#128196;">Ventas recientes</h2>
            <?php if (empty($ventas)): ?>
                <p class="text-muted small mb-0">Aun no hay ventas de refacciones.</p>
            <?php else: ?>
                <div class="vstack gap-2">
                    <?php foreach ($ventas as $venta): ?>
                        <div class="border-bottom pb-2">
                            <div class="d-flex justify-content-between gap-2">
                                <strong><?= e($venta['folio']) ?></strong>
                                <span><?= e(formatearMoneda((float) $venta['total'])) ?></span>
                            </div>
                            <div class="small text-muted">
                                <?= e(fechaHumana($venta['created_at'])) ?> - <?= e($venta['usuario_nombre'] ?: 'sistema') ?> - <?= e($venta['total_items']) ?> item(s)
                            </div>
                            <a class="btn btn-outline-dark btn-sm mt-2" target="_blank" href="<?= e(url('/punto-venta/' . $venta['id'] . '/ticket')) ?>">Ticket</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
