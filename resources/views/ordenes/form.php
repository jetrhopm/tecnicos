<?php
$pageScripts = [
    asset('js/orden-rapida.js') . '?v=20260614',
    asset('js/pattern-lock.js') . '?v=20260707-form-ui',
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
?>
<form class="quick-order-form" method="post" action="<?= e(url('/ordenes')) ?>">
    <?= csrf_field() ?>

    <div class="glass-card mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1" data-icon="&#128100;">1. Cliente</h2>
                <p class="text-muted mb-0">Selecciona un cliente existente o captura uno nuevo aqui mismo.</p>
            </div>
            <span class="badge text-bg-light">Recepcion rapida</span>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label" data-icon="&#128269;">Buscar cliente existente</label>
                <input class="form-control mb-2" id="cliente_search" placeholder="Nombre, telefono o email">
                <select class="form-select" name="cliente_id" id="cliente_id">
                    <option value="">Crear cliente nuevo</option>
                    <?php foreach ($clientes as $cliente): ?>
                        <option value="<?= e($cliente['id']) ?>" data-search="<?= e(strtolower($cliente['nombre_completo'] . ' ' . $cliente['telefono'] . ' ' . $cliente['email'])) ?>">
                            <?= e($cliente['nombre_completo'] . ' - ' . $cliente['telefono']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-7">
                <div class="quick-order-new" data-new-client>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" data-icon="&#128100;">Nombre completo</label>
                            <input class="form-control" name="nombre_completo" data-client-required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" data-icon="&#9742;">Telefono</label>
                            <input class="form-control" name="telefono" data-client-required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" data-icon="&#128241;">WhatsApp</label>
                            <input class="form-control" name="whatsapp">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" data-icon="&#9993;">Email</label>
                            <input class="form-control" type="email" name="email">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" data-icon="&#8962;">Domicilio</label>
                            <input class="form-control" name="domicilio">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#9679;">Ciudad</label>
                            <input class="form-control" name="ciudad">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#9679;">Estado</label>
                            <input class="form-control" name="estado_cliente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" data-icon="&#9998;">Notas cliente</label>
                            <input class="form-control" name="notas_cliente">
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mb-0 d-none" data-existing-client-note>Se usara el cliente seleccionado. Puedes cambiarlo si no corresponde.</div>
            </div>
        </div>
    </div>

    <div class="glass-card mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1" data-icon="&#128421;">2. Equipo</h2>
                <p class="text-muted mb-0">Reutiliza un equipo del cliente o registra el que esta entrando.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label" data-icon="&#128269;">Buscar equipo existente</label>
                <input class="form-control mb-2" id="equipo_search" placeholder="Marca, modelo, serie o IMEI">
                <select class="form-select" name="equipo_id" id="equipo_id">
                    <option value="">Crear equipo nuevo</option>
                    <?php foreach ($equipos as $equipo): ?>
                        <?php $equipoNombre = trim(($equipo['marca'] ?? '') . ' ' . ($equipo['modelo'] ?? '')) ?: $equipo['tipo']; ?>
                        <option value="<?= e($equipo['id']) ?>"
                                data-cliente-id="<?= e($equipo['cliente_id']) ?>"
                                data-search="<?= e(strtolower($equipo['cliente_nombre'] . ' ' . $equipoNombre . ' ' . $equipo['numero_serie'] . ' ' . $equipo['imei'] . ' ' . $equipo['color'])) ?>">
                            <?= e($equipo['cliente_nombre'] . ' - ' . $equipoNombre . ' - ' . ($equipo['numero_serie'] ?: $equipo['imei'] ?: 'sin serie')) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-lg-7">
                <div class="quick-order-new" data-new-equipment>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" data-icon="&#9671;">Tipo de equipo</label>
                            <div class="equipment-type-grid">
                                <?php foreach ($tiposEquipo as $tipo): ?>
                                    <label class="equipment-type-card">
                                        <input type="radio" name="tipo" value="<?= e($tipo) ?>" <?= $tipo === 'celular' ? 'checked' : '' ?> data-equipment-required>
                                        <span class="equipment-type-card__icon" aria-hidden="true"><?= $tipoIconos[$tipo] ?? '&#9671;' ?></span>
                                        <span class="equipment-type-card__text"><?= e($tipo) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9671;">Marca</label><input class="form-control" name="marca"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#128421;">Modelo</label><input class="form-control" name="modelo"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9635;">Serie</label><input class="form-control" name="numero_serie"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#35;">IMEI</label><input class="form-control" name="imei"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9679;">Color</label><input class="form-control" name="color"></div>
                        <div class="col-12">
                            <label class="form-label" id="lock-label" data-icon="&#128274;">Patron / clave de desbloqueo</label>
                            <div class="pattern-lock" data-pattern-lock>
                                <input type="hidden" name="password_equipo" id="password_equipo" value="">

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
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-pattern-clear>Borrar patron</button>
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
                        <div class="col-md-8"><label class="form-label" data-icon="&#9671;">Accesorios recibidos</label><input class="form-control" name="accesorios_recibidos" placeholder="Cargador, funda, memoria, caja"></div>
                        <div class="col-md-6"><label class="form-label" data-icon="&#9888;">Estado fisico al recibir</label><textarea class="form-control" name="estado_fisico" rows="3" data-warning></textarea></div>
                        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Observaciones del equipo</label><textarea class="form-control" name="observaciones_equipo" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="alert alert-info mb-0 d-none" data-existing-equipment-note>Se usara el equipo seleccionado. Si no aparece, cambia de cliente o crea uno nuevo.</div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1" data-icon="&#128203;">3. Orden de servicio</h2>
                <p class="text-muted mb-0">Registra lo que reporta el cliente y los datos de recepcion.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label" data-icon="&#128295;">Tecnico asignado</label>
                <select class="form-select" name="tecnico_id">
                    <option value="">Sin asignar</option>
                    <?php foreach ($tecnicos as $tecnico): ?>
                        <option value="<?= e($tecnico['id']) ?>"><?= e($tecnico['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" data-icon="&#9889;">Tipo de servicio</label>
                <input class="form-control" name="tipo_servicio" value="Revision" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" data-icon="&#9888;">Prioridad</label>
                <select class="form-select" name="prioridad">
                    <?php foreach (['baja','normal','alta','urgente'] as $prioridad): ?>
                        <option value="<?= e($prioridad) ?>" <?= $prioridad === 'normal' ? 'selected' : '' ?>><?= e($prioridad) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" data-icon="&#128197;">Fecha estimada</label>
                <input class="form-control" type="datetime-local" name="fecha_estimada_entrega">
            </div>
            <div class="col-md-3">
                <label class="form-label" data-icon="&#36;">Costo estimado</label>
                <input class="form-control" type="number" step="0.01" name="costo_estimado" value="0" data-money>
            </div>
            <div class="col-md-3">
                <label class="form-label" data-icon="&#36;">Anticipo</label>
                <input class="form-control" type="number" step="0.01" name="anticipo" value="0" data-money>
            </div>
            <div class="col-md-3">
                <label class="form-label" data-icon="&#9679;">Metodo anticipo</label>
                <select class="form-select" name="metodo_anticipo">
                    <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?>
                        <option value="<?= e($metodo) ?>"><?= e($metodo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" data-icon="&#35;">Referencia anticipo</label>
                <input class="form-control" name="referencia_anticipo">
            </div>
            <div class="col-md-6">
                <label class="form-label" data-icon="&#9888;">Falla reportada por el cliente</label>
                <textarea class="form-control" name="falla_reportada" rows="4" required data-warning></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label" data-icon="&#128269;">Diagnostico inicial</label>
                <textarea class="form-control" name="diagnostico_inicial" rows="4"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" data-icon="&#128737;">Garantia ofrecida</label>
                <input class="form-control" name="garantia_ofrecida" placeholder="Ej. 30 dias sobre reparacion">
            </div>
            <div class="col-md-4">
                <label class="form-label" data-icon="&#9998;">Observaciones internas</label>
                <textarea class="form-control" name="observaciones_internas" rows="3"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label" data-icon="&#128065;">Observaciones visibles para cliente</label>
                <textarea class="form-control" name="observaciones_cliente" rows="3"></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-4">
            <button class="btn btn-primary" data-icon="&#128190;">Crear orden completa</button>
            <a class="btn btn-outline-dark" data-icon="&#10005;" href="<?= e(url('/ordenes')) ?>">Cancelar</a>
        </div>
    </div>
</form>
