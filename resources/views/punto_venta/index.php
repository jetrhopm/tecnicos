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
                <?php if (\App\Core\Auth::can('caja', 'ver')): ?>
                    <a class="btn btn-outline-dark btn-sm" data-icon="&#128179;" href="<?= e(url('/caja')) ?>">Corte de caja</a>
                <?php endif; ?>
            </div>

            <?php if ($puedeCrear): ?>
                <form id="posSaleForm" method="post" action="<?= e(url('/punto-venta')) ?>" data-pos-form data-pos-search-url="<?= e(url('/punto-venta/buscar')) ?>">
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
                                <input type="hidden" data-pos-field="sku">
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

                    <div class="d-flex justify-content-end align-items-center gap-3 flex-wrap">
                        <div class="pos-total-box">
                            <span class="text-muted small">Total</span>
                            <strong data-pos-total>$0.00</strong>
                        </div>
                        <button class="btn btn-outline-danger btn-lg" type="button" data-pos-clear-sale data-icon="&#8634;">Limpiar venta</button>
                        <button class="btn btn-primary btn-lg" type="button" data-pos-open-payment data-icon="&#128179;">Cobrar</button>
                    </div>
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

<?php if ($puedeCrear): ?>
    <?php if (!empty($ticketUrl)): ?>
        <div class="modal fade" id="posTicketModal" tabindex="-1" aria-labelledby="posTicketModalLabel" aria-hidden="true" data-ticket-url="<?= e($ticketUrl) ?>">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="posTicketModalLabel">Ticket de venta</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body p-0">
                        <iframe class="pos-ticket-frame" src="<?= e($ticketUrl) ?>" title="Ticket de venta"></iframe>
                    </div>
                    <div class="modal-footer">
                        <a class="btn btn-outline-dark" target="_blank" href="<?= e($ticketUrl) ?>">Abrir aparte</a>
                        <button class="btn btn-primary" type="button" data-pos-print-ticket>Imprimir ticket</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="modal fade" id="posPaymentModal" tabindex="-1" aria-labelledby="posPaymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h2 class="modal-title h5" id="posPaymentModalLabel">Cobrar venta</h2>
                        <div class="text-muted small">Total: <strong data-pos-modal-total>$0.00</strong></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label" data-icon="&#9679;">Metodo de pago</label>
                    <div class="row g-2 mb-3">
                        <?php foreach (['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'] as $valor => $label): ?>
                            <div class="col-6">
                                <input class="btn-check" type="radio" name="metodo_pago" id="pos_metodo_<?= e($valor) ?>" value="<?= e($valor) ?>" form="posSaleForm" <?= $valor === 'efectivo' ? 'checked' : '' ?>>
                                <label class="btn btn-outline-primary w-100" for="pos_metodo_<?= e($valor) ?>"><?= e($label) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" data-icon="&#35;">Referencia</label>
                        <input class="form-control" name="referencia" form="posSaleForm" placeholder="Folio, autorizacion, ultimos 4 digitos o nota">
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" data-icon="&#128100;">Cliente opcional</label>
                            <input class="form-control" name="cliente_nombre" form="posSaleForm" placeholder="Venta mostrador">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" data-icon="&#128241;">Telefono opcional</label>
                            <input class="form-control" name="cliente_telefono" form="posSaleForm">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="submit" form="posSaleForm" data-icon="&#128179;">Confirmar cobro</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
