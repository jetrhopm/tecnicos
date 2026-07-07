
# Manual de Usuario
# Sistema Web de Gestion de Servicios Tecnicos y Reparaciones

Version del manual: 1.0
Fecha: 2026-06-14

Este manual explica los roles del sistema, las tareas que realiza cada usuario y el flujo completo para recibir un equipo, levantar una orden, diagnosticar, cotizar, cobrar, entregar y dar seguimiento al cliente.

## 1. Objetivo del sistema

El sistema permite administrar servicios tecnicos y reparaciones desde la recepcion del equipo hasta la entrega final. Centraliza clientes, equipos, ordenes, diagnosticos, cotizaciones, pagos, garantias, inventario, reportes y comunicacion con el cliente.

El flujo principal recomendado es:

- Recibir al cliente.
- Registrar o seleccionar cliente.
- Registrar o seleccionar equipo.
- Levantar orden de servicio.
- Imprimir comprobante con codigo de barras y link/QR de consulta.
- Revisar y diagnosticar.
- Generar cotizacion.
- Autorizar o rechazar cotizacion.
- Reparar y registrar avances.
- Cobrar anticipo, parcialidades o liquidacion.
- Entregar equipo usando clave/codigo de entrega.
- Iniciar garantia si aplica.
- Conservar historial y auditoria.

## 2. Conceptos basicos

Cliente: Persona o empresa que deja un equipo para diagnostico o reparacion.

Equipo: Aparato asociado al cliente. Puede ser celular, laptop, PC, consola, impresora, electrodomestico, herramienta, moto u otro.

Orden de servicio: Registro central de la reparacion. Contiene folio, cliente, equipo, falla, diagnostico, estado, costos, pagos, tecnico y entrega.

Folio: Identificador publico de la orden. Sirve para buscar y consultar.

Clave de entrega / codigo de barras: Llave interna para localizar y liberar el equipo al momento de entrega.

Token publico: Llave segura para que el cliente consulte su orden desde el portal publico sin ver informacion interna.

Bitacora / auditoria: Registro de cambios importantes, usuarios responsables, fecha, IP y datos modificados.

## 3. Roles del sistema

### Superadmin

Es el usuario con control total del sistema.

Puede:
- Administrar usuarios, roles y permisos.
- Ver, crear, editar, eliminar y administrar todos los modulos.
- Cambiar configuraciones generales del negocio.
- Revisar reportes, auditoria y movimientos criticos.
- Autorizar excepciones especiales.

Procesos principales:
- Configurar el sistema antes de iniciar operaciones.
- Crear usuarios para recepcion, tecnicos, caja y almacen.
- Revisar permisos si un usuario no puede completar una tarea.
- Supervisar informacion sensible y auditoria.
- Preparar el sistema para produccion.

### Admin

Es responsable de la operacion diaria del taller.

Puede:
- Ver y gestionar clientes, equipos y ordenes.
- Asignar tecnicos.
- Revisar diagnosticos, cotizaciones, pagos y entregas.
- Consultar reportes operativos.
- Apoyar a recepcion, caja o tecnicos si hay dudas.

Procesos principales:
- Monitorear el dashboard.
- Revisar ordenes abiertas, urgentes y pendientes.
- Validar que las ordenes tengan tecnico asignado.
- Dar seguimiento a equipos listos para entrega.
- Revisar saldos pendientes y garantias activas.

### Recepcion

Es el rol encargado de recibir clientes y equipos.

Puede:
- Buscar o crear clientes.
- Buscar o registrar equipos.
- Levantar ordenes de servicio.
- Imprimir comprobantes de recepcion.
- Enviar o copiar link de consulta al cliente.
- Consultar el estado de una orden.
- Entregar equipos usando la clave de entrega, si el sistema lo permite.

Procesos principales:
- Usar Nueva orden para capturar cliente, equipo y falla en una sola pantalla.
- Confirmar accesorios recibidos, estado fisico y observaciones.
- Registrar anticipo si el cliente paga algo al dejar el equipo.
- Imprimir comprobante con folio y codigo de barras.
- Explicar al cliente como consultar el avance.

### Tecnico

Es el rol encargado de revisar y reparar equipos asignados.

Puede:
- Ver ordenes asignadas.
- Consultar datos tecnicos del equipo.
- Registrar diagnostico tecnico.
- Registrar pruebas realizadas.
- Agregar observaciones internas y visibles para cliente.
- Registrar avance de reparacion.
- Marcar resultados como reparado, no reparable o pendiente por refaccion, segun permisos.

Procesos principales:
- Abrir la orden asignada.
- Leer falla reportada, accesorios, estado fisico y clave/patron si fue capturado.
- Registrar diagnostico claro.
- Indicar piezas necesarias y costo sugerido.
- Actualizar estado cuando avance el trabajo.
- No entregar equipo directamente sin seguir el proceso de entrega.

### Tecnico senior

Es un tecnico con permisos ampliados para casos complejos.

Puede:
- Revisar ordenes criticas.
- Corregir o complementar diagnosticos.
- Autorizar cambios tecnicos especiales.
- Apoyar a otros tecnicos.
- Validar reparaciones antes de entrega.

Procesos principales:
- Revisar equipos complicados o garantias.
- Confirmar diagnosticos antes de cotizar.
- Validar piezas y tiempos estimados.
- Definir si un equipo es no reparable.

### Almacen

Es responsable de refacciones e inventario.

Puede:
- Consultar refacciones.
- Registrar entradas y salidas de inventario cuando el modulo este habilitado.
- Vigilar stock bajo.
- Asociar refacciones a reparaciones.
- Reportar refacciones mas usadas.

Procesos principales:
- Revisar alertas de stock bajo.
- Registrar movimientos de inventario con motivo.
- Evitar stock negativo salvo permiso especial.
- Confirmar disponibilidad antes de que una orden pase a reparacion.

### Caja

Es responsable de cobros, recibos y cortes.

Puede:
- Registrar anticipos, pagos parciales y liquidaciones.
- Ver saldo pendiente.
- Imprimir ticket o recibo de pago.
- Consultar corte de caja.
- Entregar equipos usando la clave/codigo de entrega, segun la configuracion actual.

Procesos principales:
- Buscar orden por folio o clave de entrega.
- Confirmar total, pagos previos y saldo.
- Registrar pago final si hay saldo pendiente.
- Marcar entrega cuando el cliente presenta la clave correcta.
- Generar comprobante de entrega.

### Cliente consulta

Es un rol pensado para consulta limitada o acceso publico controlado.

Puede:
- Consultar el avance de una orden con folio y token.
- Ver estado actual, equipo, diagnostico visible, cotizacion visible y saldo pendiente.
- Aceptar o rechazar cotizacion si el portal lo permite.

No puede:
- Ver notas internas.
- Ver costos internos o utilidad.
- Ver datos de otros clientes.
- Ver usuarios internos.
- Cambiar estados administrativos.

## 4. Proceso recomendado para levantar una orden

La forma mas rapida es entrar a Ordenes > Nueva orden.

### Paso 1: Cliente

- Busca al cliente por nombre, telefono o email.
- Si aparece, seleccionalo.
- Si no aparece, llena los datos del cliente nuevo.
- Minimo recomendado: nombre completo y telefono.
- WhatsApp se puede usar igual que telefono si el cliente lo autoriza.

Consejo: Antes de crear un cliente nuevo, busca por telefono para evitar duplicados.

### Paso 2: Equipo

- Busca un equipo existente por marca, modelo, serie o IMEI.
- Si el cliente ya habia dejado ese equipo, seleccionalo.
- Si es un equipo nuevo, captura:
  - Tipo de equipo.
  - Marca y modelo.
  - Serie o IMEI si aplica.
  - Color.
  - Contrasena o patron si el cliente lo proporciona.
  - Accesorios recibidos.
  - Estado fisico al recibir.
  - Observaciones.

Consejo: El estado fisico y accesorios ayudan a evitar reclamos al entregar.

### Paso 3: Orden de servicio

Captura:
- Tipo de servicio: revision, reparacion, mantenimiento, garantia, etc.
- Prioridad: baja, normal, alta o urgente.
- Fecha estimada de entrega.
- Costo estimado si aplica.
- Anticipo y metodo de pago si el cliente paga al recibir.
- Falla reportada por el cliente.
- Diagnostico inicial si se detecta algo al recibir.
- Observaciones internas.
- Observaciones visibles para cliente.

Al guardar, el sistema:
- Crea el cliente si no existia.
- Crea el equipo si no existia.
- Crea la orden.
- Genera folio unico.
- Genera token publico.
- Genera clave/codigo de entrega.
- Registra anticipo si aplica.
- Guarda auditoria.

## 5. Estados de una orden

Recibida: La orden fue creada y el equipo ya ingreso al taller.

En revision: El tecnico esta revisando el equipo.

Diagnosticada: Ya existe diagnostico tecnico.

Esperando autorizacion: La cotizacion fue enviada o esta pendiente de aceptacion.

Autorizada: El cliente acepto la reparacion.

Rechazada: El cliente no autorizo la cotizacion.

En reparacion: El tecnico esta trabajando el equipo.

Esperando refaccion: Falta pieza o material.

Reparada: El trabajo tecnico termino.

No reparable: No se puede reparar o no conviene reparar.

Lista para entrega: El equipo puede entregarse al cliente.

Entregada: El equipo fue liberado y entregado.

Cancelada: La orden fue cancelada.

Garantia: La orden esta en proceso relacionado con garantia.

## 6. Diagnostico

El diagnostico lo registra tecnico o tecnico senior.

Debe incluir:
- Diagnostico tecnico interno.
- Diagnostico visible para cliente.
- Causa probable.
- Pruebas realizadas.
- Piezas necesarias.
- Mano de obra estimada.
- Refacciones estimadas.
- Costo total sugerido.

Buenas practicas:
- No escribir informacion sensible en diagnostico visible.
- Separar notas internas de comentarios para cliente.
- Tomar evidencias cuando aplique.
- No modificar diagnosticos autorizados sin permiso especial.

## 7. Cotizacion

La cotizacion se genera a partir del diagnostico o manualmente en la orden.

Incluye:
- Conceptos de mano de obra, refaccion, servicio u otro.
- Cantidad.
- Precio unitario.
- Subtotal.
- Descuento si aplica.
- IVA si esta configurado.
- Total.
- Vigencia y terminos.

Estados:
- Pendiente.
- Aceptada.
- Rechazada.
- Vencida.

Reglas:
- Una cotizacion aceptada no debe modificarse; se crea una nueva version.
- Si el cliente autoriza por llamada o WhatsApp, registrar autorizacion manual.
- Mantener historial para evitar dudas.

## 8. Reparacion

Cuando la cotizacion esta autorizada, el equipo puede pasar a reparacion.

El tecnico debe registrar:
- Trabajo realizado.
- Piezas instaladas.
- Pruebas finales.
- Observaciones internas.
- Observaciones para cliente.
- Resultado.

Resultados posibles:
- Reparado.
- No reparable.
- Requiere mas revision.
- Esperando refaccion.

Buenas practicas:
- No mover a reparacion sin autorizacion salvo permiso especial.
- Registrar pruebas finales antes de entregar.
- Si se usa refaccion, generar movimiento de salida de inventario cuando el modulo este activo.

## 9. Pagos y caja

El sistema permite:
- Anticipo.
- Pago parcial.
- Liquidacion.
- Corte de caja.
- Reporte por fechas.
- Reporte por usuario.

Al registrar pago:
- Selecciona orden.
- Captura monto.
- Elige metodo: efectivo, transferencia, tarjeta u otro.
- Captura referencia si aplica.
- Guarda notas.

Reglas:
- Un pago no debe borrarse; se cancela con motivo y permiso.
- El saldo pendiente se recalcula.
- Antes de entregar se debe revisar si queda saldo.

## 10. Entrega de equipo

La entrega se hace desde el modulo Entregas.

Proceso recomendado:
- El cliente presenta su comprobante o clave/codigo de entrega.
- El usuario busca o escanea la clave.
- El sistema muestra orden, cliente, equipo, saldo y estado.
- Si hay saldo pendiente, caja registra el pago final.
- Se confirma quien recibe el equipo.
- Se registra quien entrego el equipo.
- Se marca la orden como entregada.
- Se genera comprobante de entrega.
- Se inicia garantia si aplica.

Regla importante:
- La clave/codigo de entrega sirve como llave para liberar el equipo. Esto reduce entregas equivocadas y ayuda a encontrar equipos rapidamente.

## 11. Codigo de barras y QR

Codigo de barras interno:
- Se usa en recepcion, tecnico y caja.
- Contiene una clave o folio, no necesariamente un link.
- Puede leerse con lector USB o escribirse manualmente.
- Ayuda a localizar el equipo fisico.

QR publico del cliente:
- Lleva al portal publico de consulta.
- Permite al cliente revisar avance sin entrar al panel.
- Debe usar folio y token seguro.

## 12. Portal publico del cliente

El cliente puede consultar con folio y token.

Puede ver:
- Folio.
- Equipo.
- Estado actual.
- Fecha de recepcion.
- Fecha estimada.
- Diagnostico visible.
- Cotizacion visible.
- Saldo pendiente.
- Comentarios visibles.

No puede ver:
- Notas internas.
- Costos internos.
- Datos de otros clientes.
- Usuarios internos.
- Bitacora privada.

## 13. Inventario y refacciones

El modulo de inventario ayuda a controlar piezas y materiales.

Debe usarse para:
- Alta de refaccion.
- Entrada de inventario.
- Salida por reparacion.
- Ajuste manual con motivo.
- Alertas de stock bajo.
- Historial de movimientos.

Buenas practicas:
- No permitir stock negativo salvo permiso especial.
- Asociar refacciones a ordenes.
- Revisar utilidad por refaccion.
- Consultar refacciones mas usadas.

## 14. Garantias

La garantia puede generarse al entregar una orden.

Contiene:
- Orden original.
- Fecha de inicio.
- Fecha de fin.
- Condiciones.
- Estado.
- Motivo.
- Resolucion.

Proceso:
- Cliente regresa por garantia.
- Se busca la orden original.
- Se revisa si la garantia esta vigente.
- Se registra ingreso por garantia.
- Se asocia nueva revision a la orden original.
- Se evita duplicar cobros si aplica.

## 15. Dashboard

El dashboard permite revisar el estado general del taller.

Muestra:
- Ordenes abiertas.
- Ordenes urgentes.
- Ordenes esperando autorizacion.
- Ordenes en reparacion.
- Ordenes listas para entrega.
- Pagos del dia.
- Saldo pendiente total.
- Stock bajo.
- Garantias activas.
- Carga por tecnico.

Uso recomendado:
- Revisarlo al inicio del dia.
- Revisarlo antes del cierre.
- Detectar cuellos de botella.

## 16. Reportes

Los reportes ayudan a controlar operacion y dinero.

Reportes utiles:
- Ordenes por estado.
- Ordenes por tecnico.
- Ordenes por fecha.
- Reparaciones terminadas.
- Reparaciones pendientes.
- Ingresos por periodo.
- Saldos pendientes.
- Refacciones mas usadas.
- Clientes frecuentes.
- Tiempo promedio de reparacion.
- Garantias activas.
- Corte de caja.

## 17. Configuracion

En configuracion se administran datos del negocio y reglas generales.

Puede incluir:
- Nombre del negocio.
- Logo.
- Telefono.
- WhatsApp.
- Email.
- Direccion.
- Moneda.
- IVA activo/inactivo.
- Porcentaje de IVA.
- Garantia por defecto.
- Plantillas de mensajes.
- Terminos de servicio.
- Politica de garantia.
- Formato de folios.
- Tema visual.

## 18. Comunicacion con cliente

El sistema genera mensajes de WhatsApp prellenados.

Ejemplos:
- Orden recibida.
- Equipo en revision.
- Diagnostico listo.
- Cotizacion pendiente.
- Reparacion en proceso.
- Equipo listo para entrega.
- Pago pendiente.
- Garantia.

El sistema no depende obligatoriamente de una API pagada de WhatsApp. Abre enlaces tipo wa.me con texto prellenado.

## 19. Seguridad operativa

Recomendaciones:
- Cada usuario debe usar su propia cuenta.
- No compartir contrasenas.
- Cambiar la contrasena inicial.
- Revisar permisos por rol.
- No entregar equipos sin clave de entrega.
- No mostrar notas internas al cliente.
- No subir archivos peligrosos.
- Usar HTTPS en produccion.
- Mantener respaldos de base de datos.

## 20. Flujo completo resumido

1. Recepcion recibe al cliente.
2. Recepcion usa Nueva orden.
3. Selecciona o crea cliente.
4. Selecciona o crea equipo.
5. Captura falla y datos de recepcion.
6. Guarda la orden.
7. Imprime comprobante.
8. Cliente recibe folio/link/QR de consulta.
9. Tecnico revisa y diagnostica.
10. Se genera cotizacion.
11. Cliente acepta o rechaza.
12. Si acepta, tecnico repara.
13. Caja registra pagos.
14. Orden pasa a lista para entrega.
15. Cliente presenta clave/codigo.
16. Entregas busca o escanea la clave.
17. Se confirma saldo y receptor.
18. Se entrega equipo.
19. Se genera comprobante de entrega.
20. Se inicia garantia si aplica.

## 21. Recomendacion para capacitacion

Para capacitar al personal, practicar estos casos:

- Crear orden con cliente nuevo y equipo nuevo.
- Crear orden con cliente existente y equipo existente.
- Registrar diagnostico.
- Crear cotizacion.
- Autorizar cotizacion manualmente.
- Registrar anticipo y pago final.
- Entregar equipo usando clave de entrega.
- Consultar orden desde portal publico.
- Revisar dashboard y reportes.

## 22. Credenciales iniciales locales

Usuario administrador local:
- Email: admin@local.test
- Contrasena: password
- Rol: superadmin

Importante: cambiar esta contrasena antes de usar el sistema en produccion.
