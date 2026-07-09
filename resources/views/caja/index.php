<?php
$puedeAdministrar = \App\Core\Auth::can('caja', 'administrar');
$puedeCerrar = \App\Core\Auth::can('caja', 'editar');
$turno = $turno ?? null;
$corte = $corte ?? null;
$metodos = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'];
?>

<div class="d-flex justify-content-between align-items-start gap-3 flex-wrap mb-3">
    <div>
        <h2 class="h4 mb-1" data-icon="&#128179;">Caja y corte</h2>
        <p class="text-muted mb-0">Pantalla operativa para revisar ingresos, retiros y cierre del turno actual.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-dark" data-icon="&#128722;" href="<?= e(url('/punto-venta')) ?>">Punto de venta</a>
        <?php if ($turno && \App\Core\Auth::can('caja', 'imprimir')): ?>
            <a class="btn btn-outline-dark" data-icon="&#128424;" target="_blank" href="<?= e(url('/caja/' . $turno['id'] . '/imprimir')) ?>">Imprimir corte</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$turno): ?>
    <div class="glass-card mb-3">
        <h2 class="h5" data-icon="&#128275;">Caja sin iniciar</h2>
        <p class="text-muted">No hay un turno de caja abierto. Un administrador debe iniciar caja con el fondo inicial antes de operar corte.</p>
        <?php if ($puedeAdministrar): ?>
            <form class="row g-3 align-items-end" method="post" action="<?= e(url('/caja/abrir')) ?>">
                <?= csrf_field() ?>
                <div class="col-md-4">
                    <label class="form-label" data-icon="&#36;">Fondo inicial</label>
                    <input class="form-control" type="number" step="0.01" min="0" name="fondo_inicial" value="0">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary" data-icon="&#9658;">Iniciar caja</button>
                </div>
            </form>
        <?php else: ?>
            <div class="alert alert-warning mb-0">Solicita a un admin o superadmin que inicie caja.</div>
        <?php endif; ?>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="metric h-100">
                <span>Turno</span>
                <strong><?= e($turno['folio']) ?></strong>
                <small>Abierto por <?= e($turno['abierto_por_nombre'] ?? 'usuario') ?> · <?= e(fechaHumana($turno['opened_at'])) ?></small>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="metric h-100">
                <span>Esperado en caja</span>
                <strong><?= e(formatearMoneda((float) $corte['total_esperado'])) ?></strong>
                <small>Incluye fondo inicial y descuenta retiros.</small>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="metric h-100">
                <span>Retiros</span>
                <strong><?= e(formatearMoneda((float) $corte['retiros'])) ?></strong>
                <small>Solo admin/superadmin pueden registrar retiros.</small>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="glass-card h-100">
                <h2 class="h5" data-icon="&#128200;">Resumen del turno</h2>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Metodo</th><th>Ops.</th><th>Ingresos</th><th>Esperado</th></tr></thead>
                        <tbody>
                        <?php foreach ($metodos as $key => $label): ?>
                            <tr>
                                <td><?= e($label) ?></td>
                                <td><?= e((string) ($corte['resumen'][$key]['operaciones'] ?? 0)) ?></td>
                                <td><?= e(formatearMoneda((float) ($corte['resumen'][$key]['total'] ?? 0))) ?></td>
                                <td class="fw-bold"><?= e(formatearMoneda((float) ($corte['esperado'][$key] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-5">
            <div class="glass-card h-100">
                <h2 class="h5" data-icon="&#9989;">Cerrar caja</h2>
                <?php if ($puedeCerrar): ?>
                    <form method="post" action="<?= e(url('/caja/cerrar')) ?>" data-confirm="Cerrar caja con los montos contados">
                        <?= csrf_field() ?>
                        <div class="row g-2">
                            <?php foreach ($metodos as $key => $label): ?>
                                <div class="col-6">
                                    <label class="form-label"><?= e($label) ?> contado</label>
                                    <input class="form-control" type="number" min="0" step="0.01" name="<?= e($key) ?>_contado" value="<?= e((string) ($corte['esperado'][$key] ?? 0)) ?>">
                                </div>
                            <?php endforeach; ?>
                            <div class="col-12">
                                <label class="form-label">Observaciones</label>
                                <textarea class="form-control" name="observaciones" rows="2" placeholder="Diferencias, faltantes, sobrantes o notas del turno"></textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-primary w-100" data-icon="&#128190;">Cerrar e imprimir corte</button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning mb-0">Tu rol puede consultar caja, pero no cerrar el turno.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($puedeAdministrar): ?>
        <div class="glass-card mt-3">
            <h2 class="h5" data-icon="&#128176;">Retiro de efectivo</h2>
            <form class="row g-3 align-items-end" method="post" action="<?= e(url('/caja/retiro')) ?>">
                <?= csrf_field() ?>
                <div class="col-md-3">
                    <label class="form-label">Monto</label>
                    <input class="form-control" type="number" min="0.01" step="0.01" name="monto">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Motivo</label>
                    <input class="form-control" name="concepto" placeholder="Retiro parcial, deposito, resguardo">
                </div>
                <div class="col-md-2">
                    <button class="btn btn-outline-danger w-100" data-icon="&#8722;">Retirar</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <div class="row g-3 mt-1">
        <div class="col-lg-6">
            <div class="glass-card h-100">
                <h2 class="h5" data-icon="&#128203;">Operaciones del turno</h2>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Hora</th><th>Origen</th><th>Folio</th><th>Metodo</th><th>Monto</th></tr></thead>
                        <tbody>
                        <?php foreach (array_slice($corte['operaciones'], 0, 30) as $op): ?>
                            <tr>
                                <td><?= e(fechaHumana($op['created_at'])) ?></td>
                                <td><?= e($op['origen']) ?></td>
                                <td><?= e($op['folio']) ?></td>
                                <td><?= e($op['metodo']) ?></td>
                                <td><?= e(formatearMoneda((float) $op['monto'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($corte['operaciones'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aun no hay ingresos en este turno.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="glass-card h-100">
                <h2 class="h5" data-icon="&#128179;">Movimientos manuales</h2>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Hora</th><th>Tipo</th><th>Concepto</th><th>Monto</th><th>Usuario</th></tr></thead>
                        <tbody>
                        <?php foreach ($corte['movimientos'] as $mov): ?>
                            <tr>
                                <td><?= e(fechaHumana($mov['created_at'])) ?></td>
                                <td><?= e($mov['tipo']) ?></td>
                                <td><?= e($mov['concepto']) ?></td>
                                <td><?= e(formatearMoneda((float) $mov['monto'])) ?></td>
                                <td><?= e($mov['usuario_nombre']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($corte['movimientos'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Sin retiros registrados.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="glass-card mt-3">
    <h2 class="h5" data-icon="&#128338;">Cortes recientes</h2>
    <div class="table-wrap">
        <table class="table align-middle">
            <thead><tr><th>Folio</th><th>Estado</th><th>Apertura</th><th>Cierre</th><th>Total</th><th>Diferencia</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($turnos as $row): ?>
                <tr>
                    <td><?= e($row['folio']) ?></td>
                    <td><span class="badge text-bg-light"><?= e($row['estado']) ?></span></td>
                    <td><?= e(fechaHumana($row['opened_at'])) ?></td>
                    <td><?= e($row['closed_at'] ? fechaHumana($row['closed_at']) : '-') ?></td>
                    <td><?= e(formatearMoneda((float) $row['total_contado'])) ?></td>
                    <td><?= e(formatearMoneda((float) $row['diferencia'])) ?></td>
                    <td><a class="btn btn-outline-dark btn-sm" target="_blank" href="<?= e(url('/caja/' . $row['id'] . '/imprimir')) ?>">Ver</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
