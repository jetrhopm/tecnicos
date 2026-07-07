<?php
$isEdit = !empty($equipo);
$pageScripts = [
    asset('js/pattern-lock.js') . '?v=20260707-real-drag',
];
$tiposEquipo = ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'];
$tipoIconos = [
    'celular' => '&#128241;',
    'laptop' => '&#128187;',
    'pc' => '&#128421;',
    'consola' => '&#127918;',
    'impresora' => '&#128424;',
    'electrodomestico' => '&#9881;',
    'herramienta' => '&#128295;',
    'moto' => '&#127949;',
    'otro' => '&#9671;',
];
$tipoActual = in_array(($equipo['tipo'] ?? 'celular'), $tiposEquipo, true) ? ($equipo['tipo'] ?? 'celular') : 'otro';
$passwordEquipo = (string) ($equipo['password_equipo'] ?? '');
?>
<form class="glass-card" method="post" action="<?= e($isEdit ? url('/equipos/' . $equipo['id']) : url('/equipos')) ?>">
    <?= csrf_field() ?>

    <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
        <div>
            <h2 class="h5 mb-1" data-icon="&#128421;"><?= $isEdit ? 'Editar equipo' : 'Nuevo equipo' ?></h2>
            <p class="text-muted mb-0">Registra datos fisicos, identificadores y desbloqueo del equipo.</p>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label" data-icon="&#128100;">Cliente</label>
            <select class="form-select" name="cliente_id" required>
                <option value="">Seleccionar</option>
                <?php foreach ($clientes as $cliente): ?>
                    <?php $selected = (int) ($equipo['cliente_id'] ?? $cliente_id ?? 0) === (int) $cliente['id']; ?>
                    <option value="<?= e($cliente['id']) ?>" <?= $selected ? 'selected' : '' ?>><?= e($cliente['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label class="form-label" data-icon="&#9671;">Tipo de equipo</label>
            <div class="equipment-type-grid">
                <?php foreach ($tiposEquipo as $tipo): ?>
                    <label class="equipment-type-card">
                        <input type="radio" name="tipo" value="<?= e($tipo) ?>" <?= $tipoActual === $tipo ? 'checked' : '' ?>>
                        <span class="equipment-type-card__icon" aria-hidden="true"><?= $tipoIconos[$tipo] ?? '&#9671;' ?></span>
                        <span class="equipment-type-card__text"><?= e($tipo) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-3"><label class="form-label" data-icon="&#9671;">Marca</label><input class="form-control" name="marca" value="<?= e($equipo['marca'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label" data-icon="&#128421;">Modelo</label><input class="form-control" name="modelo" value="<?= e($equipo['modelo'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label" data-icon="&#9635;">Serie</label><input class="form-control" name="numero_serie" value="<?= e($equipo['numero_serie'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label" data-icon="&#35;">IMEI</label><input class="form-control" name="imei" value="<?= e($equipo['imei'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label" data-icon="&#9679;">Color</label><input class="form-control" name="color" value="<?= e($equipo['color'] ?? '') ?>"></div>

        <div class="col-lg-6">
            <label class="form-label" id="lock-label" data-icon="&#128274;">Patron / clave de desbloqueo</label>
            <div class="pattern-lock" data-pattern-lock>
                <input type="hidden" name="password_equipo" id="password_equipo" value="<?= e($passwordEquipo) ?>">

                <div class="pattern-lock__tabs" role="tablist" aria-label="Tipo de desbloqueo">
                    <button type="button" class="pattern-lock__tab is-active" data-lock-tab="patron" role="tab" aria-selected="true">Patron</button>
                    <button type="button" class="pattern-lock__tab" data-lock-tab="clave" role="tab" aria-selected="false">Clave / PIN</button>
                </div>

                <div data-lock-panel="patron">
                    <div class="pattern-grid" data-pattern-grid role="group" aria-labelledby="lock-label">
                        <svg class="pattern-grid__lines" viewBox="0 0 300 300" preserveAspectRatio="none" aria-hidden="true" focusable="false">
                            <defs>
                                <linearGradient id="patternStroke" x1="0" y1="0" x2="1" y2="1">
                                    <stop offset="0%" stop-color="#3fb5f0"></stop>
                                    <stop offset="55%" stop-color="#7c6ee6"></stop>
                                    <stop offset="100%" stop-color="#e0399e"></stop>
                                </linearGradient>
                            </defs>
                            <polyline data-pattern-path points=""></polyline>
                        </svg>
                        <?php for ($n = 1; $n <= 9; $n++): ?>
                            <button type="button" class="pattern-dot" data-dot="<?= $n ?>" aria-label="Punto <?= $n ?>" aria-pressed="false"></button>
                        <?php endfor; ?>
                    </div>
                    <div class="pattern-lock__foot">
                        <span class="pattern-lock__value" data-pattern-readout aria-live="polite">Sin patron</span>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-pattern-clear data-icon="&#9003;">Borrar patron</button>
                    </div>
                    <div class="form-text">Une los puntos como en el celular del cliente. Se guarda el orden, por ejemplo 1-2-3-6.</div>
                </div>

                <div data-lock-panel="clave" class="d-none">
                    <label class="form-label" for="lock-clave" data-icon="&#35;">PIN o clave del equipo</label>
                    <input type="text" class="form-control" id="lock-clave" data-lock-clave placeholder="Ej. 1234 o la contrasena" autocomplete="off" spellcheck="false" inputmode="text">
                    <div class="pattern-keypad" role="group" aria-label="Teclado numerico">
                        <?php foreach (['1','2','3','4','5','6','7','8','9'] as $key): ?>
                            <button type="button" data-keypad="<?= $key ?>" aria-label="Numero <?= $key ?>"><?= $key ?></button>
                        <?php endforeach; ?>
                        <button type="button" data-keypad="0" aria-label="Numero 0">0</button>
                        <button type="button" data-keypad="back" aria-label="Borrar ultimo">&larr;</button>
                    </div>
                    <div class="form-text">Escribe el PIN o contrasena; el teclado es solo un atajo, tambien puedes teclear directo.</div>
                </div>
            </div>
        </div>

        <div class="col-md-6"><label class="form-label" data-icon="&#9671;">Accesorios</label><textarea class="form-control" name="accesorios_recibidos" rows="3"><?= e($equipo['accesorios_recibidos'] ?? '') ?></textarea></div>
        <div class="col-md-6"><label class="form-label" data-icon="&#9888;">Estado fisico</label><textarea class="form-control" name="estado_fisico" rows="3" data-warning><?= e($equipo['estado_fisico'] ?? '') ?></textarea></div>
        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Observaciones</label><textarea class="form-control" name="observaciones" rows="3"><?= e($equipo['observaciones'] ?? '') ?></textarea></div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar</button>
        <a class="btn btn-outline-dark" data-icon="&#10005;" href="<?= e(url('/equipos')) ?>">Cancelar</a>
    </div>
</form>
