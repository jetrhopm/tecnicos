<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h1 class="h4 mb-1" data-icon="&#128269;">Consulta de reparacion</h1>
        <p class="text-muted mb-0">Seguimiento publico por folio y token seguro</p>
    </div>
    <a class="btn btn-outline-dark btn-sm" data-icon="&#128274;" href="<?= e(url('/login')) ?>">Panel</a>
</div>

<form class="row g-2 mb-4" method="get" action="<?= e(url('/consulta')) ?>">
    <div class="col-md-5"><input class="form-control" name="folio" value="<?= e($folio) ?>" placeholder="Folio"></div>
    <div class="col-md-5"><input class="form-control" name="token" value="<?= e($token) ?>" placeholder="Token"></div>
    <div class="col-md-2 d-grid"><button class="btn btn-primary" data-icon="&#128269;">Consultar</button></div>
</form>

<?php if ($folio && !$orden): ?>
    <div class="alert alert-warning">No encontramos una orden con esos datos.</div>
<?php endif; ?>

<?php if ($orden): ?>
    <?php $equipo = trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: $orden['equipo_tipo']; ?>
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="glass-card">
                <h2 class="h5" data-icon="&#128203;"><?= e($orden['folio']) ?></h2>
                <p class="text-muted"><?= e($equipo) ?></p>
                <p><span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span></p>
                <dl class="mb-0">
                    <dt>Recepcion</dt><dd><?= e(fechaHumana($orden['fecha_recepcion'])) ?></dd>
                    <dt>Entrega estimada</dt><dd><?= e(fechaHumana($orden['fecha_estimada_entrega'])) ?></dd>
                    <dt>Saldo pendiente</dt><dd><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></dd>
                    <dt>Comentarios</dt><dd><?= nl2br(e($orden['observaciones_cliente'] ?: '-')) ?></dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="glass-card mb-3">
                <h2 class="h5" data-icon="&#128269;">Diagnostico visible</h2>
                <p class="mb-0"><?= nl2br(e($diagnostico['diagnostico_cliente'] ?? 'Aun no hay diagnostico visible para cliente.')) ?></p>
            </div>
            <div class="glass-card">
                <h2 class="h5" data-icon="&#128179;">Cotizacion</h2>
                <?php if ($cotizacion): ?>
                    <div class="table-wrap mb-3">
                        <table class="table">
                            <thead><tr><th>Concepto</th><th>Cantidad</th><th>Subtotal</th></tr></thead>
                            <tbody>
                            <?php foreach ($cotizacion['items'] as $item): ?>
                                <tr><td><?= e($item['descripcion']) ?></td><td><?= e($item['cantidad']) ?></td><td><?= e(formatearMoneda((float) $item['subtotal'])) ?></td></tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Estado: <strong><?= e($cotizacion['estado']) ?></strong></span>
                        <strong><?= e(formatearMoneda((float) $cotizacion['total'])) ?></strong>
                    </div>
                    <?php if ($cotizacion['estado'] === 'pendiente'): ?>
                        <div class="d-flex gap-2 mt-3">
                            <form method="post" action="<?= e(url('/consulta/' . urlencode($orden['folio']) . '/' . urlencode($orden['token_publico']) . '/cotizacion/' . $cotizacion['id'])) ?>">
                                <?= csrf_field() ?><input type="hidden" name="estado" value="aceptada"><button class="btn btn-success" data-icon="&#10003;">Aceptar cotizacion</button>
                            </form>
                            <form method="post" action="<?= e(url('/consulta/' . urlencode($orden['folio']) . '/' . urlencode($orden['token_publico']) . '/cotizacion/' . $cotizacion['id'])) ?>">
                                <?= csrf_field() ?><input type="hidden" name="estado" value="rechazada"><input type="hidden" name="motivo" value="Rechazada desde portal publico"><button class="btn btn-outline-danger" data-icon="&#10005;">Rechazar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">Aun no hay cotizacion publicada.</p>
                <?php endif; ?>
            </div>
            <?php if (!empty($garantia)): ?>
                <div class="glass-card mt-3">
                    <h2 class="h5" data-icon="&#128737;">Garantia</h2>
                    <dl class="mb-2">
                        <dt>Estado</dt><dd><?= e($garantia['estado']) ?></dd>
                        <dt>Vigencia</dt><dd><?= e(fechaHumana($garantia['fecha_inicio'])) ?> a <?= e(fechaHumana($garantia['fecha_fin'])) ?></dd>
                    </dl>
                    <p class="mb-0"><?= nl2br(e($garantia['condiciones'] ?: 'Garantia sobre la reparacion realizada.')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
