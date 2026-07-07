<?php
$pageScripts = [
    asset('vendor/html5-qrcode.min.js') . '?v=20260614',
    asset('js/entregas-scanner.js') . '?v=20260614b',
];
?>
<div class="glass-card mb-3">
    <form method="post" action="<?= e(url('/entregas/buscar')) ?>" id="delivery-scan-form">
        <?= csrf_field() ?>
        <label class="form-label" for="codigo_entrega" data-icon="&#9635;">Clave del codigo de barras de la nota del cliente</label>
        <div class="input-group input-group-lg">
            <input class="form-control" id="codigo_entrega" name="codigo_entrega" value="<?= e($codigo) ?>" placeholder="Escanea o escribe la clave" autofocus>
            <button class="btn btn-primary" data-icon="&#128269;">Buscar</button>
        </div>
        <div class="d-flex gap-2 flex-wrap mt-3">
            <button class="btn btn-outline-dark" type="button" id="start-camera-scan" data-icon="&#128247;">Usar camara</button>
            <button class="btn btn-outline-secondary d-none" type="button" id="stop-camera-scan" data-icon="&#9632;">Detener camara</button>
        </div>
        <div class="form-text">El lector de barras USB funciona como teclado. En celular puedes usar camara si el navegador permite acceso seguro.</div>
    </form>
    <div id="camera-scan-alert" class="alert alert-warning d-none mt-3 mb-0"></div>
    <div id="camera-scanner-wrap" class="camera-scanner-wrap d-none mt-3">
        <div id="camera-scanner" class="camera-scanner"></div>
        <div class="small text-muted mt-2">Apunta al codigo de barras o QR de la nota. Al detectarlo se buscara automaticamente.</div>
    </div>
</div>

<?php if ($codigo !== '' && !$orden): ?>
    <div class="alert alert-warning">No se encontro ninguna orden con esa clave.</div>
<?php endif; ?>

<?php if ($orden): ?>
    <?php $equipo = trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: $orden['equipo_tipo']; ?>
    <div class="row g-3">
        <div class="col-lg-7">
            <div class="glass-card h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2 class="h4 mb-1" data-icon="&#128203;"><?= e($orden['folio']) ?></h2>
                        <p class="text-muted mb-2"><?= e($orden['cliente_nombre']) ?> · <?= e($equipo) ?></p>
                    </div>
                    <span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span>
                </div>
                <hr>
                <div class="row g-3">
                    <div class="col-md-4"><strong>Clave entrega</strong><br><?= e($orden['codigo_entrega']) ?></div>
                    <div class="col-md-4"><strong>Telefono</strong><br><?= e($orden['cliente_telefono']) ?></div>
                    <div class="col-md-4"><strong>Ubicacion</strong><br><?= e($orden['ubicacion_actual'] ?? 'Recepcion') ?></div>
                    <div class="col-md-4"><strong>Total</strong><br><?= e(formatearMoneda((float) $orden['costo_final'])) ?></div>
                    <div class="col-md-4"><strong>Pagado</strong><br><?= e(formatearMoneda((float) $orden['anticipo'])) ?></div>
                    <div class="col-md-4"><strong>Saldo</strong><br><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></div>
                    <div class="col-12"><strong>Falla reportada</strong><p><?= nl2br(e($orden['falla_reportada'])) ?></p></div>
                    <div class="col-12"><strong>Observaciones cliente</strong><p><?= nl2br(e($orden['observaciones_cliente'] ?: '-')) ?></p></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="glass-card">
                <h2 class="h5" data-icon="&#128275;">Liberar equipo</h2>
                <?php if ($orden['estado'] === 'entregada'): ?>
                    <div class="alert alert-info mb-0">Esta orden ya esta marcada como entregada.</div>
                <?php else: ?>
                    <form method="post" action="<?= e(url('/entregas/entregar')) ?>" data-confirm="Confirmar entrega del equipo con esta clave">
                        <?= csrf_field() ?>
                        <input type="hidden" name="codigo_entrega" value="<?= e($orden['codigo_entrega']) ?>">
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#128100;">Nombre de quien recibe</label>
                            <input class="form-control" name="recibido_por_nombre" value="<?= e($orden['cliente_nombre']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#35;">Identificacion opcional</label>
                            <input class="form-control" name="recibido_por_identificacion">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#36;">Pago final</label>
                            <input class="form-control" type="number" step="0.01" name="pago_final" value="<?= e((string) max(0, (float) $orden['saldo_pendiente'])) ?>" data-money>
                            <div class="form-text">Debe quedar saldo cero para liberar el equipo.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#9679;">Metodo de pago</label>
                            <select class="form-select" name="metodo_pago">
                                <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?>
                                    <option value="<?= e($metodo) ?>"><?= e($metodo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#35;">Referencia</label>
                            <input class="form-control" name="referencia_pago">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" data-icon="&#9998;">Observaciones de entrega</label>
                            <textarea class="form-control" name="observaciones" rows="3"></textarea>
                        </div>
                        <button class="btn btn-success w-100" data-icon="&#10003;">Entregar equipo</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>
