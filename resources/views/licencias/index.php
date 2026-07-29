<?php
$badge = $estado['bloqueo_manual']
    ? 'text-bg-danger'
    : ($estado['activo'] ? ($estado['vencida'] ? 'text-bg-danger' : 'text-bg-success') : 'text-bg-secondary');
$estadoTexto = $estado['bloqueo_manual']
    ? 'Bloqueada'
    : ($estado['activo'] ? ($estado['vencida'] ? 'Vencida' : 'Activa') : 'Desactivada');
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="glass-card h-100">
            <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                <div>
                    <h2 class="h5 mb-1" data-icon="&#128273;">Estado de demo</h2>
                    <p class="text-muted mb-0">Controla el acceso comercial de la versión demo instalada al cliente.</p>
                </div>
                <span class="badge <?= e($badge) ?>"><?= e($estadoTexto) ?></span>
            </div>

            <dl class="row mb-0">
                <dt class="col-5">Inicio</dt>
                <dd class="col-7"><?= e($estado['inicio']) ?></dd>
                <dt class="col-5">Días demo</dt>
                <dd class="col-7"><?= e((string) $estado['dias']) ?></dd>
                <dt class="col-5">Vence</dt>
                <dd class="col-7"><?= e($estado['vence']) ?></dd>
                <dt class="col-5">Restantes</dt>
                <dd class="col-7"><?= e((string) $estado['dias_restantes']) ?></dd>
            </dl>

            <?php if ($estado['bloqueo_manual']): ?>
                <div class="alert alert-danger mt-3 mb-0"><?= e($estado['mensaje_bloqueado']) ?></div>
            <?php elseif ($estado['vencida']): ?>
                <div class="alert alert-danger mt-3 mb-0"><?= e($estado['mensaje_expirado']) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="glass-card h-100">
            <h2 class="h5 mb-3" data-icon="&#9881;">Ajustes de demo</h2>

            <form method="post" action="<?= e(url('/licencia')) ?>">
                <?= csrf_field() ?>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label" for="demo_inicio">Fecha inicio</label>
                        <input class="form-control" id="demo_inicio" type="date" name="inicio" value="<?= e($estado['inicio']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="demo_dias">Días activos</label>
                        <input class="form-control" id="demo_dias" type="number" min="0" max="3650" name="dias" value="<?= e((string) $estado['dias']) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="demo_activo">Bloqueo demo</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" id="demo_activo" type="checkbox" name="activo" value="1" <?= $estado['activo'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="demo_activo">Vence por días</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="demo_bloqueo_manual">Bloqueo manual</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" id="demo_bloqueo_manual" type="checkbox" name="bloqueo_manual" value="1" <?= $estado['bloqueo_manual'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="demo_bloqueo_manual">Bloquear ahora</label>
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="demo_mensaje">Mensaje al vencer</label>
                        <textarea class="form-control" id="demo_mensaje" name="mensaje_expirado" rows="4" required><?= e($estado['mensaje_expirado']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="demo_mensaje_bloqueado">Mensaje de bloqueo manual</label>
                        <textarea class="form-control" id="demo_mensaje_bloqueado" name="mensaje_bloqueado" rows="3" required><?= e($estado['mensaje_bloqueado']) ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button class="btn btn-primary" data-icon="&#128190;">Guardar licencia</button>
                </div>
            </form>
            <div class="d-flex gap-2 flex-wrap mt-2">
                <form method="post" action="<?= e(url('/licencia/reiniciar')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-outline-dark" data-icon="&#8635;">Reiniciar 14 días desde hoy</button>
                </form>
            </div>
        </div>
    </div>
</div>
