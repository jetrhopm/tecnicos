<div class="row g-3">
    <div class="col-lg-4">
        <div class="glass-card">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h5" data-icon="&#128100;"><?= e($cliente['nombre_completo']) ?></h2>
                    <p class="text-muted mb-2"><?= e($cliente['telefono']) ?> · <?= e($cliente['email'] ?: 'sin email') ?></p>
                </div>
                <a class="btn btn-outline-dark btn-sm" data-icon="&#9998;" href="<?= e(url('/clientes/' . $cliente['id'] . '/edit')) ?>">Editar</a>
            </div>
            <dl class="mb-0">
                <dt>Domicilio</dt><dd><?= e($cliente['domicilio'] ?: '-') ?></dd>
                <dt>Ciudad</dt><dd><?= e(trim(($cliente['ciudad'] ?? '') . ' ' . ($cliente['estado'] ?? '')) ?: '-') ?></dd>
                <dt>RFC</dt><dd><?= e($cliente['rfc'] ?: '-') ?></dd>
            </dl>
            <div class="d-grid gap-2 mt-3">
                <a class="btn btn-primary" data-icon="&#128421;" href="<?= e(url('/equipos/create?cliente_id=' . $cliente['id'])) ?>">Registrar equipo</a>
                <a class="btn btn-outline-dark" data-icon="&#128203;" href="<?= e(url('/ordenes/create')) ?>">Nueva orden</a>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#128203;">Historial de reparaciones</h2>
            <div class="table-wrap">
                <table class="table align-middle">
                    <thead><tr><th>Folio</th><th>Equipo</th><th>Estado</th><th>Recepcion</th><th>Saldo</th></tr></thead>
                    <tbody>
                    <?php foreach ($historial as $orden): ?>
                        <tr>
                            <td><a href="<?= e(url('/ordenes/' . $orden['id'])) ?>"><?= e($orden['folio']) ?></a></td>
                            <td><?= e(trim($orden['marca'] . ' ' . $orden['modelo']) ?: $orden['tipo']) ?></td>
                            <td><span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span></td>
                            <td><?= e(fechaHumana($orden['fecha_recepcion'])) ?></td>
                            <td><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
