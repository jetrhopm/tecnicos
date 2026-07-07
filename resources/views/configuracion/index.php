<form method="post" action="<?= e(url('/configuracion')) ?>">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary">Guardar configuracion</button>
    </div>

    <div class="row g-3">
        <?php foreach ($grupos as $grupo => $items): ?>
            <div class="col-lg-6">
                <div class="glass-card h-100">
                    <h2 class="h5 text-capitalize mb-3"><?= e($grupo) ?></h2>

                    <?php foreach ($items as $item): ?>
                        <?php
                        $clave = (string) $item['clave'];
                        $tipo = (string) $item['tipo'];
                        $valor = (string) ($item['valor'] ?? '');
                        $id = 'config_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $clave);
                        ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= e($id) ?>"><?= e($clave) ?></label>

                            <?php if ($tipo === 'text'): ?>
                                <textarea class="form-control" id="<?= e($id) ?>" name="config[<?= e($clave) ?>]" rows="4"><?= e($valor) ?></textarea>
                            <?php elseif ($tipo === 'number'): ?>
                                <input class="form-control" id="<?= e($id) ?>" type="number" step="0.01" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>">
                            <?php elseif ($tipo === 'bool'): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="<?= e($id) ?>" type="checkbox" name="config[<?= e($clave) ?>]" value="1" <?= $valor === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= e($id) ?>">Activo</label>
                                </div>
                            <?php else: ?>
                                <input class="form-control" id="<?= e($id) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>">
                            <?php endif; ?>

                            <div class="form-text">Tipo: <?= e($tipo) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-primary">Guardar configuracion</button>
    </div>
</form>
