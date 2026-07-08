<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Ticket') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; background: #f5f7fb; color: #111827; font-family: Arial, sans-serif; }
        .toolbar { padding: 12px; text-align: center; }
        .ticket { width: 80mm; margin: 12px auto; padding: 10px; background: #fff; border: 1px solid #d8e1ef; }
        .center { text-align: center; }
        h1 { margin: 0; font-size: 16px; }
        .muted { color: #4b5563; font-size: 11px; }
        .line { border-top: 1px dashed #9ca3af; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { padding: 3px 0; vertical-align: top; }
        th { text-align: left; border-bottom: 1px solid #d1d5db; }
        .right { text-align: right; }
        .totals td { font-size: 12px; }
        .total td { font-size: 15px; font-weight: 700; border-top: 1px solid #111827; padding-top: 5px; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .ticket { margin: 0; border: 0; width: 80mm; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">Imprimir</button>
    <a href="<?= e(url('/punto-venta')) ?>">Volver</a>
</div>
<main class="ticket">
    <div class="center">
        <h1><?= e((string) $negocio['nombre']) ?></h1>
        <?php if (!empty($negocio['telefono']) || !empty($negocio['whatsapp'])): ?>
            <div class="muted">Tel: <?= e((string) $negocio['telefono']) ?> WhatsApp: <?= e((string) $negocio['whatsapp']) ?></div>
        <?php endif; ?>
        <?php if (!empty($negocio['email'])): ?><div class="muted"><?= e((string) $negocio['email']) ?></div><?php endif; ?>
        <?php if (!empty($negocio['direccion'])): ?><div class="muted"><?= e((string) $negocio['direccion']) ?></div><?php endif; ?>
    </div>
    <div class="line"></div>
    <table>
        <tr><td>Folio</td><td class="right"><?= e($venta['folio']) ?></td></tr>
        <tr><td>Fecha</td><td class="right"><?= e(fechaHumana($venta['created_at'])) ?></td></tr>
        <tr><td>Cajero</td><td class="right"><?= e($venta['usuario_nombre'] ?: 'sistema') ?></td></tr>
        <?php if (!empty($venta['cliente_nombre'])): ?><tr><td>Cliente</td><td class="right"><?= e($venta['cliente_nombre']) ?></td></tr><?php endif; ?>
        <?php if (!empty($venta['cliente_telefono'])): ?><tr><td>Telefono</td><td class="right"><?= e($venta['cliente_telefono']) ?></td></tr><?php endif; ?>
    </table>
    <div class="line"></div>
    <table>
        <thead>
            <tr><th>Producto</th><th class="right">Imp.</th></tr>
        </thead>
        <tbody>
        <?php foreach ($venta['items'] as $item): ?>
            <tr>
                <td>
                    <?= e($item['descripcion']) ?><br>
                    <span class="muted"><?= e($item['sku']) ?> - <?= e($item['cantidad']) ?> x <?= e(formatearMoneda((float) $item['precio_unitario'])) ?></span>
                </td>
                <td class="right"><?= e(formatearMoneda((float) $item['subtotal'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <div class="line"></div>
    <table class="totals">
        <tr><td>Subtotal</td><td class="right"><?= e(formatearMoneda((float) $venta['subtotal'])) ?></td></tr>
        <tr><td>Descuento</td><td class="right"><?= e(formatearMoneda((float) $venta['descuento'])) ?></td></tr>
        <tr class="total"><td>Total</td><td class="right"><?= e(formatearMoneda((float) $venta['total'])) ?></td></tr>
        <tr><td>Metodo</td><td class="right"><?= e($venta['metodo_pago']) ?></td></tr>
        <?php if (!empty($venta['referencia'])): ?><tr><td>Referencia</td><td class="right"><?= e($venta['referencia']) ?></td></tr><?php endif; ?>
    </table>
    <?php if (!empty($venta['notas'])): ?>
        <div class="line"></div>
        <div class="muted"><?= nl2br(e($venta['notas'])) ?></div>
    <?php endif; ?>
    <div class="line"></div>
    <div class="center muted">Gracias por su compra.</div>
</main>
</body>
</html>
