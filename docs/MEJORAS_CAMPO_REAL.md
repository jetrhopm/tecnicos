# Mejoras para Campo Real

Este documento concentra las mejoras detectadas en la revision profunda del
sistema. Cada mejora debe documentarse aqui y en `CHANGELOG.md` conforme se
implemente.

## Prioridad alta

### 1. Pagos, cancelaciones y sobrepagos

Estado: implementado.

Objetivo:

- Validar que todo pago sea mayor a cero.
- Bloquear sobrepagos salvo que exista una regla explicita futura.
- Cancelar pagos con motivo, usuario responsable y auditoria.
- Recalcular saldo de la orden despues de registrar o cancelar pagos.
- Evitar borrado fisico de pagos.

Implementado:

- `PagoService` valida orden, monto positivo y saldo disponible.
- El registro de pagos bloquea sobrepagos para usuarios sin permiso especial.
- Se agrego cancelacion de pagos con motivo, responsable y auditoria.
- Al cancelar un pago se recalcula el saldo de la orden.
- La ficha de orden muestra pagos activos/cancelados y permite cancelar pagos
  activos segun permisos.

### 2. Garantia configurable real

Estado: implementado.

Objetivo:

- Usar dias de garantia desde configuracion, no un valor fijo.
- Permitir garantia por orden cuando aplique.
- Mostrar vigencia y condiciones claramente en entrega y consulta.

Implementado:

- Se agrego la clave `garantia.dias_default` para definir los dias de garantia
  desde Configuracion.
- Al entregar una orden, la garantia usa la vigencia configurada; si el valor es
  `0`, no se genera garantia automatica.
- Las condiciones usan primero la garantia indicada en la orden y, si esta vacia,
  la politica legal configurable del taller.
- El portal publico muestra estado, vigencia y condiciones de la garantia cuando
  ya existe una garantia generada para la orden.
- El PDF de orden mantiene dos copias en la primera hoja y agrega una segunda
  hoja con las condiciones completas para que no se recorten.

### 3. Cotizaciones con bloqueo estricto

Estado: implementado.

Objetivo:

- Evitar que una cotizacion aceptada, rechazada o vencida pueda cambiarse.
- Evitar cotizaciones duplicadas mientras exista una version pendiente.
- Bloquear autorizaciones de cotizaciones vencidas.
- Crear nuevas versiones en lugar de modificar o reutilizar una cotizacion
  cerrada.
- Mantener auditoria de autorizacion, rechazo y vencimiento.

Implementado:

- `CotizacionService` valida la orden, bloquea ordenes entregadas/canceladas y
  no permite crear otra version si la ultima cotizacion sigue pendiente.
- La autorizacion corre en transaccion y usa actualizacion condicional para que
  dobles clics o peticiones repetidas no cambien dos veces el estado.
- Si una cotizacion pendiente ya vencio, se marca como `vencida` y se exige
  generar una nueva version.
- Las cotizaciones cerradas muestran un aviso en la ficha de orden y habilitan
  el formulario de nueva version.
- API y formularios validan importes negativos, cantidad cero y errores basicos
  antes de crear la cotizacion.

### 4. Administracion completa de usuarios

Estado: implementado.

Objetivo:

- Editar usuarios.
- Cambiar/resetear contrasena.
- Activar, bloquear o desactivar usuarios.
- Reasignar roles.
- Auditar cambios de permisos.

Implementado:

- El modulo `/usuarios` permite crear y editar perfil, telefono, estatus y roles.
- El formulario de edicion permite restablecer contrasena dejando la actual si el
  campo queda vacio.
- La lista de usuarios permite activar, desactivar o bloquear cuentas desde una
  accion rapida.
- Se bloquea quitar o desactivar el ultimo superadmin activo y tambien que el
  usuario actual se quite su propio acceso de superadmin.
- Los cambios de perfil, roles, estatus y restablecimiento de contrasena quedan
  registrados en auditoria.

### 5. Refacciones ligadas a reparacion

Estado: implementado.

Objetivo:

- Agregar refacciones desde la orden/reparacion.
- Descontar stock automaticamente.
- Registrar utilidad estimada.
- Revertir movimientos si se cancela una aplicacion.

Implementado:

- La ficha de orden permite aplicar refacciones directamente desde inventario.
- Al aplicar una refaccion se descuenta stock, se registra `refacciones_ordenes`
  y se crea movimiento de inventario tipo `salida` ligado a la orden.
- Se bloquea stock negativo y no se permite aplicar refacciones en ordenes
  entregadas o canceladas.
- Se puede cancelar una refaccion aplicada con motivo; el sistema devuelve el
  stock y registra movimiento tipo `cancelacion`.
- La orden muestra refacciones activas/canceladas, importe estimado y motivo de
  cancelacion.
- Se agrego `database/upgrade_refacciones_ordenes_estado.php` para instalaciones
  existentes.

### 6. Agenda minima operativa

Estado: implementado.

Objetivo:

- Vista diaria/semanal por tecnico.
- Eventos ligados a orden.
- Programar entrega, visita o revision.
- Registrar carga de trabajo.

Implementado:

- Se agrego modulo `/agenda` con vista diaria/semanal, filtros por tecnico,
  estado, tipo y busqueda.
- Se pueden crear eventos de visita, entrega, recordatorio, trabajo u otro,
  ligados opcionalmente a una orden por folio, clave o id.
- La ficha de orden permite programar seguimientos y muestra los ultimos eventos
  de esa orden.
- El dashboard muestra la agenda programada del dia.
- Los eventos pueden cambiar entre `programado`, `realizado` y `cancelado` con
  auditoria.
- Se agrego `database/upgrade_agenda_roles.php` para permisos de roles
  operativos en instalaciones existentes.

## Prioridad media

### 7. Reportes exportables

Estado: implementado.

Objetivo:

- Exportar CSV.
- Corte de caja por usuario/fecha/metodo.
- Saldos pendientes.
- Refacciones mas usadas.
- Utilidad estimada.

Implementado:

- El modulo `/reportes` incluye filtros por fecha y botones de exportacion CSV.
- Se agrego corte de caja agrupado por fecha, usuario y metodo de pago.
- Se agrego reporte de saldos pendientes con cliente, telefono, estado y saldo.
- Se agrego reporte de refacciones mas usadas con venta, costo y utilidad
  estimada.
- Se agrego reporte de utilidad estimada por orden, separando mano de obra
  aproximada y margen de refacciones activas.

### 8. Pruebas reales de impresion

Estado: pendiente.

Objetivo:

- Probar PDF carta en impresora real.
- Probar ticket 80/58 mm.
- Probar etiqueta de equipo.
- Probar lectura de codigo con lector USB y camara movil.

### 9. Checklist de produccion/seguridad

Estado: implementado.

Objetivo:

- Alerta si `APP_DEBUG=true` en hosting.
- Alerta si usuarios demo siguen activos.
- Verificacion de permisos de `storage`.
- Respaldo de base de datos.
- Configuracion de dominio/HTTPS.

Implementado:

- Configuracion muestra un checklist de produccion con estado `ok`, `warning` o
  `danger`.
- El checklist alerta si `APP_DEBUG` esta activo, si `APP_ENV` no es
  `production`, si `APP_URL` sigue en `auto`/local o si la peticion no usa
  HTTPS.
- Se detectan usuarios demo activos para recordar bloquearlos o cambiar
  contrasenas antes de produccion.
- Se verifican permisos de escritura en `storage`, `storage/uploads` y
  `storage/logs`, ademas de `.htaccess` raiz y de `storage`.
- Se alerta si no existe un respaldo reciente en `storage/backups`.
