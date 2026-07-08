<?php
/*
 * Etiqueta fisica para pegar al equipo.
 * Solo imprime la llave operativa (codigo_entrega) y datos minimos para
 * identificar el dispositivo sin exponer notas, patron ni costos.
 */
$printSize = 'etiqueta';
$orden = $orden ?? [];
$config = $config ?? [];
$negNombre = trim((string) ($config['negocio.nombre'] ?? '')) ?: 'Servicio Tecnico';
$tipo = ucfirst((string) ($orden['equipo_tipo'] ?? 'Equipo'));
$equipo = trim($tipo . ' ' . (string) ($orden['equipo_marca'] ?? '') . ' ' . (string) ($orden['equipo_modelo'] ?? ''));
$serie = trim((string) (($orden['imei'] ?? '') ?: ($orden['numero_serie'] ?? '')));
$codigo = (string) ($orden['codigo_entrega'] ?? '');
?>
<div class="label-device">
    <div class="label-device__top">
        <strong><?= e($negNombre) ?></strong>
        <span><?= e($orden['folio'] ?? '') ?></span>
    </div>
    <div class="label-device__device"><?= e($equipo ?: 'Equipo') ?></div>
    <?php if ($serie !== ''): ?><div class="label-device__serie">ID: <?= e($serie) ?></div><?php endif; ?>
    <div class="label-device__barcode"><?= codigoBarras39Svg($codigo, 34, 1) ?></div>
    <div class="label-device__code"><?= e($codigo) ?></div>
</div>
