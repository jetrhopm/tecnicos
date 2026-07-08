<?php
$puedeCrear = \App\Core\Auth::can('punto_venta', 'crear');
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-3">
                <div>
                    <h2 class="h5 mb-1" data-icon="&#128722;">Venta de refacciones</h2>
                    <div class="text-muted small">Busca por SKU con lector de barras o por nombre/modelo para elegir coincidencias.</div>
                </div>
            </div>

            <?php if ($puedeCrear): ?>
                <form method="post" action="<?= e(url('/punto-venta')) ?>" data-pos-form data-pos-search-url="<?= e(url('/punto-venta/buscar')) ?>">
                    <?= csrf_field() ?>

                    <div class="pos-search mb-3">
                        <label class="form-label" data-icon="&#128269;">Buscar / escanear SKU</label>
                        <div class="input-group">
                            <input class="form-control" type="search" autocomplete="off" placeholder="Escanea codigo de barras o escribe nombre, SKU, marca o modelo" data-pos-search>
                            <button class="btn btn-outline-dark" type="button" data-pos-clear-search>Limpiar</button>
                        </div>
                        <div class="pos-search__results d-none" data-pos-results></div>
                        <div class="form-text">Si el SKU coincide exactamente, se agrega automatico. Si hay varias coincidencias, da clic en la refaccion.</div>
                    </div>

                    <div class="table-wrap mb-3">
                        <table class="table align-middle pos-cart-table">
                            <thead>
                                <tr>
                                    <th>Refaccion</th>
                                    <th style="width:120px;">Cantidad</th>
                                    <th style="width:150px;">Precio unitario</th>
                                    <th style="width:140px;">Total</th>
                                    <th style="width:80px;"></th>
                                </tr>
                            </thead>
                            <tbody data-pos-cart>
                                <tr data-pos-empty>
                                    <td colspan="5" class="text-muted text-center py-4">Escanea o busca una refaccion para agregarla.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <template data-pos-row-template>
                        <tr data-pos-row>
                            <td>
                                <strong data-pos-name></strong><br>
                                <small class="text-muted"><span data-pos-sku></span> - Stock <span data-pos-stock></span></small>
                                <input type="hidden" data-pos-field="refaccion_id">
                            </td>
                            <td>
                                <input class="form-control" type="number" min="1" step="1" value="1" data-pos-qty>
                            </td>
                            <td>
                                <input class="form-control" type="number" min="0" step="0.01" value="0" data-pos-price>
                            </td>
                            <td class="fw-bold" data-pos-line-total>$0.00</td>
                            <td>
                                <button class="btn btn-outline-danger btn-sm" type="button" data-pos-remove-item data-icon="&#10005;">Quitar</button>
                            </td>
                        </tr>
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
