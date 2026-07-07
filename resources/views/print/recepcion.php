<?php
/*
 * Documento imprimible de la orden de servicio.
 * Formatos: carta (hoja con recuadros y firmas) y termico 80/58 mm.
 * $formato lo pasa el controlador; $config trae los datos del negocio.
 */
$formato = in_array(($formato ?? 'carta'), ['carta', '80', '58'], true) ? $formato : 'carta';
$printSize = $formato;
$docClass = 'doc doc-' . $formato;

$orden = $orden ?? [];
$config = $config ?? [];

$tipo = ucfirst((string) ($orden['equipo_tipo'] ?? ''));
$marcaModelo = trim((string) (($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')));
$equipoFull = trim($tipo . ' ' . $marcaModelo) ?: ($tipo ?: 'Equipo');
$serie = (string) ($orden['numero_serie'] ?? '');
$imei = (string) ($orden['imei'] ?? '');
$imeiSerie = trim($imei . ($imei && $serie ? ' / ' : '') . $serie);

$desbloqueo = patronDesbloqueo($orden['password_equipo'] ?? '');
$patronPx = $formato === '58' ? 64 : ($formato === '80' ? 78 : 92);

$total = (float) ($orden['costo_final'] ?? $orden['costo_estimado'] ?? 0);
$anticipo = (float) ($orden['anticipo'] ?? 0);
$saldo = (float) ($orden['saldo_pendiente'] ?? 0);

$negNombre = trim((string) ($config['negocio.nombre'] ?? '')) ?: 'Servicio Tecnico';
$negTel = trim((string) ($config['negocio.telefono'] ?? ''));
$negWa = trim((string) ($config['negocio.whatsapp'] ?? ''));
$negDir = trim((string) ($config['negocio.direccion'] ?? ''));
$logo = trim((string) ($config['negocio.logo_url'] ?? ''));
$garantia = trim((string) ($config['ticket.garantia'] ?? $config['legal.politica_garantia'] ?? ''));
$condiciones = trim((string) ($config['legal.politica_garantia'] ?? ''));

$falla = trim((string) ($orden['falla_reportada'] ?? ''));
$reparacion = trim((string) ($orden['diagnostico_inicial'] ?? '')) ?: trim((string) ($orden['tipo_servicio'] ?? ''));
$observaciones = trim((string) ($orden['observaciones_cliente'] ?? ''));
$estadoFisico = trim((string) ($orden['equipo_estado_fisico'] ?? ''));
$accesorios = trim((string) ($orden['accesorios_recibidos'] ?? '')) ?: 'Sin accesorios';
?>
<div class="<?= e($docClass) ?>">
    <header class="doc-head">
        <div class="doc-logo">
            <?php if ($logo !== ''): ?>
                <img src="<?= e($logo) ?>" alt="<?= e($negNombre) ?>">
            <?php else: ?>
                <span class="doc-logo-mark">ST</span>
            <?php endif; ?>
        </div>
        <div class="doc-biz">
            <div class="doc-biz-name"><?= e($negNombre) ?></div>
            <div class="doc-biz-sub">Servicio Tecnico</div>
            <?php if ($negTel !== '' || $negWa !== ''): ?>
                <div class="doc-biz-contact">
                    <?php if ($negTel !== ''): ?>Tel: <?= e($negTel) ?><?php endif; ?>
                    <?php if ($negTel !== '' && $negWa !== ''): ?> &middot; <?php endif; ?>
                    <?php if ($negWa !== ''): ?>WhatsApp: <?= e($negWa) ?><?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($negDir !== ''): ?><div class="doc-biz-contact"><?= e($negDir) ?></div><?php endif; ?>
        </div>
    </header>

    <div class="doc-title">
        <div class="doc-orden">ORDEN #<?= e($orden['folio'] ?? '') ?></div>
        <div class="doc-subtitle"><?= $formato === 'carta' ? 'Comprobante de recepcion y retiro' : 'Ticket interno de recepcion y retiro' ?></div>
    </div>
    <hr class="doc-rule">

    <?php if ($formato === 'carta'): ?>
        <div class="doc-grid">
            <div class="cell cell-8"><div class="field-l">Cliente</div><div class="field-v"><?= e($orden['cliente_nombre'] ?? '') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Telefono / WhatsApp</div><div class="field-v"><?= e($orden['cliente_telefono'] ?? '') ?></div></div>

            <div class="cell cell-8"><div class="field-l">Direccion</div><div class="field-v"><?= e(($orden['cliente_domicilio'] ?? '') ?: '-') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Correo</div><div class="field-v"><?= e(($orden['cliente_email'] ?? '') ?: '-') ?></div></div>

            <div class="cell cell-4"><div class="field-l">Equipo</div><div class="field-v"><?= e($tipo ?: '-') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Marca</div><div class="field-v"><?= e(($orden['equipo_marca'] ?? '') ?: '-') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Modelo</div><div class="field-v"><?= e(($orden['equipo_modelo'] ?? '') ?: '-') ?></div></div>

            <div class="cell cell-6"><div class="field-l">IMEI / Serie</div><div class="field-v"><?= e($imeiSerie ?: '-') ?></div></div>
            <div class="cell cell-6">
                <div class="field-l">Patron / Clave de desbloqueo</div>
                <?php if ($desbloqueo && $desbloqueo['tipo'] === 'patron'): ?>
                    <div class="patron-box">
                        <?= patronSvg($desbloqueo['secuencia'], $patronPx) ?>
                        <div class="patron-meta">
                            <div><span class="patron-meta-strong">Inicio:</span> <?= e((string) $desbloqueo['secuencia'][0]) ?> &middot; <span class="patron-meta-strong">Fin:</span> <?= e((string) end($desbloqueo['secuencia'])) ?></div>
                            <div class="patron-seq">Sec: <?= e(implode(' > ', $desbloqueo['secuencia'])) ?></div>
                        </div>
                    </div>
                <?php elseif ($desbloqueo && $desbloqueo['tipo'] === 'clave'): ?>
                    <div class="field-v big"><?= e($desbloqueo['valor']) ?></div>
                <?php else: ?>
                    <div class="field-v">-</div>
                <?php endif; ?>
            </div>

            <div class="cell cell-8"><div class="field-l">Servicio rapido / Plantilla</div><div class="field-v"><?= e(($orden['tipo_servicio'] ?? '') ?: '-') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Accesorios</div><div class="field-v"><?= e($accesorios) ?></div></div>

            <div class="cell cell-12"><div class="field-l">Falla declarada</div><div class="field-v"><?= nl2br(e($falla ?: '-')) ?></div></div>
            <div class="cell cell-12"><div class="field-l">Observaciones / Senas</div><div class="field-v"><?= nl2br(e($observaciones ?: '-')) ?></div></div>
            <div class="cell cell-12"><div class="field-l">Estado fisico al recibir</div><div class="field-v"><?= nl2br(e($estadoFisico ?: '-')) ?></div></div>
            <div class="cell cell-12"><div class="field-l">Reparacion / Trabajo</div><div class="field-v"><?= nl2br(e($reparacion ?: '-')) ?></div></div>

            <div class="cell cell-3"><div class="field-l">Fecha entrada</div><div class="field-v"><?= e(fechaHumana($orden['fecha_recepcion'] ?? null)) ?></div></div>
            <div class="cell cell-3"><div class="field-l">Fecha entrega</div><div class="field-v"><?= e(fechaHumana($orden['fecha_estimada_entrega'] ?? null)) ?></div></div>
            <div class="cell cell-3"><div class="field-l">Estado</div><div class="field-v"><?= e($orden['estado'] ?? '') ?></div></div>
            <div class="cell cell-3"><div class="field-l">Clave de entrega</div><div class="field-v"><?= e($orden['codigo_entrega'] ?? '') ?></div></div>
        </div>

        <div style="display:flex; gap:16px; margin-top:8px;">
            <div style="flex:1.4;">
                <div class="field-l">Garantia y condiciones</div>
                <div class="doc-terms"><?= nl2br(e($condiciones ?: $garantia)) ?></div>
            </div>
            <div style="flex:1;">
                <table class="totales">
                    <tr><td class="t-label">Presupuesto</td><td class="t-amount"><?= e(formatearMoneda($total)) ?></td></tr>
                    <tr class="t-total"><td class="t-label">Total del servicio</td><td class="t-amount"><?= e(formatearMoneda($total)) ?></td></tr>
                    <tr><td class="t-label">Cobrado / Sena</td><td class="t-amount"><?= e(formatearMoneda($anticipo)) ?></td></tr>
                </table>
                <div class="saldo-box"><span>Saldo a cobrar</span><span class="saldo-amount"><?= e(formatearMoneda($saldo)) ?></span></div>
            </div>
        </div>

        <div class="firmas">
            <div class="firma">Firma cliente</div>
            <div class="firma">Recibe (taller)</div>
        </div>
        <p class="doc-note" style="margin-top:6px;">El cliente acepta el ingreso del equipo al servicio tecnico y las condiciones descritas. Presenta esta nota para retirar el equipo.</p>

    <?php else: ?>
        <div class="field"><div class="field-l">Fecha</div><div class="field-v"><?= e(fechaHumana($orden['fecha_recepcion'] ?? null)) ?></div></div>
        <div class="field"><div class="field-l">Equipo</div><div class="field-v"><?= e($equipoFull) ?></div></div>
        <?php if ($imeiSerie !== ''): ?><div class="field"><div class="field-l">IMEI / Serie</div><div class="field-v"><?= e($imeiSerie) ?></div></div><?php endif; ?>
        <div class="field"><div class="field-l">Estado</div><div class="field-v"><?= e($orden['estado'] ?? '') ?></div></div>

        <?php if ($desbloqueo && $desbloqueo['tipo'] === 'patron'): ?>
            <div class="patron-box">
                <?= patronSvg($desbloqueo['secuencia'], $patronPx) ?>
                <div class="patron-meta">
                    <div class="field-l">Patron</div>
                    <div><span class="patron-meta-strong">Inicio:</span> <?= e((string) $desbloqueo['secuencia'][0]) ?> &middot; <span class="patron-meta-strong">Fin:</span> <?= e((string) end($desbloqueo['secuencia'])) ?></div>
                    <div class="patron-seq"><?= e(implode(' > ', $desbloqueo['secuencia'])) ?></div>
                </div>
            </div>
        <?php elseif ($desbloqueo && $desbloqueo['tipo'] === 'clave'): ?>
            <div class="field"><div class="field-l">Clave / PIN</div><div class="field-v big"><?= e($desbloqueo['valor']) ?></div></div>
        <?php endif; ?>

        <?php if ($estadoFisico !== ''): ?><div class="field"><div class="field-l">Estado fisico al recibir</div><div class="field-v"><?= nl2br(e($estadoFisico)) ?></div></div><?php endif; ?>
        <?php if ($observaciones !== ''): ?><div class="field"><div class="field-l">Observaciones / Senas</div><div class="field-v"><?= nl2br(e($observaciones)) ?></div></div><?php endif; ?>
        <div class="field"><div class="field-l">Falla declarada</div><div class="field-v"><?= nl2br(e($falla ?: '-')) ?></div></div>
        <div class="field"><div class="field-l">Reparacion / Trabajo</div><div class="field-v"><?= nl2br(e($reparacion ?: '-')) ?></div></div>

        <hr class="doc-rule">
        <table class="totales">
            <tr><td class="t-label">Presupuesto</td><td class="t-amount"><?= e(formatearMoneda($total)) ?></td></tr>
            <tr class="t-total"><td class="t-label">Total del servicio</td><td class="t-amount"><?= e(formatearMoneda($total)) ?></td></tr>
            <tr><td class="t-label">Cobrado / Sena</td><td class="t-amount"><?= e(formatearMoneda($anticipo)) ?></td></tr>
        </table>
        <div class="saldo-box"><span>Saldo a cobrar</span><span class="saldo-amount"><?= e(formatearMoneda($saldo)) ?></span></div>

        <hr class="doc-rule-dashed">
        <div class="field-l">Garantia / condiciones del ticket</div>
        <p class="doc-note"><?= e($garantia ?: $condiciones) ?></p>
        <p class="doc-note">Orden #<?= e($orden['folio'] ?? '') ?>: presenta este ticket. Clave de entrega: <strong><?= e($orden['codigo_entrega'] ?? '') ?></strong>.</p>

        <ul class="checklist">
            <li><span class="box"></span>Equipo probado al retirar</li>
            <li><span class="box"></span>Accesorios entregados</li>
            <li><span class="box"></span>Saldo cancelado</li>
        </ul>
        <p class="doc-note">Revisar funcionamiento antes de retirarse.</p>

        <div class="firmas">
            <div class="firma">Firma cliente</div>
        </div>
    <?php endif; ?>
</div>
