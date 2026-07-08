<?php
$inicio = (string) ($inicio ?? '');
$fin = (string) ($fin ?? '');
$query = http_build_query(array_filter(['inicio' => $inicio, 'fin' => $fin], static fn ($v) => $v !== ''));
$exportUrl = static fn (string $tipo): string => url('/reportes/exportar?' . http_build_query(array_filter(['tipo' => $tipo, 'inicio' => $inicio, 'fin' => $fin], static fn ($v) => $v !== '')));
?>

<div class="glass-card mb-3">
    <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
        <div>
            <h2 class="h5 mb-1" data-icon="&#128202;">Reportes exportables</h2>
            <p class="text-muted mb-0">Filtra por fechas y exporta CSV para caja, saldos, refacciones y utilidad estimada.</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-dark btn-sm" data-icon="&#8681;" href="<?= e($exportUrl('caja')) ?>">Caja CSV</a>
            <a class="btn btn-outline-dark btn-sm" data-icon="&#8681;" href="<?= e($exportUrl('saldos')) ?>">Saldos CSV</a>
            <a class="btn btn-outline-dark btn-sm" data-icon="&#8681;" href="<?= e($exportUrl('refacciones')) ?>">Refacciones CSV</a>
            <a class="btn btn-outline-dark btn-sm" data-icon="&#8681;" href="<?= e($exportUrl('utilidad')) ?>">Utilidad CSV</a>
        </div>
    </div>
    <form class="row g-2 mt-3" method="get" action="<?= e(url('/reportes')) ?>">
        <div class="col-md-3"><input class="form-control" type="date" name="inicio" value="<?= e($inicio) ?>"></div>
        <div class="col-md-3"><input class="form-control" type="date" name="fin" value="<?= e($fin) ?>"></div>
        <div class="col-auto"><button class="btn btn-primary" data-icon="&#128269;">Filtrar</button></div>
        <?php if ($query !== ''): ?><div class="col-auto"><a class="btn btn-outline-dark" href="<?= e(url('/reportes')) ?>">Limpiar</a></div><?php endif; ?>
    </form>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#128203;">Ordenes por estado</h2>
            <?php foreach ($dashboard['por_estado'] as $row): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><?= e($row['estado']) ?></span>
                    <strong><?= e($row['total']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#128179;">Corte de caja</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Fecha</th><th>Folio</th><th>Metodo</th><th>Monto</th></tr></thead>
                    <tbody>
                    <?php foreach ($pagos as $pago): ?>
                        <tr><td><?= e(fechaHumana($pago['created_at'])) ?></td><td><?= e($pago['folio']) ?></td><td><?= e($pago['metodo']) ?></td><td><?= e(formatearMoneda((float) $pago['monto'])) ?></td></tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="glass-card h-100">
            <h2 class="h5" data-icon="&#128179;">Resumen por usuario/metodo</h2>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>Fecha</th><th>Usuario</th><th>Metodo</th><th>Ops.</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach (($reportes['caja_resumen'] ?? []) as $row): ?>
                        <tr>
                            <td><?= e($row['fecha']) ?></td>
                            <td><?= e($row['usuario']) ?></td>
                            <td><?= e($row['metodo']) ?></td>
                            <td><?= e($row['operaciones']) ?></td>
                            <td><?= e(formatearMoneda((float) $row['total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card h-100">
            <h2 class="h5" data-icon="&#9888;">Saldos pendientes</h2>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>Folio</th><th>Cliente</th><th>Estado</th><th>Saldo</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($reportes['saldos_pendientes'] ?? [], 0, 10) as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td><?= e($row['cliente']) ?></td>
                            <td><span class="badge text-bg-light"><?= e($row['estado']) ?></span></td>
                            <td><?= e(formatearMoneda((float) $row['saldo_pendiente'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-lg-6">
        <div class="glass-card h-100">
            <h2 class="h5" data-icon="&#128230;">Refacciones mas usadas</h2>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>SKU</th><th>Refaccion</th><th>Cant.</th><th>Utilidad</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($reportes['refacciones_usadas'] ?? [], 0, 10) as $row): ?>
                        <tr>
                            <td><?= e($row['sku']) ?></td>
                            <td><?= e($row['nombre']) ?></td>
                            <td><?= e($row['cantidad_usada']) ?></td>
                            <td><?= e(formatearMoneda((float) $row['utilidad_estimada'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="glass-card h-100">
            <h2 class="h5" data-icon="&#128200;">Utilidad estimada</h2>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>Folio</th><th>Cliente</th><th>Total</th><th>Utilidad</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($reportes['utilidad_estimada'] ?? [], 0, 10) as $row): ?>
                        <tr>
                            <td><?= e($row['folio']) ?></td>
                            <td><?= e($row['cliente']) ?></td>
                            <td><?= e(formatearMoneda((float) $row['total_orden'])) ?></td>
                            <td><?= e(formatearMoneda((float) $row['utilidad_estimada'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="small text-muted mb-0">La utilidad es estimada: mano de obra aproximada + margen de refacciones activas.</p>
        </div>
    </div>
</div>
