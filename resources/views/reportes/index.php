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
            <form class="row g-2 mb-3">
                <div class="col"><input class="form-control" type="date" name="inicio" value="<?= e($_GET['inicio'] ?? '') ?>"></div>
                <div class="col"><input class="form-control" type="date" name="fin" value="<?= e($_GET['fin'] ?? '') ?>"></div>
                <div class="col-auto"><button class="btn btn-outline-dark" data-icon="&#128269;">Filtrar</button></div>
            </form>
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
