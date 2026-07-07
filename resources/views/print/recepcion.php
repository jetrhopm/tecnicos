<div class="d-flex justify-content-between mb-4">
    <div>
        <h1 class="h4">Comprobante de recepcion</h1>
        <p class="mb-0">Sistema Web de Gestion de Servicios Tecnicos y Reparaciones</p>
    </div>
    <strong><?= e($orden['folio'] ?? '') ?></strong>
</div>
<table class="table table-bordered">
    <tr><th>Cliente</th><td><?= e($orden['cliente_nombre'] ?? '') ?></td></tr>
    <tr><th>Telefono</th><td><?= e($orden['cliente_telefono'] ?? '') ?></td></tr>
    <tr><th>Equipo</th><td><?= e(trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: ($orden['equipo_tipo'] ?? '')) ?></td></tr>
    <tr><th>Falla reportada</th><td><?= nl2br(e($orden['falla_reportada'] ?? '')) ?></td></tr>
    <tr><th>Fecha recepcion</th><td><?= e(fechaHumana($orden['fecha_recepcion'] ?? null)) ?></td></tr>
    <tr><th>Estado</th><td><?= e($orden['estado'] ?? '') ?></td></tr>
    <tr><th>Clave de entrega</th><td><?= e($orden['codigo_entrega'] ?? $orden['folio'] ?? '') ?></td></tr>
    <tr><th>Costo estimado</th><td><?= e(formatearMoneda((float) ($orden['costo_estimado'] ?? 0))) ?></td></tr>
    <tr><th>Anticipo</th><td><?= e(formatearMoneda((float) ($orden['anticipo'] ?? 0))) ?></td></tr>
</table>
<p><strong>Codigo de barras para entrega:</strong></p>
<?= codigoBarras39Svg((string) ($orden['codigo_entrega'] ?? $orden['folio'] ?? ''), 52, 2) ?>
<p class="small">El cliente acepta las condiciones de revision y autoriza la recepcion del equipo en el estado descrito.</p>
<div class="row mt-5">
    <div class="col-6 text-center">_________________________<br>Recibe</div>
    <div class="col-6 text-center">_________________________<br>Cliente</div>
</div>
