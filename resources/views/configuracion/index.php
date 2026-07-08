<form method="post" action="<?= e(url('/configuracion')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="d-flex justify-content-end mb-3">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar configuracion</button>
    </div>

    <div class="row g-3">
        <?php foreach ($grupos as $grupo => $items): ?>
            <div class="col-lg-6">
                <div class="glass-card h-100">
                    <h2 class="h5 text-capitalize mb-3" data-icon="&#9881;"><?= e($grupo) ?></h2>

                    <?php foreach ($items as $item): ?>
                        <?php
                        $clave = (string) $item['clave'];
                        $tipo = (string) $item['tipo'];
                        $valor = (string) ($item['valor'] ?? '');
                        $id = 'config_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $clave);
                        ?>
                        <div class="mb-3">
                            <label class="form-label" for="<?= e($id) ?>" data-icon="&#9881;"><?= e($clave) ?></label>

                            <?php if ($tipo === 'text'): ?>
                                <textarea class="form-control" id="<?= e($id) ?>" name="config[<?= e($clave) ?>]" rows="4"><?= e($valor) ?></textarea>
                            <?php elseif ($tipo === 'number'): ?>
                                <input class="form-control" id="<?= e($id) ?>" type="number" step="0.01" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>" data-money>
                            <?php elseif ($tipo === 'bool'): ?>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" id="<?= e($id) ?>" type="checkbox" name="config[<?= e($clave) ?>]" value="1" <?= $valor === '1' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= e($id) ?>">Activo</label>
                                </div>
                            <?php else: ?>
                                <input class="form-control" id="<?= e($id) ?>" name="config[<?= e($clave) ?>]" value="<?= e($valor) ?>">
                            <?php endif; ?>

                            <?php if ($clave === 'negocio.logo_url'): ?>
                                <?php $logoPreview = config_asset_src($valor); ?>
                                <?php if ($logoPreview !== ''): ?>
                                    <div class="mt-2 d-flex align-items-center gap-2">
                                        <img src="<?= e($logoPreview) ?>" alt="Logo actual" style="width:64px;height:64px;object-fit:contain;border:1px solid rgba(0,0,0,.15);border-radius:8px;background:#fff;">
                                        <span class="small text-muted">Logo actual del taller</span>
                                    </div>
                                <?php endif; ?>
                                <input class="form-control mt-2" type="file" name="logo_taller" accept="image/jpeg,image/png,image/webp">
                                <div class="form-text">Puedes pegar una URL/ruta o subir un JPG, PNG o WEBP.</div>
                            <?php endif; ?>

                            <div class="form-text">Tipo: <?= e($tipo) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-end mt-3">
        <button class="btn btn-primary" data-icon="&#128190;">Guardar configuracion</button>
    </div>
</form>
