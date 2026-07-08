<?php
$equipoNombre = trim(($orden['equipo_marca'] ?? '') . ' ' . ($orden['equipo_modelo'] ?? '')) ?: $orden['equipo_tipo'];
$consulta = url('/consulta?folio=' . urlencode($orden['folio']) . '&token=' . urlencode($orden['token_publico']));
$pdfPublico = absolute_url('/consulta/' . rawurlencode((string) $orden['folio']) . '/' . rawurlencode((string) $orden['token_publico']) . '/pdf');
$telefonoCliente = (string) (($orden['cliente_whatsapp'] ?? '') ?: ($orden['cliente_telefono'] ?? ''));
$whatsappPdf = linkWhatsapp($telefonoCliente, 'Hola ' . (string) $orden['cliente_nombre'] . ', te compartimos el PDF de tu orden ' . (string) $orden['folio'] . ': ' . $pdfPublico);
$puedeCancelarPagos = \App\Core\Auth::can('pagos', 'editar');
$puedeGestionarInventario = \App\Core\Auth::can('inventario', 'editar');
$puedeCrearCotizacionRol = \App\Core\Auth::can('cotizaciones', 'crear');
$puedeAutorizarCotizacion = \App\Core\Auth::can('cotizaciones', 'autorizar');
$totalRefaccionesUsadas = array_reduce($refaccionesUsadas ?? [], static function (float $total, array $uso): float {
    return $uso['estado'] === 'activa' ? $total + ((float) $uso['precio_unitario'] * (int) $uso['cantidad']) : $total;
}, 0.0);
$help = static function (string $texto, string $ejemplo = ''): string {
    $contenido = $texto . ($ejemplo !== '' ? ' Ejemplo: ' . $ejemplo : '');
    return '<button type="button" class="help-tip" data-help-popover data-bs-toggle="popover" data-bs-title="Ayuda" data-bs-content="' . e($contenido) . '" aria-label="Ayuda">?</button>';
};
?>
<div class="orden-detalle">
        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h2 class="h4 mb-1" data-icon="&#128203;"><?= e($orden['folio']) ?> <?= $help('Folio unico de la orden. Sirve para buscar la reparacion, imprimir comprobantes y consultar historial.', 'ST-2026-00045') ?></h2>
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
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" target="_blank" href="<?= e(url('/ordenes/' . $orden['id'] . '/etiqueta')) ?>">Etiqueta equipo</a></li>
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
                <div class="col-md-6"><strong>Falla reportada <?= $help('Lo que el cliente dijo que le pasa al equipo. No es el diagnostico tecnico definitivo.', 'No carga, pantalla rota, se apaga') ?></strong><p><?= nl2br(e($orden['falla_reportada'])) ?></p></div>
                <div class="col-md-6"><strong>Diagnostico inicial <?= $help('Primera revision al recibir. Puede cambiar cuando el tecnico haga pruebas completas.', 'Posible centro de carga danado') ?></strong><p><?= nl2br(e($orden['diagnostico_inicial'] ?: '-')) ?></p></div>
                <div class="col-md-4"><strong>Telefono cliente <?= $help('Telefono general del cliente para llamadas o identificacion.', '4491234567') ?></strong><br><?= e($orden['cliente_telefono'] ?: '-') ?></div>
                <div class="col-md-4"><strong>WhatsApp cliente <?= $help('Numero usado para enviar mensajes prellenados desde el boton WhatsApp.', '524491234567') ?></strong><br><?= e($orden['cliente_whatsapp'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Email cliente <?= $help('Correo del cliente. Es opcional y sirve para contacto o comprobantes futuros.', 'cliente@correo.com') ?></strong><br><?= e($orden['cliente_email'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Clave entrega <?= $help('Llave del codigo de barras. Se usa en Entregas para localizar y liberar el equipo correcto.', 'ENT-8K4P2Z') ?></strong><br><?= e($orden['codigo_entrega'] ?: '-') ?></div>
                <div class="col-md-4"><strong>Ubicacion <?= $help('Lugar fisico donde esta el equipo dentro del taller.', 'Recepcion, Mesa 2, Caja, Entregado') ?></strong><br><?= e($orden['ubicacion_actual'] ?? 'Recepcion') ?></div>
                <div class="col-md-4"><strong>Recepcion <?= $help('Fecha y hora en que se registro la entrada del equipo.', '08/07/2026 10:30') ?></strong><br><?= e(fechaHumana($orden['fecha_recepcion'])) ?></div>
                <div class="col-md-4"><strong>Entrega estimada <?= $help('Fecha prometida o aproximada para entregar. Sirve para agenda y seguimiento.', '10/07/2026') ?></strong><br><?= e(fechaHumana($orden['fecha_estimada_entrega'])) ?></div>
                <div class="col-md-4"><strong>Tecnico <?= $help('Persona asignada para diagnosticar o reparar esta orden.', 'Tecnico Demo') ?></strong><br><?= e($orden['tecnico_nombre'] ?: 'Sin asignar') ?></div>
                <div class="col-md-4"><strong>Total <?= $help('Importe total actual de la orden. Se actualiza al generar cotizacion o registrar cambios de cobro.', '$1,450.00') ?></strong><br><?= e(formatearMoneda((float) $orden['costo_final'])) ?></div>
                <div class="col-md-4"><strong>Pagado <?= $help('Suma de pagos activos registrados para esta orden.', '$500.00 de anticipo') ?></strong><br><?= e(formatearMoneda((float) $orden['anticipo'])) ?></div>
                <div class="col-md-4"><strong>Saldo <?= $help('Cantidad pendiente por cobrar. La entrega bloquea si queda saldo pendiente.', '$950.00') ?></strong><br><?= e(formatearMoneda((float) $orden['saldo_pendiente'])) ?></div>
            </div>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128269;">Diagnostico tecnico <?= $help('Resultado de la revision del tecnico. Puede tener version interna y version visible para cliente.', 'Interno: corto en placa. Cliente: requiere revision avanzada') ?></h2>
            <?php if ($diagnostico): ?>
                <p><strong>Diagnostico <?= $help('Detalle tecnico interno. No debe enviarse al cliente si contiene notas sensibles.', 'Falla por humedad en conector') ?>:</strong> <?= nl2br(e($diagnostico['diagnostico_tecnico'])) ?></p>
                <p><strong>Visible al cliente <?= $help('Resumen explicado de forma clara para el cliente.', 'Se requiere cambio de centro de carga') ?>:</strong> <?= nl2br(e($diagnostico['diagnostico_cliente'] ?: '-')) ?></p>
                <p><strong>Pruebas realizadas <?= $help('Acciones tecnicas hechas para confirmar la falla.', 'Prueba de carga, prueba de pantalla, revision con fuente') ?>:</strong> <?= nl2br(e($diagnostico['pruebas_realizadas'] ?: '-')) ?></p>
                <p><strong>Piezas necesarias <?= $help('Refacciones o materiales que el tecnico considera necesarios. El precio se captura en Cotizacion.', 'Pantalla, centro de carga, flex') ?>:</strong> <?= nl2br(e($diagnostico['piezas_necesarias'] ?: '-')) ?></p>
                <p class="mb-0"><strong>Tiempo estimado <?= $help('Tiempo aproximado de trabajo o espera antes de entregar.', '2 dias habiles') ?>:</strong> <?= e($diagnostico['tiempo_estimado'] ?: '-') ?></p>
                <?php if ((float) ($diagnostico['costo_total_sugerido'] ?? 0) > 0): ?>
                    <p class="small text-muted mt-2 mb-0">Este diagnostico conserva un monto sugerido de versiones anteriores: <?= e(formatearMoneda((float) $diagnostico['costo_total_sugerido'])) ?>. Los importes nuevos deben capturarse desde Cotizacion.</p>
                <?php endif; ?>
            <?php else: ?>
                <form method="post" action="<?= e(url('/diagnosticos')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label" data-icon="&#9998;">Diagnostico interno <?= $help('Notas tecnicas para el taller. No aparecen en portal publico.', 'Flex danado, posible humedad') ?></label><textarea class="form-control" name="diagnostico_tecnico" required></textarea></div>
                        <div class="col-md-6"><label class="form-label" data-icon="&#128065;">Diagnostico visible <?= $help('Texto que si puede entender/ver el cliente.', 'La pantalla requiere reemplazo') ?></label><textarea class="form-control" name="diagnostico_cliente"></textarea></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9888;">Causa probable <?= $help('Origen probable de la falla segun pruebas.', 'Golpe, humedad, desgaste, variacion de voltaje') ?></label><input class="form-control" name="causa_probable"></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#128300;">Pruebas realizadas <?= $help('Describe como se reviso el equipo. No lleva precio.', 'Prueba de carga y pantalla') ?></label><textarea class="form-control" name="pruebas_realizadas"></textarea></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#128230;">Piezas necesarias <?= $help('Lista tecnica de piezas probables. El costo se captura en Cotizacion.', 'Display, bateria, flex') ?></label><textarea class="form-control" name="piezas_necesarias"></textarea></div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9201;">Tiempo estimado <?= $help('Tiempo aproximado para reparar o conseguir piezas.', '24 a 48 horas') ?></label><input class="form-control" name="tiempo_estimado"></div>
                    </div>
                    <button class="btn btn-primary mt-3" data-icon="&#128190;">Guardar diagnostico</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128179;">Cotizacion <?= $help('Propuesta economica que el cliente debe aceptar o rechazar antes de reparar, salvo permiso especial.', 'Mano de obra + refaccion + IVA') ?></h2>
            <?php $puedeCrearCotizacion = !$cotizacion || (!in_array($cotizacion['estado'], ['pendiente'], true) && !in_array($orden['estado'], ['entregada', 'cancelada'], true)); ?>
            <?php if ($cotizacion): ?>
                <div class="table-wrap mb-3">
                    <table class="table">
                        <thead><tr><th>Concepto</th><th>Cantidad</th><th>Precio</th><th>Subtotal</th></tr></thead>
                        <tbody>
                        <?php foreach ($cotizacion['items'] as $item): ?>
                            <tr>
                                <td>
                                    <?= e($item['descripcion']) ?>
                                    <?php if (!empty($item['refaccion_id'])): ?>
                                        <br><small class="text-muted">Inventario: <?= e($item['refaccion_sku'] ?? '') ?> · Stock actual <?= e($item['refaccion_stock_actual'] ?? '0') ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($item['cantidad']) ?></td>
                                <td><?= e(formatearMoneda((float) $item['precio_unitario'])) ?></td>
                                <td><?= e(formatearMoneda((float) $item['subtotal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Estado <?= $help('Indica si la cotizacion sigue pendiente, fue aceptada, rechazada o vencio.', 'pendiente') ?>: <strong><?= e($cotizacion['estado']) ?></strong></span>
                    <strong>Total <?= $help('Importe total de la version actual de cotizacion.', '$1,450.00') ?> <?= e(formatearMoneda((float) $cotizacion['total'])) ?></strong>
                </div>
                <?php if ($cotizacion['estado'] === 'pendiente' && $puedeAutorizarCotizacion): ?>
                    <div class="d-flex gap-2 mt-3">
                        <form method="post" action="<?= e(url('/cotizaciones/' . $cotizacion['id'] . '/autorizar')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="aceptada"><button class="btn btn-success btn-sm" data-icon="&#10003;">Autorizar manual</button></form>
                        <form method="post" action="<?= e(url('/cotizaciones/' . $cotizacion['id'] . '/autorizar')) ?>"><?= csrf_field() ?><input type="hidden" name="estado" value="rechazada"><button class="btn btn-outline-danger btn-sm" data-icon="&#10005;">Rechazar manual</button></form>
                    </div>
                <?php elseif ($cotizacion['estado'] === 'pendiente'): ?>
                    <div class="alert alert-warning mt-3 mb-0">
                        Cotizacion pendiente de autorizacion. Tu rol puede prepararla, pero no autorizarla manualmente.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mt-3 mb-0">
                        Esta cotizacion ya esta cerrada. Si necesitas cambiar importes o conceptos, genera una nueva version.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($puedeCrearCotizacion && $puedeCrearCotizacionRol): ?>
                <?php if ($cotizacion): ?>
                    <hr>
                    <h3 class="h6" data-icon="&#128221;">Nueva version de cotizacion</h3>
                <?php endif; ?>
                <form method="post" action="<?= e(url('/cotizaciones')) ?>" data-quote-form>
                    <?= csrf_field() ?>
                    <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                    <div class="row g-3">
                        <div class="col-12 d-flex justify-content-between align-items-center gap-2 flex-wrap">
                            <label class="form-label mb-0" data-icon="&#128221;">Conceptos de cotizacion <?= $help('Agrega todos los cargos de una misma cotizacion: mano de obra, servicios y varias refacciones.', 'Display + bateria + mano de obra') ?></label>
                            <button class="btn btn-outline-primary btn-sm" type="button" data-add-quote-item data-icon="&#43;">Agregar concepto</button>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" data-icon="&#128230;">Refaccion inventario <?= $help('Opcional. Si eliges una pieza, se cargan descripcion y precio de venta desde inventario.', 'Pantalla OLED · Stock 2 · $850') ?></label>
                            <select class="form-select" name="items[0][refaccion_id]" data-quote-part-select>
                                <option value="">Concepto manual sin inventario</option>
                                <?php foreach ($refaccionesDisponibles ?? [] as $refaccion): ?>
                                    <option
                                        value="<?= e($refaccion['id']) ?>"
                                        data-description="<?= e(trim((string) $refaccion['nombre'] . ' ' . (string) $refaccion['sku'])) ?>"
                                        data-price="<?= e((string) $refaccion['precio_venta']) ?>"
                                        data-stock="<?= e((string) $refaccion['stock_actual']) ?>"
                                    >
                                        <?= e($refaccion['nombre']) ?> · <?= e($refaccion['sku']) ?> · Stock <?= e($refaccion['stock_actual']) ?> · <?= e(formatearMoneda((float) $refaccion['precio_venta'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" data-icon="&#9671;">Tipo <?= $help('Clasifica el concepto para reportes y claridad.', 'mano_obra, refaccion, servicio') ?></label>
                            <select class="form-select" name="items[0][tipo]" data-quote-type><option value="mano_obra">Mano de obra</option><option value="refaccion">Refaccion</option><option value="servicio">Servicio</option><option value="otro">Otro</option></select>
                        </div>
                        <div class="col-md-4"><label class="form-label" data-icon="&#9998;">Descripcion <?= $help('Texto que explica que se va a cobrar. Si eliges inventario, se llena con la refaccion.', 'Cambio de pantalla calidad original') ?></label><input class="form-control" name="items[0][descripcion]" data-quote-description></div>
                        <div class="col-md-2"><label class="form-label" data-icon="&#35;">Cantidad <?= $help('Numero de piezas o servicios iguales.', '1 pantalla, 2 conectores') ?></label><input class="form-control" type="number" step="0.01" name="items[0][cantidad]" value="1"></div>
                        <div class="col-md-2"><label class="form-label" data-icon="&#36;">Precio venta <?= $help('Precio unitario que se cobrara al cliente. En refacciones se toma de inventario y puedes ajustarlo si tu rol lo permite.', '850') ?></label><input class="form-control" type="number" step="0.01" name="items[0][precio_unitario]" value="<?= e($diagnostico['costo_total_sugerido'] ?? 0) ?>" data-money data-quote-price></div>
                        <div class="col-12 vstack gap-2" data-quote-items></div>
                        <template data-quote-item-template>
                            <div class="quote-item-row" data-quote-row>
                                <div class="row g-2 align-items-end">
                                    <div class="col-lg-4">
                                        <label class="form-label" data-icon="&#128230;">Refaccion inventario</label>
                                        <select class="form-select" name="items[__INDEX__][refaccion_id]" data-quote-part-select>
                                            <option value="">Concepto manual sin inventario</option>
                                            <?php foreach ($refaccionesDisponibles ?? [] as $refaccion): ?>
                                                <option value="<?= e($refaccion['id']) ?>" data-description="<?= e(trim((string) $refaccion['nombre'] . ' ' . (string) $refaccion['sku'])) ?>" data-price="<?= e((string) $refaccion['precio_venta']) ?>" data-stock="<?= e((string) $refaccion['stock_actual']) ?>"><?= e($refaccion['nombre']) ?> - <?= e($refaccion['sku']) ?> - Stock <?= e($refaccion['stock_actual']) ?> - <?= e(formatearMoneda((float) $refaccion['precio_venta'])) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label class="form-label" data-icon="&#9671;">Tipo</label>
                                        <select class="form-select" name="items[__INDEX__][tipo]" data-quote-type><option value="mano_obra">Mano de obra</option><option value="refaccion">Refaccion</option><option value="servicio">Servicio</option><option value="otro">Otro</option></select>
                                    </div>
                                    <div class="col-lg-3 col-md-8">
                                        <label class="form-label" data-icon="&#9998;">Descripcion</label>
                                        <input class="form-control" name="items[__INDEX__][descripcion]" data-quote-description>
                                    </div>
                                    <div class="col-lg-1 col-md-3">
                                        <label class="form-label" data-icon="&#35;">Cant.</label>
                                        <input class="form-control" type="number" step="0.01" name="items[__INDEX__][cantidad]" value="1">
                                    </div>
                                    <div class="col-lg-2 col-md-4">
                                        <label class="form-label" data-icon="&#36;">Precio venta</label>
                                        <input class="form-control" type="number" step="0.01" name="items[__INDEX__][precio_unitario]" value="0" data-money data-quote-price>
                                    </div>
                                    <div class="col-12 d-flex justify-content-end">
                                        <button class="btn btn-outline-danger btn-sm" type="button" data-remove-quote-item data-icon="&#10005;">Quitar concepto</button>
                                    </div>
                                </div>
                            </div>
                        </template>
                        <div class="col-md-3"><label class="form-label" data-icon="&#37;">Descuento <?= $help('Descuento en pesos aplicado al subtotal.', '100') ?></label><input class="form-control" type="number" step="0.01" name="descuento" value="0" data-money></div>
                        <div class="col-md-3"><label class="form-label" data-icon="&#37;">IVA <?= $help('Impuesto en pesos si lo aplicas. Si no cobras IVA, dejalo en 0.', '232') ?></label><input class="form-control" type="number" step="0.01" name="iva" value="0" data-money></div>
                        <div class="col-md-3"><label class="form-label" data-icon="&#128197;">Vigencia <?= $help('Fecha hasta la que respetas precio y disponibilidad.', '2026-07-15') ?></label><input class="form-control" type="date" name="vigencia"></div>
                        <div class="col-md-12"><label class="form-label" data-icon="&#9998;">Terminos <?= $help('Condiciones de la cotizacion para el cliente.', 'Precio sujeto a disponibilidad de refaccion') ?></label><textarea class="form-control" name="terminos"></textarea></div>
                    </div>
                    <button class="btn btn-primary mt-3" data-icon="&#128179;">Generar cotizacion</button>
                </form>
            <?php elseif ($puedeCrearCotizacion): ?>
                <div class="alert alert-warning mb-0">
                    Tu rol no tiene permiso para generar cotizaciones.
                </div>
            <?php endif; ?>
        </div>

        <?php $desbloqueo = patronDesbloqueo($orden['password_equipo'] ?? ''); ?>
        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128274;">Desbloqueo del equipo <?= $help('Patron, PIN o clave recibida para probar el equipo. Debe tratarse como dato sensible.', 'Patron 1 > 2 > 5 > 8') ?></h2>
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
            <h2 class="h5" data-icon="&#9889;">Acciones rapidas <?= $help('Controles operativos para mover la orden sin salir de la ficha.', 'Cambiar a en_reparacion o asignar tecnico') ?></h2>
            <form class="mb-3" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/estado')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#9679;">Cambiar estado <?= $help('Actualiza la etapa del proceso. Cada cambio queda en bitacora.', 'recibida -> en_revision -> reparada') ?></label>
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
                <label class="form-label" data-icon="&#128295;">Asignar tecnico <?= $help('Define quien atendera la orden y ayuda a medir carga de trabajo.', 'Tecnico Demo') ?></label>
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
                <h2 class="h5 mb-0" data-icon="&#128197;">Agenda <?= $help('Seguimientos programados para revision, visita, trabajo o entrega.', 'Revision manana 11:00') ?></h2>
                <a class="btn btn-outline-dark btn-sm" href="<?= e(url('/agenda?q=' . urlencode((string) $orden['folio']))) ?>">Ver agenda</a>
            </div>
            <form method="post" action="<?= e(url('/agenda')) ?>" class="mb-3">
                <?= csrf_field() ?>
                <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                <input type="hidden" name="titulo" value="Seguimiento <?= e($orden['folio']) ?>">
                <label class="form-label" data-icon="&#128197;">Programar seguimiento <?= $help('Crea un evento ligado a esta orden para no olvidar acciones.', 'Llamar al cliente el viernes') ?></label>
                <div class="row g-2">
                    <div class="col-7"><input class="form-control" type="datetime-local" name="inicio" value="<?= e(date('Y-m-d\TH:i', strtotime('+1 day'))) ?>" required></div>
                    <div class="col-5">
                        <select class="form-select" name="tipo">
                            <option value="trabajo">Trabajo</option>
                            <option value="entrega">Entrega</option>
                            <option value="recordatorio">Recordatorio</option>
                            <option value="visita">Visita</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <select class="form-select" name="tecnico_id">
                            <option value="">Sin asignar</option>
                            <?php foreach ($tecnicos as $tecnico): ?>
                                <option value="<?= e($tecnico['id']) ?>" <?= (int) $orden['tecnico_id'] === (int) $tecnico['id'] ? 'selected' : '' ?>><?= e($tecnico['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><input class="form-control" name="descripcion" placeholder="Nota del seguimiento"></div>
                    <div class="col-12 d-grid"><button class="btn btn-primary" data-icon="&#128190;">Programar</button></div>
                </div>
            </form>
            <?php if (!empty($agendaEventos)): ?>
                <?php foreach (array_slice($agendaEventos, 0, 4) as $evento): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <strong><?= e($evento['titulo']) ?></strong><br>
                            <small class="text-muted"><?= e(fechaHumana($evento['inicio'])) ?> · <?= e($evento['tecnico_nombre'] ?: 'Sin asignar') ?></small>
                        </div>
                        <span class="badge text-bg-light align-self-start"><?= e($evento['estado']) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="small text-muted mb-0">Sin eventos programados para esta orden.</p>
            <?php endif; ?>
        </div>

        <div class="glass-card mb-3">
            <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                <h2 class="h5 mb-0" data-icon="&#128230;">Refacciones usadas <?= $help('Piezas tomadas del inventario para esta reparacion. Al aplicar una refaccion se descuenta stock.', 'Pantalla OLED x1') ?></h2>
                <span class="badge text-bg-light"><?= e(formatearMoneda($totalRefaccionesUsadas)) ?> <?= $help('Importe total de refacciones activas aplicadas a esta orden.', '$850.00') ?></span>
            </div>

            <?php if ($puedeGestionarInventario && !empty($refaccionesCotizadasPendientes) && !in_array($orden['estado'], ['entregada', 'cancelada'], true)): ?>
                <div class="alert alert-info">
                    <strong>Refacciones cotizadas pendientes:</strong>
                    <?= e(count($refaccionesCotizadasPendientes)) ?> pieza(s) aprobadas en cotizacion.
                    <form class="mt-2" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/refacciones-cotizadas')) ?>" data-confirm="Aplicar refacciones cotizadas y descontar stock">
                        <?= csrf_field() ?>
                        <button class="btn btn-primary btn-sm" data-icon="&#8722;">Aplicar refacciones cotizadas</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if ($puedeGestionarInventario && !in_array($orden['estado'], ['entregada', 'cancelada'], true)): ?>
                <form class="row g-2 mb-3" method="post" action="<?= e(url('/ordenes/' . $orden['id'] . '/refacciones')) ?>">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label class="form-label" data-icon="&#128295;">Refaccion <?= $help('Selecciona la pieza que saldra del inventario.', 'Centro de carga Samsung A12') ?></label>
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
                        <label class="form-label" data-icon="&#35;">Cant. <?= $help('Cantidad que se descuenta del stock.', '1') ?></label>
                        <input class="form-control" type="number" min="1" step="1" name="cantidad" value="1" required>
                    </div>
                    <div class="col-8">
                        <label class="form-label" data-icon="&#36;">Precio venta <?= $help('Precio que se cobrara por esa pieza. Si lo dejas vacio usa el precio de inventario.', '450') ?></label>
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
                                    <?php if (!empty($uso['cotizacion_item_id'])): ?>
                                        <div class="small text-success">Aplicada desde cotizacion</div>
                                    <?php endif; ?>
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
            <h2 class="h5" data-icon="&#128247;">Evidencia y aceptacion <?= $help('Fotos o comprobantes que prueban estado, autorizacion o entrega. Ayudan ante aclaraciones.', 'Foto del ticket firmado') ?></h2>
            <?php if (!empty($orden['firma_recepcion'])): ?>
                <div class="alert alert-success py-2 small mb-3"><?= e($orden['firma_recepcion']) ?></div>
            <?php else: ?>
                <p class="small text-muted">Aun no se registra la aceptacion del cliente. Sube la foto del ticket firmado como evidencia.</p>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data" action="<?= e(url('/ordenes/' . $orden['id'] . '/evidencia')) ?>">
                <?= csrf_field() ?>
                <label class="form-label" data-icon="&#128247;">Foto del ticket firmado <?= $help('Sube una foto tomada con camara o archivo. Queda ligada a la orden.', 'Ticket con firma del cliente') ?></label>
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
            <h2 class="h5" data-icon="&#128179;">Registrar pago <?= $help('Agrega anticipo, pago parcial o liquidacion. Los pagos no se borran; se cancelan con motivo.', '$500 efectivo') ?></h2>
            <form method="post" action="<?= e(url('/pagos')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="orden_id" value="<?= e($orden['id']) ?>">
                <label class="form-label" data-icon="&#36;">Monto <?= $help('Cantidad que entra a caja. No debe superar el saldo pendiente.', '300') ?></label>
                <input class="form-control mb-2" type="number" step="0.01" min="0.01" max="<?= e((string) max(0, (float) $orden['saldo_pendiente'])) ?>" name="monto" value="<?= e((string) max(0, (float) $orden['saldo_pendiente'])) ?>" required data-money>
                <div class="form-text mb-2">No debe superar el saldo pendiente.</div>
                <label class="form-label" data-icon="&#9679;">Metodo <?= $help('Forma en que el cliente pago.', 'efectivo, transferencia, tarjeta') ?></label>
                <select class="form-select mb-2" name="metodo">
                    <?php foreach (['efectivo','transferencia','tarjeta','otro'] as $metodo): ?><option value="<?= e($metodo) ?>"><?= e($metodo) ?></option><?php endforeach; ?>
                </select>
                <label class="form-label" data-icon="&#35;">Referencia <?= $help('Dato opcional para rastrear el pago.', 'folio transferencia, ultimos 4 tarjeta') ?></label>
                <input class="form-control mb-2" name="referencia">
                <button class="btn btn-primary w-100" data-icon="&#128190;">Guardar pago</button>
            </form>
        </div>

        <div class="glass-card mb-3">
            <h2 class="h5" data-icon="&#128179;">Pagos <?= $help('Historial de cobros activos y cancelados de esta orden.', 'Anticipo + liquidacion') ?></h2>
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
            <h2 class="h5" data-icon="&#128220;">Bitacora de la orden <?= $help('Registro automatico de cambios importantes. Sirve para saber quien hizo que y cuando.', 'Cambio de estado por Tecnico Demo') ?></h2>
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
