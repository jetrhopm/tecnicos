<?php
$turno = $corte['turno'];
$metodos = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'tarjeta' => 'Tarjeta', 'otro' => 'Otro'];
?>
<section class="print-sheet">
    <div class="print-header">
        <div>
            <h1><?= e($negocio['nombre'] ?? 'Servicio Tecnico') ?></h1>
            <p><?= e($negocio['telefono'] ?? '') ?> <?= !empty($negocio['email']) ? ' · ' . e($negocio['email']) : '' ?></p>
        </div>
        <div class="text-end">
            <strong>Corte de caja</strong><br>
            <?= e($turno['folio']) ?><br>
            <?= e($turno['estado']) ?>
        </div>
    </div>

    <div class="print-grid two">
        <div><strong>Apertura</strong><br><?= e(fechaHumana($turno['opened_at'])) ?><br><?= e($turno['abierto_por_nombre'] ?? '') ?></div>
        <div><strong>Cierre</strong><br><?= e($turno['closed_at'] ? fechaHumana($turno['closed_at']) : 'Caja abierta') ?><br><?= e($turno['cerrado_por_nombre'] ?? '') ?></div>
    </div>

    <table class="print-table">
        <thead><tr><th>Metodo</th><th>Ops.</th><th>Ingresos</th><th>Esperado</th><th>Contado</th></tr></thead>
        <tbody>
        <?php foreach ($metodos as $key => $label): ?>
            <?php $contadoKey = $key . '_contado'; ?>
            <tr>
                <td><?= e($label) ?></td>
                <td><?= e((string) ($corte['resumen'][$key]['operaciones'] ?? 0)) ?></td>
                <td><?= e(formatearMoneda((float) ($corte['resumen'][$key]['total'] ?? 0))) ?></td>
                <td><?= e(formatearMoneda((float) ($corte['esperado'][$key] ?? 0))) ?></td>
                <td><?= e(formatearMoneda((float) ($turno[$contadoKey] ?? 0))) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="print-grid three">
        <div><strong>Fondo inicial</strong><br><?= e(formatearMoneda((float) $turno['fondo_inicial'])) ?></div>
        <div><strong>Retiros</strong><br><?= e(formatearMoneda((float) $corte['retiros'])) ?></div>
        <div><strong>Diferencia</strong><br><?= e(formatearMoneda((float) $turno['diferencia'])) ?></div>
    </div>

    <?php if (!empty($turno['observaciones'])): ?>
        <div class="print-box"><strong>Observaciones</strong><br><?= e($turno['observaciones']) ?></div>
    <?php endif; ?>

    <div class="print-signatures">
        <div>Entrega caja</div>
        <div>Recibe / revisa</div>
    </div>
</section>
