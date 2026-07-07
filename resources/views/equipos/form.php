<?php $isEdit = !empty($equipo); ?>
<form class="glass-card" method="post" action="<?= e($isEdit ? url('/equipos/' . $equipo['id']) : url('/equipos')) ?>">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-5">
            <label class="form-label">Cliente</label>
            <select class="form-select" name="cliente_id" required>
                <option value="">Seleccionar</option>
                <?php foreach ($clientes as $cliente): ?>
                    <?php $selected = (int) ($equipo['cliente_id'] ?? $cliente_id ?? 0) === (int) $cliente['id']; ?>
                    <option value="<?= e($cliente['id']) ?>" <?= $selected ? 'selected' : '' ?>><?= e($cliente['nombre_completo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select class="form-select" name="tipo">
                <?php foreach (['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'] as $tipo): ?>
                    <option value="<?= e($tipo) ?>" <?= (($equipo['tipo'] ?? 'otro') === $tipo) ? 'selected' : '' ?>><?= e($tipo) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Marca</label><input class="form-control" name="marca" value="<?= e($equipo['marca'] ?? '') ?>"></div>
        <div class="col-md-2"><label class="form-label">Modelo</label><input class="form-control" name="modelo" value="<?= e($equipo['modelo'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Serie</label><input class="form-control" name="numero_serie" value="<?= e($equipo['numero_serie'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">IMEI</label><input class="form-control" name="imei" value="<?= e($equipo['imei'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Color</label><input class="form-control" name="color" value="<?= e($equipo['color'] ?? '') ?>"></div>
        <div class="col-md-3"><label class="form-label">Contrasena/patron</label><input class="form-control" name="password_equipo" value="<?= e($equipo['password_equipo'] ?? '') ?>"></div>
        <div class="col-md-4"><label class="form-label">Accesorios</label><textarea class="form-control" name="accesorios_recibidos"><?= e($equipo['accesorios_recibidos'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Estado fisico</label><textarea class="form-control" name="estado_fisico"><?= e($equipo['estado_fisico'] ?? '') ?></textarea></div>
        <div class="col-md-4"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones"><?= e($equipo['observaciones'] ?? '') ?></textarea></div>
    </div>
    <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">Guardar</button>
        <a class="btn btn-outline-dark" href="<?= e(url('/equipos')) ?>">Cancelar</a>
    </div>
</form>
