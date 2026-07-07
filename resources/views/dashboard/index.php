<?php $stats = $dashboard['ordenes']; ?>
<div class="row g-3 mb-4">
    <?php
    $cards = [
        ['Ordenes abiertas', $stats['abiertas'], ''],
        ['Urgentes', $stats['urgentes'], 'text-danger'],
        ['Esperando autorizacion', $stats['esperando_autorizacion'], 'text-warning'],
        ['En reparacion', $stats['en_reparacion'], 'text-info'],
        ['Listas para entrega', $stats['listas'], 'text-success'],
        ['Pagos del dia', formatearMoneda($dashboard['pagos_hoy']), 'text-success'],
        ['Saldo pendiente', formatearMoneda($stats['saldo_pendiente']), 'text-danger'],
        ['Stock bajo', $dashboard['stock_bajo'], 'text-warning'],
    ];
    ?>
    <?php foreach ($cards as $card): ?>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="metric">
                <small><?= e($card[0]) ?></small>
                <strong class="<?= e($card[2]) ?>"><?= e($card[1]) ?></strong>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0" data-icon="&#128203;">Ordenes recientes</h2>
                <a class="btn btn-primary btn-sm" data-icon="&#43;" href="<?= e(url('/ordenes/create')) ?>">Nueva orden</a>
            </div>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>Folio</th><th>Cliente</th><th>Equipo</th><th>Estado</th><th>Saldo</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($dashboard['recientes'], 0, 8) as $orden): ?>
                        <tr>
                            <td><a href="<?= e(url('/ordenes/' . $orden['id'])) ?>"><?= e($orden['folio']) ?></a></td>
                            <td><?= e($orden['cliente_nombre']) ?></td>
                            <td><?= e(trim($orden['equipo_marca'] . ' ' . $orden['equipo_modelo']) ?: $orden['equipo_tipo']) ?></td>
                            <td><span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span></td>
                            <td><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#128295;">Carga por tecnico</h2>
            <?php foreach ($dashboard['por_tecnico'] as $row): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <span><?= e($row['tecnico']) ?></span>
                    <strong><?= e($row['total']) ?></strong>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
