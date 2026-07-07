<?php
$pageScripts = [asset('js/orden-rapida.js') . '?v=20260614'];
$tiposEquipo = ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'];
?>
<form class="quick-order-form" method="post" action="<?= e(url('/ordenes')) ?>">
    <?= csrf_field() ?>

    <div class="glass-card mb-3">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">1. Cliente</h2>
                <p class="text-muted mb-0">Selecciona un cliente existente o captura uno nuevo aqui mismo.</p>
            </div>
            <span class="badge text-bg-light">Recepcion rapida</span>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Buscar cliente existente</label>
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
                            <label class="form-label">Nombre completo</label>
                            <input class="form-control" name="nombre_completo" data-client-required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Telefono</label>
                            <input class="form-control" name="telefono" data-client-required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">WhatsApp</label>
                            <input class="form-control" name="whatsapp">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Email</label>
                            <input class="form-control" type="email" name="email">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label">Domicilio</label>
                            <input class="form-control" name="domicilio">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Ciudad</label>
                            <input class="form-control" name="ciudad">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Estado</label>
                            <input class="form-control" name="estado_cliente">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Notas cliente</label>
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
                <h2 class="h5 mb-1">2. Equipo</h2>
                <p class="text-muted mb-0">Reutiliza un equipo del cliente o registra el que esta entrando.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                <label class="form-label">Buscar equipo existente</label>
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
                        <div class="col-md-4">
                            <label class="form-label">Tipo</label>
                            <select class="form-select" name="tipo" data-equipment-required>
                                <?php foreach ($tiposEquipo as $tipo): ?>
                                    <option value="<?= e($tipo) ?>" <?= $tipo === 'celular' ? 'selected' : '' ?>><?= e($tipo) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><label class="form-label">Marca</label><input class="form-control" name="marca"></div>
                        <div class="col-md-4"><label class="form-label">Modelo</label><input class="form-control" name="modelo"></div>
                        <div class="col-md-4"><label class="form-label">Serie</label><input class="form-control" name="numero_serie"></div>
                        <div class="col-md-4"><label class="form-label">IMEI</label><input class="form-control" name="imei"></div>
                        <div class="col-md-4"><label class="form-label">Color</label><input class="form-control" name="color"></div>
                        <div class="col-md-4"><label class="form-label">Contrasena/patron</label><input class="form-control" name="password_equipo"></div>
                        <div class="col-md-8"><label class="form-label">Accesorios recibidos</label><input class="form-control" name="accesorios_recibidos" placeholder="Cargador, funda, memoria, caja"></div>
                        <div class="col-md-6"><label class="form-label">Estado fisico al recibir</label><textarea class="form-control" name="estado_fisico" rows="3"></textarea></div>
                        <div class="col-md-6"><label class="form-label">Observaciones del equipo</label><textarea class="form-control" name="observaciones_equipo" rows="3"></textarea></div>
                    </div>
                </div>
                <div class="alert alert-info mb-0 d-none" data-existing-equipment-note>Se usara el equipo seleccionado. Si no aparece, cambia de cliente o crea uno nuevo.</div>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
            <div>
                <h2 class="h5 mb-1">3. Orden de servicio</h2>
                <p class="text-muted mb-0">Registra lo que reporta el cliente y los datos de recepcion.</p>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-4">
                <label class="form-label">Tecnico asignado</label>
                <select class="form-select" name="tecnico_id">
                    <option value="">Sin asignar</option>
                    <?php foreach ($tecnicos as $tecnico): ?>
                        <option value="<?= e($tecnico['id']) ?>"><?= e($tecnico['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Tipo de servicio</label>
                <input class="form-control" name="tipo_servicio" value="Revision" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Prioridad</label>
                <select class="form-select" name="prioridad">
                    <?php foreach (['baja','normal','alta','urgente'] as $prioridad): ?>
                        <option value="<?= e($prioridad) ?>" <?= $prioridad === 'normal' ? 'selected' : '' ?>><?= e($prioridad) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Fecha estimada</label>
                <input class="form-control" type="datetime-local" name="fecha_estimada_entrega">
            </div>
            <div class="col-md-3">
                <label class="form-label">Costo estimado</label>
                <input class="form-control" type="number" step="0.01" name="costo_estimado" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Anticipo</label>
                <input class="form-control" type="number" step="0.01" name="anticipo" value="0">
            </div>
            <div class="col-md-3">
                <label class="form-label">Metodo anticipo</label>
                <select class="form-select" name="metodo_anticipo">
                    <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?>
                        <option value="<?= e($metodo) ?>"><?= e($metodo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Referencia anticipo</label>
                <input class="form-control" name="referencia_anticipo">
            </div>
            <div class="col-md-6">
                <label class="form-label">Falla reportada por el cliente</label>
                <textarea class="form-control" name="falla_reportada" rows="4" required></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Diagnostico inicial</label>
                <textarea class="form-control" name="diagnostico_inicial" rows="4"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Garantia ofrecida</label>
                <input class="form-control" name="garantia_ofrecida" placeholder="Ej. 30 dias sobre reparacion">
            </div>
            <div class="col-md-4">
                <label class="form-label">Observaciones internas</label>
                <textarea class="form-control" name="observaciones_internas" rows="3"></textarea>
            </div>
            <div class="col-md-4">
                <label class="form-label">Observaciones visibles para cliente</label>
                <textarea class="form-control" name="observaciones_cliente" rows="3"></textarea>
            </div>
        </div>

        <div class="d-flex gap-2 flex-wrap mt-4">
            <button class="btn btn-primary">Crear orden completa</button>
            <a class="btn btn-outline-dark" href="<?= e(url('/ordenes')) ?>">Cancelar</a>
        </div>
    </div>
</form>
