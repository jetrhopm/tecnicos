<div class="d-flex justify-content-between mb-4">
    <div>
        <h1 class="h4">Comprobante de entrega</h1>
        <p class="mb-0">Sistema Web de Gestion de Servicios Tecnicos y Reparaciones</p>
    </div>
    <strong><?= e($entrega['folio'] ?? '') ?></strong>
</div>

<table class="table table-bordered">
    <tr><th>Cliente</th><td><?= e($entrega['cliente_nombre'] ?? '') ?></td></tr>
    <tr><th>Telefono</th><td><?= e($entrega['cliente_telefono'] ?? '') ?></td></tr>
    <tr><th>Equipo</th><td><?= e(trim(($entrega['equipo_marca'] ?? '') . ' ' . ($entrega['equipo_modelo'] ?? '')) ?: ($entrega['equipo_tipo'] ?? '')) ?></td></tr>
    <tr><th>Clave entrega</th><td><?= e($entrega['codigo_entrega'] ?? '') ?></td></tr>
    <tr><th>Entregado por</th><td><?= e($entrega['usuario_nombre'] ?? '') ?></td></tr>
    <tr><th>Recibido por</th><td><?= e($entrega['recibido_por_nombre'] ?? '') ?></td></tr>
    <tr><th>Identificacion</th><td><?= e($entrega['recibido_por_identificacion'] ?: '-') ?></td></tr>
    <tr><th>Saldo antes</th><td><?= e(formatearMoneda((float) ($entrega['saldo_antes'] ?? 0))) ?></td></tr>
    <tr><th>Pago final</th><td><?= e(formatearMoneda((float) ($entrega['pago_final'] ?? 0))) ?></td></tr>
    <tr><th>Saldo despues</th><td><?= e(formatearMoneda((float) ($entrega['saldo_despues'] ?? 0))) ?></td></tr>
    <tr><th>Fecha entrega</th><td><?= e(fechaHumana($entrega['created_at'] ?? null)) ?></td></tr>
</table>

<p><strong>Codigo de barras:</strong></p>
<?= codigoBarras39Svg((string) ($entrega['codigo_entrega'] ?? ''), 52, 2) ?>

<p class="small mt-3">El equipo fue liberado usando la clave de entrega presentada por el cliente. Se registra usuario, fecha y saldo como evidencia operativa.</p>
<div class="row mt-5">
    <div class="col-6 text-center">_________________________<br>Entrega</div>
    <div class="col-6 text-center">_________________________<br>Recibe</div>
</div>
