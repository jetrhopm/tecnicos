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

Estado: pendiente.

Objetivo:

- Editar usuarios.
- Cambiar/resetear contrasena.
- Activar, bloquear o desactivar usuarios.
- Reasignar roles.
- Auditar cambios de permisos.

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

Estado: pendiente.

Objetivo:

- Vista diaria/semanal por tecnico.
- Eventos ligados a orden.
- Programar entrega, visita o revision.
- Registrar carga de trabajo.

## Prioridad media

### 7. Reportes exportables

Estado: pendiente.

Objetivo:

- Exportar CSV.
- Corte de caja por usuario/fecha/metodo.
- Saldos pendientes.
- Refacciones mas usadas.
- Utilidad estimada.

### 8. Pruebas reales de impresion

Estado: pendiente.

Objetivo:

- Probar PDF carta en impresora real.
- Probar ticket 80/58 mm.
- Probar etiqueta de equipo.
- Probar lectura de codigo con lector USB y camara movil.

### 9. Checklist de produccion/seguridad

Estado: pendiente.

Objetivo:

- Alerta si `APP_DEBUG=true` en hosting.
- Alerta si usuarios demo siguen activos.
- Verificacion de permisos de `storage`.
- Respaldo de base de datos.
- Configuracion de dominio/HTTPS.
