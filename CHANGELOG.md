# Registro de cambios

Historial de cambios del sistema. Las fechas usan formato AAAA-MM-DD.
Este repositorio se versiona en **git local**; el push a GitHub lo realiza el
responsable del proyecto.

---

## 2026-07-08

### Cotizaciones conectadas con inventario

- La cotizacion ahora puede seleccionar refacciones existentes del inventario.
- Una cotizacion puede contener varios conceptos, por ejemplo mano de obra,
  modulo de carga, bateria y display en la misma version.
- Al seleccionar una refaccion se precargan descripcion y precio de venta, y se
  guarda una referencia al item de inventario con costo snapshot.
- Las refacciones de una cotizacion aceptada pueden aplicarse desde la ficha de
  la orden para descontar stock y registrar el movimiento de salida.
- Se evita aplicar dos veces la misma refaccion cotizada mediante relacion con
  el item de cotizacion.
- El diagnostico tecnico deja de pedir montos de mano de obra/refacciones en la
  vista; esos importes se capturan en Cotizacion para mantener separado lo
  tecnico de lo financiero.
- Se agrego `database/upgrade_cotizaciones_inventario.php` para instalaciones
  existentes.

## 2026-07-07

### Ayudas contextuales en ficha de orden

- Se agregaron botones pequenos `?` con ventanas informativas en la consulta
  interna de orden.
- Las ayudas explican campos clave, formularios y secciones como diagnostico,
  cotizacion, estados, agenda, refacciones, evidencia, pagos y bitacora.
- Los popovers usan Bootstrap y no envian formularios ni modifican datos.

### Permisos de cotizacion para tecnico

- El rol `tecnico` ahora puede ver y crear cotizaciones desde la orden.
- La autorizacion manual de cotizaciones sigue reservada a roles con permiso
  `cotizaciones.autorizar`.
- La ficha de orden oculta acciones de cotizacion que el rol no puede ejecutar.
- Se agrego `database/upgrade_tecnico_cotizaciones.php` para instalaciones
  existentes.

### Etiqueta de equipo con codigo de barras

- Se agrego impresion de etiqueta de equipo desde la ficha de orden.
- La etiqueta imprime Code 39 con la clave de entrega para usar lector USB o
  camara del modulo de entregas, sin incluir datos sensibles.
- La nota de recepcion/ticket ahora muestra el codigo de barras de entrega
  ademas de la clave en texto.

### Reportes exportables y checklist de produccion

- El modulo `/reportes` agrega exportaciones CSV para corte de caja, saldos
  pendientes, refacciones mas usadas y utilidad estimada.
- El corte de caja se resume por fecha, usuario y metodo de pago.
- Los reportes en pantalla muestran saldos pendientes, refacciones usadas y
  utilidad estimada por orden.
- Configuracion ahora muestra un checklist de produccion/seguridad con alertas
  de `APP_DEBUG`, `APP_ENV`, dominio/HTTPS, usuarios demo activos, permisos de
  `storage`, `.htaccess` y respaldos recientes.

### Administracion completa de usuarios

- El modulo `/usuarios` ahora permite editar usuarios existentes, actualizar
  telefono, correo, estatus y reasignar roles.
- Se agrego restablecimiento de contrasena desde el formulario de edicion sin
  exponer la contrasena anterior.
- La lista de usuarios muestra telefono, ultimo acceso y accion rapida para
  activar, desactivar o bloquear cuentas.
- Se protege al sistema para no dejarlo sin un `superadmin` activo ni permitir
  que el usuario actual se quite su propio acceso critico.
- Los cambios de perfil, roles, estatus y contrasena se registran en auditoria.

### Agenda minima operativa

- Se agrego el modulo `/agenda` con vista diaria/semanal y filtros por tecnico,
  estado, tipo y busqueda.
- Se pueden programar eventos ligados a orden por folio, clave o id.
- La ficha de orden permite programar seguimientos y muestra sus ultimos eventos.
- El dashboard muestra la agenda programada del dia.
- Los eventos cambian entre `programado`, `realizado` y `cancelado` con
  auditoria.
- Se agrego `database/upgrade_agenda_roles.php` para permisos de agenda en roles
  operativos.

### Inventario conectado al flujo de reparacion

- La ficha de orden permite aplicar refacciones del inventario a una reparacion.
- Al aplicar una refaccion se descuenta stock, se registra uso en
  `refacciones_ordenes`, se crea movimiento `salida` ligado a la orden y queda
  auditoria.
- Se impide dejar stock negativo y se bloquea aplicar refacciones en ordenes
  entregadas o canceladas.
- Se agrego cancelacion de refacciones aplicadas con motivo, devolucion de stock
  y movimiento `cancelacion`.
- Se actualizo el esquema y se agrego
  `database/upgrade_refacciones_ordenes_estado.php` para instalaciones
  existentes.

### Bloqueo estricto de cotizaciones

- Se impide crear una nueva cotizacion si la ultima version de la orden sigue
  pendiente.
- Una cotizacion solo puede aceptarse o rechazarse cuando esta en estado
  `pendiente`; las aceptadas, rechazadas o vencidas quedan cerradas.
- Las cotizaciones vencidas se marcan como `vencida` y obligan a generar una
  nueva version.
- La autorizacion/rechazo de cotizacion ahora corre en transaccion y usa
  actualizacion condicional para evitar dobles clics o peticiones repetidas.
- Formularios y API validan cantidad, precio, descuento e IVA antes de crear
  cotizaciones.
- La ficha de orden permite crear nueva version solo cuando la cotizacion previa
  ya esta cerrada.

### Garantia configurable para campo real

- Se agrego `garantia.dias_default` para definir desde Configuracion cuantos
  dias dura la garantia creada al entregar un equipo.
- La entrega ya no usa 30 dias fijos; toma la vigencia configurada y las
  condiciones desde la orden o desde la politica legal del taller.
- El PDF de orden conserva las dos copias en la primera hoja y agrega una
  segunda pagina con la garantia completa para evitar texto recortado.
- Se agrego `database/upgrade_garantia_config.php` para instalaciones
  existentes.

### Mejora de pagos para campo real

- Se documento la hoja de ruta `docs/MEJORAS_CAMPO_REAL.md`.
- Los pagos ahora validan monto mayor a cero y bloquean sobrepagos sin permiso
  especial.
- Se agrego cancelacion de pagos con motivo, responsable, auditoria y recalculo
  de saldo de la orden.
- La ficha de orden muestra pagos cancelados y permite cancelar pagos activos
  segun permisos.

### PDF de orden para cliente

- Se actualizo el PDF de recepcion/consulta para que use el diseno nuevo de
  ficha: cabecera del negocio, recuadros de datos, patron o clave de
  desbloqueo, condiciones, resumen de cobro, codigo de barras y firma.
- El PDF publico por folio/token ahora carga tambien datos visibles del cliente
  y equipo necesarios para el comprobante, sin exponer notas internas.
- Se separo el codigo de barras de entrega de las ligas de consulta/PDF para
  dejar una zona limpia de lectura.
- Se corrigio la codificacion del PDF para conservar acentos, tildes y la letra
  enie en textos del comprobante.
- Se actualizo el texto legal de garantia y se agrego
  `database/upgrade_garantia_texto.php` para aplicarlo en instalaciones
  existentes.
- El PDF de orden ahora imprime dos tantos en una sola hoja carta: copia para
  cliente y copia para taller.
- Configuracion permite editar el nombre del sistema y subir el logo del
  taller; se agrego `database/upgrade_branding_config.php`.
- En el PDF se corrigio la posicion del folio y los enlaces de consulta/PDF
  ahora son anotaciones clicables con la URL completa.

Ronda de trabajo enfocada en seguridad, generacion de URLs, un tema visual
nuevo, el patron/PIN de desbloqueo y los documentos imprimibles (orden y
entrega) en tamano carta y ticket termico 80/58 mm, mas evidencia de
aceptacion del cliente y bitacora por orden.

### Pasos posteriores a actualizar (instalaciones existentes)

Ejecutar una sola vez, en este orden:

```bash
php database/upgrade_delivery_codes.php     # claves de entrega aleatorias
php database/upgrade_ticket_config.php       # config de logo y garantia del ticket
php database/upgrade_garantia_texto.php      # texto legal actualizado de garantia
php database/upgrade_branding_config.php     # nombre del sistema y logo del taller
php database/upgrade_garantia_config.php     # dias configurables de garantia
php database/upgrade_whatsapp_templates.php  # mensajes de WhatsApp por contexto
php database/upgrade_notificaciones.php      # tabla de notificaciones in-app
php database/upgrade_inventario_roles.php    # acceso al almacen para tecnicos y recepcion
php database/upgrade_refacciones_ordenes_estado.php # cancelacion de refacciones aplicadas
php database/upgrade_agenda_roles.php        # permisos del modulo agenda
```

Nuevas variables de entorno (opcional) en `.env`:

```ini
# Solo true si la app corre detras de un proxy/balanceador que manda X-Forwarded-*
APP_TRUST_PROXY=false
# Cierre de sesion por inactividad (minutos) y duracion de "No cerrar sesion" (dias)
SESSION_IDLE_MINUTES=120
SESSION_REMEMBER_DAYS=30
```

Nuevas claves de configuracion (editables en el panel de Configuracion):

- `negocio.logo_url` — URL o ruta del logo para tickets y documentos.
- `ticket.garantia` — texto de garantia que sale en los tickets termicos.

---

### 1. Endurecimiento de seguridad

**Por que:** cerrar vias de abuso y fugas de informacion antes de exponer el
sistema fuera de la red local.

- **Manejador global de excepciones** ([app/bootstrap.php](app/bootstrap.php)):
  registra el detalle en `storage/logs/app.log` y responde sin trazas ni SQL
  al navegador, salvo con `APP_DEBUG=true`. Los errores de negocio
  (`RuntimeException`) responden 422 con mensaje util; el resto, 500 generico.
- **CSRF tambien en la API** ([app/Core/Middleware.php](app/Core/Middleware.php)):
  POST/PUT/PATCH/DELETE a `/api/*` exigen token (`_csrf` o header
  `X-CSRF-TOKEN`, disponible en el `<meta name="csrf-token">` del layout).
- **Fix de bypass CSRF con token vacio** ([app/Core/Csrf.php](app/Core/Csrf.php)):
  antes `hash_equals('','')` devolvia `true` sin token en sesion.
- **Freno de fuerza bruta en login**
  ([app/Services/AuthService.php](app/Services/AuthService.php)): 5 intentos
  fallidos por email o IP en 15 minutos bloquean temporalmente, apoyado en la
  tabla `auditoria`.
- **Contrasena minima de 8 caracteres** al crear usuarios
  ([app/Services/UserService.php](app/Services/UserService.php)).
- **Cabeceras de seguridad** (nosniff, X-Frame-Options, Referrer-Policy,
  Permissions-Policy, HSTS en HTTPS) y supresion de `X-Powered-By`.
- **`session.use_strict_mode`** contra session fixation
  ([app/Core/Session.php](app/Core/Session.php)).
- **`_method` restringido** ([app/Core/Request.php](app/Core/Request.php)):
  solo permite override POST -> PUT/PATCH/DELETE; no puede degradar a GET para
  esquivar la validacion CSRF.
- **Open redirect en `Response::back()`**
  ([app/Core/Response.php](app/Core/Response.php)): solo regresa a paginas del
  propio host.
- **Cabeceras X-Forwarded-* solo con `APP_TRUST_PROXY=true`**
  ([app/Helpers/security.php](app/Helpers/security.php)).
- **Validacion minima de cliente en el servicio**
  ([app/Services/ClienteService.php](app/Services/ClienteService.php)): la API
  no pasaba por el validador y permitia clientes vacios.

### 2. Clave de entrega aleatoria y no derivable del folio

**Por que:** antes la clave era `ENT-<folio>` (folio visible y secuencial), asi
que cualquiera podia liberar un equipo sin la nota fisica del cliente.

- Clave aleatoria `ENT-XXXXXXXX` (8 caracteres, alfabeto sin 0/O ni 1/I,
  compatible con Code 39) ([app/Services/FolioService.php](app/Services/FolioService.php)).
- El folio deja de aceptarse como clave en `/entregas`
  ([app/Repositories/OrdenRepository.php](app/Repositories/OrdenRepository.php),
  [app/Services/EntregaService.php](app/Services/EntregaService.php)).
- La nota de recepcion ya no imprime el folio como clave si falta la real.
- Migracion [database/upgrade_delivery_codes.php](database/upgrade_delivery_codes.php)
  regenera claves de ordenes activas (las entregadas/canceladas conservan la
  suya). Clave demo nueva: `ENT-DEMO2468`.

### 3. URLs de assets y navegacion relativas a la raiz

**Por que:** las URLs absolutas con `http://localhost` fijo rompian los `.js`/`.css`
al entrar por HTTPS (contenido mixto) o desde otra IP/dominio.

- `url()` y `asset()` devuelven rutas relativas a la raiz (`/tecnico/...`), que
  heredan esquema y host de la pagina
  ([app/Helpers/security.php](app/Helpers/security.php)).
- Nuevo `absolute_url()` para enlaces que salen del navegador (WhatsApp,
  portal); el enlace del portal en el mensaje de WhatsApp lo usa
  ([app/Services/MensajeService.php](app/Services/MensajeService.php)).

### 4. Tema "Blueprint neon" y selector de patron / PIN

**Por que:** nuevo estilo visual solicitado y captura del patron de desbloqueo
del equipo al registrar la orden.

- **5o tema** ([public/assets/css/themes/blueprint.css](public/assets/css/themes/blueprint.css)):
  fondo navy con rejilla, bordes cian con glow, boton principal verde, dinero
  en naranja y pastillas de estado con color. Se elige en el switcher de Tema;
  no altera los otros 4 temas.
- **Selector de patron / clave** en el registro de orden
  ([public/assets/js/pattern-lock.js](public/assets/js/pattern-lock.js), estilos
  en [app/assets/css/app.css](public/assets/css/app.css)): rejilla 3x3 que se
  une por arrastre, clic o teclado y serializa como `Patron: 1-4-7-8-9`; pestana
  alterna a clave/PIN con teclado en pantalla. Reutiliza el campo existente
  `password_equipo`, sin cambios en la base.

### 5. Documento de orden imprimible: carta y ticket termico 80/58 mm

**Por que:** imprimir el comprobante de recepcion tanto en hoja carta como en
impresora termica, con el patron dibujado y logo del negocio.

- **Estilos de impresion** ([public/assets/css/print.css](public/assets/css/print.css))
  con tres formatos por clase (`.doc-carta`, `.doc-80`, `.doc-58`); el layout
  fija `@page` segun formato
  ([resources/views/layouts/print.php](resources/views/layouts/print.php)).
- **Vista del documento**
  ([resources/views/print/recepcion.php](resources/views/print/recepcion.php)):
  carta con campos en recuadros, garantia, totales, saldo en caja y firmas;
  termico en una columna con checklist de retiro.
- **Patron dibujado** ([app/Helpers/print.php](app/Helpers/print.php)):
  `patronDesbloqueo()` interpreta `password_equipo` y `patronSvg()` dibuja la
  mini rejilla 3x3 con su secuencia.
- `OrdenRepository::find()` ahora trae IMEI, patron/clave, estado fisico,
  accesorios y color del equipo.
- `OrdenController::imprimir()` acepta `?formato=carta|80|58` y pasa los datos
  del negocio ([app/Controllers/OrdenController.php](app/Controllers/OrdenController.php)).
- **Logo y datos del negocio configurables**: nuevas claves `negocio.logo_url`
  y `ticket.garantia` (seed + migracion
  [database/upgrade_ticket_config.php](database/upgrade_ticket_config.php)).
- Se conservan los endpoints PDF de orden y portal, generados al vuelo
  (`OrdenPdfService`, rutas `/pdf`).

### 6. Evidencia de aceptacion del cliente + bitacora de la orden

**Por que:** el PDF no se guarda (se genera al momento por memoria), pero debe
existir evidencia de que el cliente acepto presupuesto/terminos y un historico
de la orden.

- **Subir foto del ticket firmado** como evidencia: se guarda en
  `storage/uploads/ordenes/{id}` (fuera del webroot) y se registra en la tabla
  `archivos`. Solo imagenes (JPG/PNG/WEBP), con limite `UPLOAD_MAX_MB` y
  validacion de MIME real
  ([app/Services/EvidenciaService.php](app/Services/EvidenciaService.php),
  [app/Repositories/ArchivoRepository.php](app/Repositories/ArchivoRepository.php)).
- **Aceptacion de presupuesto y terminos**: se sella en
  `ordenes_servicio.firma_recepcion` (fecha y usuario) y queda en la bitacora.
- **Bitacora de la orden** ([app/Services/AuditoriaService.php](app/Services/AuditoriaService.php)
  `historial()`): tarjeta en la ficha con el historico (creada, cambios de
  estado, tecnico, evidencia, aceptacion, PDF generado, entrega).
- La evidencia se sirve por ruta autenticada
  `/ordenes/{id}/evidencia/{archivo}` validando que pertenezca a la orden.
- `OrdenController::pdf()` deja constancia de "PDF generado" sin guardar el
  archivo.

### 7. Comprobante de entrega en carta y ticket termico 80/58 mm

**Por que:** dar al comprobante de entrega los mismos formatos y estilos que la
orden.

- Vista reconstruida
  ([resources/views/print/entrega.php](resources/views/print/entrega.php)) con
  el sistema de documentos: carta con recuadros, totales (saldo antes, pago
  final, saldo pendiente) y firmas; termico con checklist de conformidad.
- `EntregaController::comprobante()` acepta `?formato=` y pasa datos del negocio.
- `EntregaService::ultimaPorOrden()` permite reimprimir el comprobante desde la
  ficha de la orden; la ficha muestra un menu "Comprobante" (carta/80/58) cuando
  la orden ya fue entregada.

### 8. Sesion: opcion "No cerrar sesion" e inactividad de 2 horas

**Por que:** antes la sesion dependia del `session.gc_maxlifetime` de PHP (24 min)
y del `C:\xampp\tmp` compartido, lo que cerraba sesion antes de tiempo y sin
control.

- **Cierre por inactividad configurable** (2 h por defecto,
  `SESSION_IDLE_MINUTES`), aplicado por la app en
  [app/Core/Middleware.php](app/Core/Middleware.php) `enforceSession()`, no por
  la recoleccion de basura de PHP. Se reinicia con cada actividad.
- **Casilla "No cerrar sesion en este dispositivo"** en el login: la sesion no
  expira por inactividad y la cookie se vuelve persistente
  (`SESSION_REMEMBER_DAYS`, 30 dias por defecto), sobreviviendo al cierre del
  navegador ([resources/views/auth/login.php](resources/views/auth/login.php),
  [app/Services/AuthService.php](app/Services/AuthService.php),
  [app/Core/Session.php](app/Core/Session.php) `persistCookie()`).
- **Carpeta de sesiones propia** en `storage/sessions` (fuera del
  `C:\xampp\tmp` compartido), para que la limpieza de otros proyectos no cierre
  las sesiones de este sistema.

> Nota: al cambiar la carpeta de sesiones, las sesiones abiertas antes de
> actualizar se invalidan una vez (todos vuelven a iniciar sesion).

### 9. Bitacora con detalle de estado y WhatsApp por contexto

**Por que:** la bitacora decia "Cambio de estado" sin indicar a cual, y el boton
de WhatsApp enviaba siempre el mensaje de bienvenida aunque se quisiera pedir la
autorizacion de la cotizacion o avisar que el equipo esta listo.

- **Bitacora**: el evento de cambio de estado ahora muestra el estado anterior y
  el nuevo (por ejemplo "recibida -> en reparacion"), leidos de la auditoria
  ([resources/views/ordenes/show.php](resources/views/ordenes/show.php)).
- **WhatsApp por contexto**: el boton se convierte en un menu con los mensajes
  adecuados —aviso de recepcion, solicitar autorizacion de cotizacion, avisar
  demora / mas tiempo, avisar equipo listo, avisar equipo no reparable,
  agradecer entrega y enviar link del PDF— cada uno con su plantilla
  ([app/Controllers/OrdenController.php](app/Controllers/OrdenController.php),
  usando `MensajeService::whatsappOrden($orden, $plantilla)`).
- Se mejoraron/agregaron plantillas: `whatsapp.diagnostico_listo` (cotizacion
  por validar), `whatsapp.equipo_listo` (equipo listo), y las nuevas
  `whatsapp.demora`, `whatsapp.no_reparable` y `whatsapp.entregado`. Migracion
  [database/upgrade_whatsapp_templates.php](database/upgrade_whatsapp_templates.php):
  actualiza los textos solo si siguen siendo los originales y agrega las nuevas
  plantillas si faltan (no pisa personalizaciones).

### 10. Desbloqueo del equipo visible en pantalla

**Por que:** el patron/clave se capturaba y salia en el ticket impreso, pero el
tecnico no lo veia en pantalla al revisar la orden.

- Nueva tarjeta "Desbloqueo del equipo" en la ficha de la orden
  ([resources/views/ordenes/show.php](resources/views/ordenes/show.php)) y en la
  ficha del equipo ([resources/views/equipos/show.php](resources/views/equipos/show.php)):
  muestra el patron dibujado (con secuencia, inicio y fin) o la clave/PIN.
- El patron se dibuja sobre fondo claro (`.unlock-box`) para verse bien en
  cualquier tema. Solo visible dentro del panel autenticado; no se expone en el
  portal publico.

### 11. Notificaciones in-app (campana en la barra)

**Por que:** el tecnico necesita enterarse de que hay una orden nueva por atender
o de que el cliente autorizo la cotizacion, sin depender de que alguien le avise.

- **Campana con contador** en la barra superior (para cualquier usuario del
  panel); muestra las no leidas, un menu con las recientes y una pagina
  `/notificaciones` con el historico. Cada tipo tiene su icono.
- **Avisos automaticos**:
  - *Orden nueva* -> a todos los tecnicos (roles `tecnico`/`tecnico_senior`)
    al crear una orden, con enlace directo a la orden
    ([app/Services/OrdenService.php](app/Services/OrdenService.php)).
  - *Cotizacion autorizada* -> al tecnico asignado de la orden (o a todos si no
    hay asignado) cuando el cliente acepta la cotizacion, desde el portal o
    manualmente ([app/Services/CotizacionService.php](app/Services/CotizacionService.php)).
- Piezas: tabla `notificaciones` (schema + migracion
  [database/upgrade_notificaciones.php](database/upgrade_notificaciones.php)),
  `NotificacionRepository`, `NotificacionService`, `NotificacionController` y
  rutas `/notificaciones`, `/notificaciones/{id}`, `/notificaciones/leer-todas`.
- La creacion de notificaciones nunca interrumpe la operacion principal (si algo
  falla, se ignora en silencio).

### 12. Modulo de almacen (inventario y proveedores) con CRUD

**Por que:** el inventario solo mostraba stock bajo (solo lectura); faltaba
poder gestionar refacciones, entradas/salidas y proveedores.

- **Refacciones (CRUD)**: listado con busqueda y filtro de stock bajo, alta,
  edicion y ficha con historial de movimientos
  ([app/Controllers/InventarioController.php](app/Controllers/InventarioController.php)).
- **Movimientos de stock**: entrada, salida y ajuste (conteo fisico) en una
  sola transaccion; el stock inicial se registra como una entrada para dejar
  rastro; no permite dejar stock negativo
  ([app/Services/InventarioService.php](app/Services/InventarioService.php)).
- **Proveedores (CRUD)**: listado con busqueda, alta y edicion
  ([app/Controllers/ProveedorController.php](app/Controllers/ProveedorController.php)).
- **Aviso de stock bajo** al rol `almacen` cuando un movimiento deja una
  refaccion en o por debajo de su minimo (usa las notificaciones in-app).
- **Acceso** al modulo para los roles `almacen`, `tecnico`, `tecnico_senior` y
  `recepcion`, ademas de los administradores. Migracion
  [database/upgrade_inventario_roles.php](database/upgrade_inventario_roles.php).
- Enlace "Proveedores" en el menu lateral.
- **Lector de codigo de barras** en el almacen: boton de escaneo con camara
  ([public/assets/js/barcode-scan.js](public/assets/js/barcode-scan.js),
  reutilizable por atributos) junto al **SKU** de la refaccion (el codigo del
  producto va en el SKU) y en el **buscador** del inventario (escanear para
  encontrar el producto). Lee EAN/UPC/Code128/Code39/QR; un lector USB tambien
  funciona tecleando en el campo enfocado. No requiere cambios en la base
  (usa el campo `sku` existente).

### Otros cambios de la ronda

- Pulido de UI: iconos en titulos/botones, tema aplicado tambien en login y
  portal publico, y sincronizacion del tema entre panel y portal.
- Mejora de la alta rapida de orden: al usar cliente/equipo existente se puede
  actualizar su ficha o crear un equipo nuevo tomando otro como base.
- Impresion manual para movil (boton "Imprimir" en la vista de impresion).
- Documentacion de datos demo en el README.

---

## Antes de 2026-07-07

- `71165c7` — Commit inicial: MVP del sistema de servicios tecnicos
  (autenticacion, roles/permisos, clientes, equipos, ordenes, diagnosticos,
  cotizaciones, pagos, entregas, portal publico, dashboard, reportes,
  configuracion, auditoria y API JSON interna).
