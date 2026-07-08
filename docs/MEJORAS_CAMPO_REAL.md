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

Estado: pendiente.

Objetivo:

- Usar dias de garantia desde configuracion, no un valor fijo.
- Permitir garantia por orden cuando aplique.
- Mostrar vigencia y condiciones claramente en entrega y consulta.

### 3. Administracion completa de usuarios

Estado: pendiente.

Objetivo:

- Editar usuarios.
- Cambiar/resetear contrasena.
- Activar, bloquear o desactivar usuarios.
- Reasignar roles.
- Auditar cambios de permisos.

### 4. Refacciones ligadas a reparacion

Estado: pendiente.

Objetivo:

- Agregar refacciones desde la orden/reparacion.
- Descontar stock automaticamente.
- Registrar utilidad estimada.
- Revertir movimientos si se cancela una aplicacion.

### 5. Agenda minima operativa

Estado: pendiente.

Objetivo:

- Vista diaria/semanal por tecnico.
- Eventos ligados a orden.
- Programar entrega, visita o revision.
- Registrar carga de trabajo.

## Prioridad media

### 6. Reportes exportables

Estado: pendiente.

Objetivo:

- Exportar CSV.
- Corte de caja por usuario/fecha/metodo.
- Saldos pendientes.
- Refacciones mas usadas.
- Utilidad estimada.

### 7. Pruebas reales de impresion

Estado: pendiente.

Objetivo:

- Probar PDF carta en impresora real.
- Probar ticket 80/58 mm.
- Probar etiqueta de equipo.
- Probar lectura de codigo con lector USB y camara movil.

### 8. Checklist de produccion/seguridad

Estado: pendiente.

Objetivo:

- Alerta si `APP_DEBUG=true` en hosting.
- Alerta si usuarios demo siguen activos.
- Verificacion de permisos de `storage`.
- Respaldo de base de datos.
- Configuracion de dominio/HTTPS.
