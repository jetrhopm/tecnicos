<?php
$fechaAnterior = $filtros['vista'] === 'semana'
    ? $desde->modify('-7 days')->format('Y-m-d')
    : $desde->modify('-1 day')->format('Y-m-d');
$fechaSiguiente = $filtros['vista'] === 'semana'
    ? $desde->modify('+7 days')->format('Y-m-d')
    : $desde->modify('+1 day')->format('Y-m-d');
$rangoTitulo = $filtros['vista'] === 'semana'
    ? fechaHumana($desde->format('Y-m-d')) . ' - ' . fechaHumana($hasta->modify('-1 day')->format('Y-m-d'))
    : fechaHumana($desde->format('Y-m-d'));
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-3">
                <div>
                    <h2 class="h5 mb-1" data-icon="&#128197;">Agenda operativa</h2>
                    <div class="text-muted"><?= e($rangoTitulo) ?></div>
                </div>
                <div class="btn-group">
                    <a class="btn btn-outline-dark btn-sm" href="<?= e(url('/agenda?vista=' . urlencode($filtros['vista']) . '&fecha=' . $fechaAnterior)) ?>">&lt;</a>
                    <a class="btn btn-outline-dark btn-sm" href="<?= e(url('/agenda')) ?>">Hoy</a>
                    <a class="btn btn-outline-dark btn-sm" href="<?= e(url('/agenda?vista=' . urlencode($filtros['vista']) . '&fecha=' . $fechaSiguiente)) ?>">&gt;</a>
                </div>
            </div>

            <form class="row g-2 mb-3" method="get" action="<?= e(url('/agenda')) ?>">
                <div class="col-md-2">
                    <select class="form-select" name="vista">
                        <option value="dia" <?= $filtros['vista'] === 'dia' ? 'selected' : '' ?>>Dia</option>
                        <option value="semana" <?= $filtros['vista'] === 'semana' ? 'selected' : '' ?>>Semana</option>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" type="date" name="fecha" value="<?= e($filtros['fecha']) ?>"></div>
                <div class="col-md-3">
                    <select class="form-select" name="tecnico_id">
                        <option value="">Todos los tecnicos</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?= e($tecnico['id']) ?>" <?= (string) $filtros['tecnico_id'] === (string) $tecnico['id'] ? 'selected' : '' ?>><?= e($tecnico['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select" name="estado">
                        <option value="">Estado</option>
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= e($estado) ?>" <?= $filtros['estado'] === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input class="form-control" name="q" value="<?= e($filtros['q']) ?>" placeholder="Buscar"></div>
                <div class="col-md-1 d-grid"><button class="btn btn-primary" data-icon="&#128269;">Filtrar</button></div>
            </form>

            <?php if ($eventos): ?>
                <div class="table-wrap">
                    <table class="table align-middle">
                        <thead><tr><th>Horario</th><th>Evento</th><th>Tecnico</th><th>Estado</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($eventos as $evento): ?>
                            <?php $equipo = trim(($evento['equipo_marca'] ?? '') . ' ' . ($evento['equipo_modelo'] ?? '')) ?: ($evento['equipo_tipo'] ?? ''); ?>
                            <tr>
                                <td>
                                    <strong><?= e(date('d/m H:i', strtotime((string) $evento['inicio']))) ?></strong><br>
                                    <small class="text-muted"><?= $evento['fin'] ? e(date('H:i', strtotime((string) $evento['fin']))) : 'Sin fin' ?></small>
                                </td>
                                <td>
                                    <span class="badge text-bg-light"><?= e($evento['tipo']) ?></span>
                                    <strong><?= e($evento['titulo']) ?></strong>
                                    <?php if (!empty($evento['folio'])): ?>
                                        <div><a href="<?= e(url('/ordenes/' . $evento['orden_id'])) ?>"><?= e($evento['folio']) ?></a> · <?= e($evento['cliente_nombre'] ?? '') ?> · <?= e($equipo) ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($evento['descripcion'])): ?><small class="text-muted"><?= e($evento['descripcion']) ?></small><?php endif; ?>
                                </td>
                                <td><?= e($evento['tecnico_nombre'] ?: 'Sin asignar') ?></td>
                                <td><span class="badge <?= $evento['estado'] === 'programado' ? 'text-bg-primary' : ($evento['estado'] === 'realizado' ? 'text-bg-success' : 'text-bg-secondary') ?>"><?= e($evento['estado']) ?></span></td>
                                <td>
                                    <form method="post" action="<?= e(url('/agenda/' . $evento['id'] . '/estado')) ?>">
                                        <?= csrf_field() ?>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select" name="estado">
                                                <?php foreach ($estados as $estado): ?>
                                                    <option value="<?= e($estado) ?>" <?= $evento['estado'] === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-outline-dark" data-icon="&#10003;">OK</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">No hay eventos en este rango.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="glass-card">
            <h2 class="h5" data-icon="&#43;">Programar evento</h2>
            <form method="post" action="<?= e(url('/agenda')) ?>">
                <?= csrf_field() ?>
                <label class="form-label">Titulo</label>
                <input class="form-control mb-2" name="titulo" required placeholder="Revision, entrega, visita...">
                <label class="form-label">Orden (folio o clave)</label>
                <input class="form-control mb-2" name="orden_ref" placeholder="Opcional">
                <label class="form-label">Tecnico</label>
                <select class="form-select mb-2" name="tecnico_id">
                    <option value="">Sin asignar</option>
                    <?php foreach ($tecnicos as $tecnico): ?>
                        <option value="<?= e($tecnico['id']) ?>"><?= e($tecnico['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Inicio</label>
                        <input class="form-control mb-2" type="datetime-local" name="inicio" value="<?= e(date('Y-m-d\TH:i')) ?>" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label">Fin</label>
                        <input class="form-control mb-2" type="datetime-local" name="fin">
                    </div>
                </div>
                <label class="form-label">Tipo</label>
                <select class="form-select mb-2" name="tipo">
                    <?php foreach ($tipos as $tipo): ?><option value="<?= e($tipo) ?>"><?= e($tipo) ?></option><?php endforeach; ?>
                </select>
                <label class="form-label">Descripcion</label>
                <textarea class="form-control mb-3" name="descripcion" rows="3"></textarea>
                <button class="btn btn-primary w-100" data-icon="&#128190;">Guardar evento</button>
            </form>
        </div>
    </div>
</div>
