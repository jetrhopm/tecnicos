<?php
$equipoNombre = trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: $orden['equipo_tipo'];
$consulta = url('/consulta?folio=' . urlencode($orden['folio']) . '&token=' . urlencode($orden['token_publico']));
$pdfPublico = absolute_url('/consulta/' . rawurlencode((string) $orden['folio']) . '/' . rawurlencode((string) $orden['token_publico']) . '/pdf');
$telefonoCliente = (string) (($orden['cliente_whatsapp'] ?? '') ?: ($orden['cliente_telefono'] ?? ''));
$whatsappPdf = linkWhatsapp($telefonoCliente, 'Hola ' . (string) $orden['cliente_nombre'] . ', te compartimos el PDF de tu orden ' . (string) $orden['folio'] . ': ' . $pdfPublico);
$puedeCancelarPagos = \App\Core\Auth::can('pagos', 'editar');
$puedeGestionarInventario = \App\Core\Auth::can('inventario', 'editar');
$totalRefaccionesUsadas = array_reduce($refaccionesUsadas ?? [], static function (float $total, array $uso): float {
    return $uso['estado'] === 'activa' ? $total + ((float) $uso['precio_unitario'] * (int) $uso['cantidad']) : $total;
}, 0.0);
?>
<div class="row g-3">
    <div class="col-xl-8">
        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h4 mb-1" data-icon="&#128203;"><?= e($orden['folio']) ?></h2>
                    <p class="text-muted mb-2"><?= e($orden['cliente_nombre']) ?> · <?= e($equipoNombre) ?></p>
                    <span class="badge-state status-<?= e($orden['estado']) ?>"><?= e($orden['estado']) ?></span>
                    <span class="badge text-bg-light"><?= e($orden['prioridad']) ?></span>
                </div>
                <div class="d-flex gap-2 flex-wrap justify-content-end order-actions">
                    <div class="dropdown">
                        <button class="btn btn-success btn-sm dropdown-toggle" data-icon="&#128241;" type="button" data-bs-toggle="dropdown" aria-expanded="false">WhatsApp</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['recibido']) ?>">Aviso de recepcion</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['cotizacion']) ?>">Solicitar autorizacion de cotizacion</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['demora']) ?>">Avisar demora / mas tiempo</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['listo']) ?>">Avisar equipo listo para entrega</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['no_reparable']) ?>">Avisar equipo no reparable</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappMensajes['entregado']) ?>">Agradecer entrega</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e($whatsappPdf) ?>">Enviar link del PDF</a></li>
                        </ul>
                    </div>
                    <a class="btn btn-outline-dark btn-sm" data-icon="&#128065;" target="_blank" href="<?= e($consulta) ?>">Portal</a>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark btn-sm dropdown-toggle" data-icon="&#128424;" type="button" data-bs-toggle="dropdown" aria-expanded="false">Imprimir</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/imprimir?formato=carta')) ?>">Hoja carta</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/imprimir?formato=80')) ?>">Ticket 80&nbsp;mm</a></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/imprimir?formato=58')) ?>">Ticket 58&nbsp;mm</a></li>
                        </ul>
                    </div>
                    <a class="btn btn-outline-dark btn-sm" data-icon="&#128196;" target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/pdf')) ?>">PDF</a>
                    <?php if (!empty($entrega['id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-outline-dark btn-sm dropdown-toggle" data-icon="&#128230;" type="button" data-bs-toggle="dropdown" aria-expanded="false">Comprobante</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" target="_blank" href="<?= e(url('/entregas/' . $entrega['id'] . '/comprobante?formato=carta')) ?>">Hoja carta</a></li>
                                <li><a class="dropdown-item" target="_blank" href="<?= e(url('/entregas/' . $entrega['id'] . '/comprobante?formato=80')) ?>">Ticket 80&nbsp;mm</a></li>
                                <li><a class="dropdown-item" target="_blank" href="<?= e(url('/entregas/' . $entrega['id'] . '/comprobante?formato=58')) ?>">Ticket 58&nbsp;mm</a></li>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <hr>
            <div class="row g-3">
                <div class="col-md-6"><strong>Falla reportada</strong><p><?= nl2br(e($orden['falla_reportada'])) ?></p></div>
                <div class="col-md-6"><strong>Diagnostico inicial</strong><p><?= nl2br(e($orden['diagnostico_inicial'] ?: '-')) ?></p></div>
                <div class="col-md-4"><strong>Telefono cliente</strong><br><?= e($orden['cliente_telefono'] ?: '-') ?></div>
                <div class="col-md-4"><strong>WhatsApp cliente</strong><br><?= e($orden['cliente_whatsapp'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Email cliente</strong><br><?= e($orden['cliente_email'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Clave entrega</strong><br><?= e($orden['codigo_entrega'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Ubicacion</strong><br><?= e($orden['ubicacion_actual'] ?? 'Recepcion') ?></div>
                <div class="col-md-4"><strong>Recepcion</strong><br><?= e(fechaHumana($orden['fecha_recepcion'])) ?></div>
                <div class="col-md-4"><strong>Entrega estimada</strong><br><?= e(fechaHumana($orden['fecha_estimada_entrega'])) ?></div>
                <div class="col-md-4"><strong>Tecnico</strong><br><?= e($orden['tecnico_nombre'] ?: 'Sin asignar') ?></div>
                <div class="col-md-4"><strong>Total</strong><br><?= e(formatearMoneda((float) $orden['costo_final'])) ?></div>
                <div class="col-md-4"><strong>Pagado</strong><br><?= e(formatearMoneda((float) $orden['anticipo'])) ?></div>
                <div class="col-md-4"><strong>Saldo</strong><br><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></div>
            </div>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128269;">Diagnostico tecnico</h2>
            <?php if ($diagnostico): ?>
                <p><strong>Diagnostico:</strong> <?= nl2br(e($diagnostico['diagnostico_tecnico'])) ?></p>
                <p><strong>Visible al cliente:</strong> <?= nl2br(e($diagnostico['diagnostico_cliente'] ?: '-')) ?></p>
                <p class="mb-0"><strong>Total sugerido:</strong> <?= e(formatearMoneda((float) $diagnostico['costo_total_sugerido'])) ?></p>
            <?php else: ?>
                <form method="post" action="<?= e(url('/diagnosticos')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Diagnostico interno</label><textarea class="form-control" name="diagnostico_tecnico" required></textarea></div>
                        <div class="col-md-6"><label class="form-label" data-icon="&#128065;">Diagnostico visible</label><textarea class="form-control" name="diagnostico_cliente"></textarea></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9888;">Causa probable</label><input class="form-control" name="causa_probable"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#36;">Mano de obra</label><input class="form-control" type="number" step="0.01" name="costo_mano_obra" value="0" data-money></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#36;">Refacciones</label><input class="form-control" type="number" step="0.01" name="costo_refacciones" value="0" data-money></div>
                    </div>
                    <button class="btn btn-primary mt-3" data-icon="&#128190;">Guardar diagnostico</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128179;">Cotizacion</h2>
            <?php $puedeCrearCotizacion = !$cotizacion || (!in_array($cotizacion['estado'], ['pendiente'], true) && !in_array($orden['estado'], ['entregada', 'cancelada'], true)); ?>
            <?php if ($cotizacion): ?>
                <div class="table-wrap mb-3">
                    <table class="table">
                        <thead><tr><th>Concepto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($cotizacion['items'] as $item): ?>
                            <tr><td><?= e($item['descripcion']) ?></td><td><?= e($item['cantidad']) ?></td><td><?= e(formatearMoneda((float) $item['precio_unitario'])) ?></td><td><?= e(formatearMoneda((float) $item['subtotal'])) ?></td></tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Estado: <strong><?= e($cotizacion['estado']) ?></strong></span>
                    <strong>Total <?= e(formatearMoneda((float) $cotizacion['total'])) ?></strong>
                </div>
                <?php if ($cotizacion['estado'] === 'pendiente'): ?>
                    <div class="d-flex gap-2 mt-3">
                        <form method="post" action="<?= e(url('/cotizaciones/' . $cotizacion['id'] . '/autorizar')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="aceptada"><button class="btn btn-success btn-sm" data-icon="&#10003;">Autorizar manual</button></form>
                        <form method="post" action="<?= e(url('/cotizaciones/' . $cotizacion['id'] . '/autorizar')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="rechazada"><button class="btn btn-outline-danger btn-sm" data-icon="&#10005;">Rechazar manual</button></form>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3 mb-0">
                        Esta cotizacion ya esta cerrada. Si necesitas cambiar importes o conceptos, genera una nueva version.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeCrearCotizacion): ?>
                <?php if ($cotizacion): ?>
                    <hr>
                    <h3 class="h6" data-icon="&#128221;">Nueva version de cotizacion</h3>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/cotizaciones')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label" data-icon="&#9671;">Tipo</label>
                            <select class="form-select" name="tipo"><option value="mano_obra">Mano de obra</option><option value="refaccion">Refaccion</option><option value="servicio">Servicio</option><option value="otro">Otro</option></select>
                        </div>
                        <div class="col-md-5"><label class="form-label" data-icon="&#9998;">Descripcion</label><input class="form-control" name="descripcion" required></div>
                        <div class="col-md-2"><label class="form-label" data-icon="&#35;">Cantidad</label><input class="form-control" type="number" step="0.01" name="cantidad" value="1"></div>
                        <div class="col-md-2"><label class="form-label" data-icon="&#36;">Precio</label><input class="form-control" type="number" step="0.01" name="precio_unitario" value="<?= e($diagnostico['costo_total_sugerido'] ?? 0) ?>" data-money></div>
                        <div class="col-md-3"><label class="form-label" data-icon="&#37;">Descuento</label><input class="form-control" type="number" step="0.01" name="descuento" value="0" data-money></div>
                        <div class="col-md-3"><label class="form-label" data-icon="&#37;">IVA</label><input class="form-control" type="number" step="0.01" name="iva" value="0" data-money></div>
                        <div class="col-md-3"><label class="form-label" data-icon="&#128197;">Vigencia</label><input class="form-control" type="date" name="vigencia"></div>
                        <div class="col-md-12"><label class="form-label" data-icon="&#9998;">Terminos</label><textarea class="form-control" name="terminos"></textarea></div>
                    </div>
                    <button class="btn btn-primary mt-3" data-icon="&#128179;">Generar cotizacion</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-xl-4">
        <?php $desbloqueo = patronDesbloqueo($orden['password_equipo'] ?? ''); ?>
        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128274;">Desbloqueo del equipo</h2>
            <?php if ($desbloqueo && $desbloqueo['tipo'] === 'patron'): ?>
                <div class="unlock-box"><?= patronSvg($desbloqueo['secuencia'], 132) ?></div>
                <div class="mt-2"><strong>Secuencia:</strong> <span class="patron-seq"><?= e(implode(' → ', $desbloqueo['secuencia'])) ?></span></div>
                <div class="small text-muted"><strong>Inicio:</strong> <?= e((string) $desbloqueo['secuencia'][0]) ?> &middot; <strong>Fin:</strong> <?= e((string) end($desbloqueo['secuencia'])) ?></div>
            <?php elseif ($desbloqueo && $desbloqueo['tipo'] === 'clave'): ?>
                <div class="unlock-code"><?= e($desbloqueo['valor']) ?></div>
                <div class="small text-muted">Clave / PIN del equipo</div>
            <?php else: ?>
                <p class="small text-muted mb-0">No se registro patron ni clave de desbloqueo para este equipo.</p>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#9889;">Acciones rapidas</h2>
            <form class="mb-3" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/estado')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#9679;">Cambiar estado</label>
                <div class="input-group">
                    <select class="form-select" name="estado">
                        <?php foreach ($estados as $estado): ?>
                            <option value="<?= e($estado) ?>" <?= $orden['estado'] === $estado ? 'selected' : '' ?>><?= e($estado) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-dark" data-icon="&#8635;">Cambiar</button>
                </div>
            </form>
            <form method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/tecnico')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#128295;">Asignar tecnico</label>
                <div class="input-group">
                    <select class="form-select" name="tecnico_id">
                        <option value="">Sin asignar</option>
                        <?php foreach ($tecnicos as $tecnico): ?>
                            <option value="<?= e($tecnico['id']) ?>" <?= (int) $orden['tecnico_id'] === (int) $tecnico['id'] ? 'selected' : '' ?>><?= e($tecnico['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-dark" data-icon="&#10003;">Asignar</button>
                </div>
            </form>
        </div>

        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h5 mb-0" data-icon="&#128230;">Refacciones usadas</h2>
                <span class="badge text-bg-light"><?= e(formatearMoneda($totalRefaccionesUsadas)) ?></span>
            </div>

            <?php if ($puedeGestionarInventario && !in_array($orden['estado'], ['entregada', 'cancelada'], true)): ?>
                <form class="row g-2 mb-3" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/refacciones')) ?>">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" data-icon="&#128295;">Refaccion</label>
                        <select class="form-select" name="refaccion_id" required>
                            <option value="">Selecciona refaccion...</option>
                            <?php foreach ($refaccionesDisponibles ?? [] as $refaccion): ?>
                                <option value="<?= e($refaccion['id']) ?>">
                                    <?= e($refaccion['nombre']) ?> · <?= e($refaccion['sku']) ?> · Stock <?= e($refaccion['stock_actual']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-4">
                        <label class="form-label" data-icon="&#35;">Cant.</label>
                        <input class="form-control" type="number" min="1" step="1" name="cantidad" value="1" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label" data-icon="&#36;">Precio venta</label>
                        <input class="form-control" type="number" min="0" step="0.01" name="precio_unitario" placeholder="Usa precio de inventario" data-money>
                    </div>
                    <div class="col-12">
                        <input class="form-control" name="motivo" placeholder="Motivo / trabajo donde se instalo">
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary" data-icon="&#8722;">Aplicar y descontar stock</button>
                    </div>
                </form>
            <?php endif; ?>

            <?php if (!empty($refaccionesUsadas)): ?>
                <div class="table-wrap">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>Refaccion</th><th>Cant.</th><th>Importe</th><th>Estado</th></tr></thead>
                        <tbody>
                        <?php foreach ($refaccionesUsadas as $uso): ?>
                            <tr>
                                <td>
                                    <strong><?= e($uso['nombre']) ?></strong><br>
                                    <small class="text-muted"><?= e($uso['sku']) ?></small>
                                    <?php if ($uso['estado'] === 'cancelada' && $uso['motivo_cancelacion']): ?>
                                        <div class="small text-danger"><?= e($uso['motivo_cancelacion']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($uso['cantidad']) ?></td>
                                <td><?= e(formatearMoneda((float) $uso['precio_unitario'] * (int) $uso['cantidad'])) ?></td>
                                <td>
                                    <span class="badge <?= $uso['estado'] === 'activa' ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= e($uso['estado']) ?></span>
                                    <?php if ($puedeGestionarInventario && $uso['estado'] === 'activa'): ?>
                                        <form class="mt-2" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/refacciones/' . $uso['id'] . '/cancelar')) ?>" data-confirm="Cancelar esta refaccion y devolver el stock">
                                            <?= csrf_field() ?>
                                            <input class="form-control form-control-sm mb-1" name="motivo_cancelacion" placeholder="Motivo" required>
                                            <button class="btn btn-outline-danger btn-sm w-100" data-icon="&#8634;">Cancelar</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-0">Aun no se han aplicado refacciones a esta orden.</p>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128247;">Evidencia y aceptacion</h2>
            <?php if (!empty($orden['firma_recepcion'])): ?>
                <div class="alert alert-success py-2 small mb-3"><?= e($orden['firma_recepcion']) ?></div>
            <?php else: ?>
                <p class="small text-muted">Aun no se registra la aceptacion del cliente. Sube la foto del ticket firmado como evidencia.</p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="<?= e(url('/ordenes/' . $orden['id'] . '/evidencia')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#128247;">Foto del ticket firmado</label>
                <input class="form-control mb-2" type="file" name="evidencia" accept="image/*" capture="environment" required>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="acepta_terminos" name="acepta_terminos" value="1" checked>
                    <label class="form-check-label small" for="acepta_terminos">El cliente acepta el presupuesto y los terminos y condiciones.</label>
                </div>
                <input class="form-control mb-2" name="nota_evidencia" placeholder="Nota (opcional)">
                <button class="btn btn-primary w-100" data-icon="&#128228;">Subir evidencia</button>
            </form>
            <?php if (!empty($evidencias)): ?>
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php foreach ($evidencias as $ev): ?>
                        <a target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/evidencia/' . $ev['id'])) ?>" title="<?= e($ev['nombre_original']) ?>">
                            <img src="<?= e(url('/ordenes/' . $orden['id'] . '/evidencia/' . $ev['id'])) ?>" alt="Evidencia de la orden" width="70" height="70" style="object-fit:cover;border-radius:8px;border:1px solid var(--line);">
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128179;">Registrar pago</h2>
            <form method="post" action="<?= e(url('/pagos')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                <label class="form-label" data-icon="&#36;">Monto</label>
                <input class="form-control mb-2" type="number" step="0.01" min="0.01" max="<?= e((string) max(0, (float) $orden['saldo_pendiente'])) ?>" name="monto" value="<?= e((string) max(0, (float) $orden['saldo_pendiente'])) ?>" required data-money>
                <div class="form-text mb-2">No debe superar el saldo pendiente.</div>
                <label class="form-label" data-icon="&#9679;">Metodo</label>
                <select class="form-select mb-2" name="metodo">
                    <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?><option value="<?= e($metodo) ?>"><?= e($metodo) ?></option><?php endforeach; ?>
                </select>
                <label class="form-label" data-icon="&#35;">Referencia</label>
                <input class="form-control mb-2" name="referencia">
                <button class="btn btn-primary w-100" data-icon="&#128190;">Guardar pago</button>
            </form>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128179;">Pagos</h2>
            <?php foreach ($pagos as $pago): ?>
                <?php $cancelado = ($pago['estado'] ?? 'activo') === 'cancelado'; ?>
                <div class="border-bottom py-2">
                    <div class="d-flex justify-content-between gap-2">
                        <span>
                            <?= e(fechaHumana($pago['created_at'])) ?>
                            <?php if ($cancelado): ?><span class="badge text-bg-danger ms-1">Cancelado</span><?php endif; ?>
                            <br>
                            <small class="text-muted"><?= e($pago['metodo']) ?> &middot; <?= e($pago['usuario_nombre'] ?? 'sistema') ?></small>
                        </span>
                        <strong class="<?= $cancelado ? 'text-decoration-line-through text-muted' : '' ?>"><?= e(formatearMoneda((float) $pago['monto'])) ?></strong>
                    </div>
                    <?php if ($cancelado): ?>
                        <div class="small text-muted mt-1">
                            Motivo: <?= e($pago['motivo_cancelacion'] ?? '-') ?>
                            <?php if (!empty($pago['cancelado_por_nombre'])): ?>
                                &middot; Cancelo: <?= e($pago['cancelado_por_nombre']) ?>
                            <?php endif; ?>
                        </div>
                    <?php elseif ($puedeCancelarPagos): ?>
                        <form class="mt-2" method="post" action="<?= e(url('/pagos/' . $pago['id'] . '/cancelar')) ?>" data-confirm="Cancelar este pago y recalcular el saldo de la orden">
                            <?= csrf_field() ?>
                            <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                            <div class="input-group input-group-sm">
                                <input class="form-control" name="motivo_cancelacion" placeholder="Motivo de cancelacion" required>
                                <button class="btn btn-outline-danger" data-icon="&#10005;">Cancelar</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="glass-card">
            <h2 class="h5" data-icon="&#128220;">Bitacora de la orden</h2>
            <?php
            $accionLabel = [
                'crear' => 'Orden creada',
                'editar' => 'Orden editada',
                'cambiar_estado' => 'Cambio de estado',
                'asignar_tecnico' => 'Tecnico asignado',
                'entregar' => 'Equipo entregado',
                'evidencia_subida' => 'Evidencia subida',
                'terminos_aceptados' => 'Cliente acepto terminos',
                'pdf_generado' => 'PDF generado',
                'pago_cancelado' => 'Pago cancelado',
            ];
            ?>
            <?php if (empty($bitacora)): ?>
                <p class="small text-muted mb-0">Sin movimientos registrados aun.</p>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($bitacora as $evento): ?>
                        <?php
                        // Detalle segun la accion: para cambio de estado se muestra
                        // el estado anterior y el nuevo tomados de la auditoria.
                        $detalle = '';
                        $antes = json_decode((string) ($evento['datos_anteriores'] ?? ''), true) ?: [];
                        $nuevos = json_decode((string) ($evento['datos_nuevos'] ?? ''), true) ?: [];
                        if ($evento['accion'] === 'cambiar_estado' && !empty($nuevos['estado'])) {
                            $de = str_replace('_', ' ', (string) ($antes['estado'] ?? ''));
                            $a = str_replace('_', ' ', (string) $nuevos['estado']);
                            $detalle = ($de !== '' ? $de . ' → ' : '') . $a;
                        } elseif ($evento['accion'] === 'terminos_aceptados') {
                            $detalle = 'Presupuesto y terminos';
                        }
                        ?>
                        <div class="timeline-item">
                            <strong><?= e($accionLabel[$evento['accion']] ?? ucfirst(str_replace('_', ' ', (string) $evento['accion']))) ?></strong>
                            <?php if ($detalle !== ''): ?>
                                <div class="small"><?= e($detalle) ?></div>
                            <?php endif; ?>
                            <div class="small text-muted"><?= e(fechaHumana($evento['created_at'])) ?> &middot; <?= e($evento['usuario'] ?: 'sistema') ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
