<?php
/*
 * Comprobante de entrega imprimible.
 * Formatos: carta y termico 80/58 mm (mismos estilos que la orden).
 * $formato lo pasa el controlador; $config trae los datos del negocio.
 */
$formato = in_array(($formato ?? 'carta'), ['carta', '80', '56'], true) ? $formato : 'carta';
$printSize = $formato;
$docClass = 'doc doc-' . $formato;

$entrega = $entrega ?? [];
$config = $config ?? [];

$equipo = trim((string) (($entrega['equipo_marca'] ?? '') . ' ' . ($entrega['equipo_modelo'] ?? ''))) ?: ($entrega['equipo_tipo'] ?? 'Equipo');
$saldoAntes = (float) ($entrega['saldo_antes'] ?? 0);
$pagoFinal = (float) ($entrega['pago_final'] ?? 0);
$saldoDespues = (float) ($entrega['saldo_despues'] ?? 0);
$metodo = trim((string) ($entrega['metodo_pago'] ?? ''));
$referencia = trim((string) ($entrega['referencia_pago'] ?? ''));
$observaciones = trim((string) ($entrega['observaciones'] ?? ''));

$negNombre = trim((string) ($config['negocio.nombre'] ?? '')) ?: 'Servicio Tecnico';
$negTel = trim((string) ($config['negocio.telefono'] ?? ''));
$negWa = trim((string) ($config['negocio.whatsapp'] ?? ''));
$negDir = trim((string) ($config['negocio.direccion'] ?? ''));
$logo = config_asset_src((string) ($config['negocio.logo_url'] ?? ''));
$garantia = trim((string) ($config['ticket.garantia'] ?? $config['legal.politica_garantia'] ?? ''));
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
        <div class="doc-orden">ENTREGA #<?= e($entrega['folio'] ?? '') ?></div>
        <div class="doc-subtitle">Comprobante de retiro del equipo</div>
    </div>
    <hr class="doc-rule">

    <?php if ($formato === 'carta'): ?>
        <div class="doc-grid">
            <div class="cell cell-8"><div class="field-l">Cliente</div><div class="field-v"><?= e($entrega['cliente_nombre'] ?? '') ?></div></div>
            <div class="cell cell-4"><div class="field-l">Telefono</div><div class="field-v"><?= e($entrega['cliente_telefono'] ?? '') ?></div></div>

            <div class="cell cell-8"><div class="field-l">Equipo</div><div class="field-v"><?= e($equipo) ?></div></div>
            <div class="cell cell-4"><div class="field-l">Clave de entrega</div><div class="field-v"><?= e($entrega['codigo_entrega'] ?? '') ?></div></div>

            <div class="cell cell-6"><div class="field-l">Entregado por (taller)</div><div class="field-v"><?= e($entrega['usuario_nombre'] ?? '') ?></div></div>
            <div class="cell cell-6"><div class="field-l">Fecha y hora de entrega</div><div class="field-v"><?= e(fechaHumana($entrega['created_at'] ?? null)) ?></div></div>

            <div class="cell cell-6"><div class="field-l">Recibido por (cliente)</div><div class="field-v"><?= e($entrega['recibido_por_nombre'] ?? '') ?></div></div>
            <div class="cell cell-6"><div class="field-l">Identificacion</div><div class="field-v"><?= e(($entrega['recibido_por_identificacion'] ?? '') ?: '-') ?></div></div>

            <?php if ($observaciones !== ''): ?>
                <div class="cell cell-12"><div class="field-l">Observaciones de entrega</div><div class="field-v"><?= nl2br(e($observaciones)) ?></div></div>
            <?php endif; ?>
        </div>

        <div style="display:flex; gap:16px; margin-top:8px;">
            <div style="flex:1.4;">
                <div class="field-l">Garantia y condiciones</div>
                <div class="doc-terms"><?= nl2br(e($garantia)) ?></div>
            </div>
            <div style="flex:1;">
                <table class="totales">
                    <tr><td class="t-label">Saldo antes</td><td class="t-amount"><?= e(formatearMoneda($saldoAntes)) ?></td></tr>
                    <tr><td class="t-label">Pago final<?= $metodo !== '' ? ' (' . e($metodo) . ')' : '' ?></td><td class="t-amount"><?= e(formatearMoneda($pagoFinal)) ?></td></tr>
                </table>
                <div class="saldo-box"><span>Saldo pendiente</span><span class="saldo-amount"><?= e(formatearMoneda($saldoDespues)) ?></span></div>
            </div>
        </div>

        <div class="firmas">
            <div class="firma">Entrega (taller)</div>
            <div class="firma">Recibe (cliente)</div>
        </div>
        <p class="doc-note" style="margin-top:6px;">El equipo fue liberado con la clave de entrega presentada por el cliente. Se registra quien entrego, quien recibio, fecha y saldo como evidencia operativa.</p>

    <?php else: ?>
        <div class="field"><div class="field-l">Fecha entrega</div><div class="field-v"><?= e(fechaHumana($entrega['created_at'] ?? null)) ?></div></div>
        <div class="field"><div class="field-l">Cliente</div><div class="field-v"><?= e($entrega['cliente_nombre'] ?? '') ?></div></div>
        <div class="field"><div class="field-l">Equipo</div><div class="field-v"><?= e($equipo) ?></div></div>
        <div class="field"><div class="field-l">Clave de entrega</div><div class="field-v"><?= e($entrega['codigo_entrega'] ?? '') ?></div></div>
        <div class="field"><div class="field-l">Entregado por</div><div class="field-v"><?= e($entrega['usuario_nombre'] ?? '') ?></div></div>
        <div class="field"><div class="field-l">Recibido por</div><div class="field-v"><?= e($entrega['recibido_por_nombre'] ?? '') ?><?= !empty($entrega['recibido_por_identificacion']) ? ' (' . e($entrega['recibido_por_identificacion']) . ')' : '' ?></div></div>
        <?php if ($observaciones !== ''): ?><div class="field"><div class="field-l">Observaciones</div><div class="field-v"><?= nl2br(e($observaciones)) ?></div></div><?php endif; ?>

        <hr class="doc-rule">
        <table class="totales">
            <tr><td class="t-label">Saldo antes</td><td class="t-amount"><?= e(formatearMoneda($saldoAntes)) ?></td></tr>
            <tr><td class="t-label">Pago final<?= $metodo !== '' ? ' (' . e($metodo) . ')' : '' ?></td><td class="t-amount"><?= e(formatearMoneda($pagoFinal)) ?></td></tr>
        </table>
        <div class="saldo-box"><span>Saldo pendiente</span><span class="saldo-amount"><?= e(formatearMoneda($saldoDespues)) ?></span></div>

        <?php if ($referencia !== ''): ?><p class="doc-note">Referencia de pago: <?= e($referencia) ?></p><?php endif; ?>

        <hr class="doc-rule-dashed">
        <div class="field-l">Garantia / condiciones</div>
        <p class="doc-note"><?= e($garantia) ?></p>

        <ul class="checklist">
            <li><span class="box"></span>Equipo probado y funcionando al retirar</li>
            <li><span class="box"></span>Accesorios entregados</li>
            <li><span class="box"></span>Conforme con el servicio</li>
        </ul>

        <div class="firmas">
            <div class="firma">Recibe (cliente)</div>
        </div>
    <?php endif; ?>
</div>
