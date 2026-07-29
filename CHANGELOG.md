# Registro de cambios

Historial resumido del sistema. El detalle exacto de cada cambio vive en git.

---

## 2026-07-29

### SQL limpio de instalacion

- Se eliminaron los scripts PHP temporales de `database/` usados para migraciones,
  carga de datos y verificaciones locales.
- `database/schema.sql` queda como estructura limpia, sin datos y sin
  `CREATE DATABASE`/`USE`.
- Se agregaron `database/seed_roles_demo.sql` para roles, permisos y usuarios
  demo, `database/seed_configuracion_base.sql` para configuracion operativa,
  `database/seed_catalogos_base.sql` para marcas/modelos base, y
  `database/seed_demo_data.sql` para datos operativos demo.
- La instalacion nueva se hace importando SQL en orden desde phpMyAdmin/MySQL.

### Configuracion

- Se agrego configuracion editable de moneda, codigo de pais para WhatsApp y
  zona horaria.
- El usuario/rol de licenciamiento quedo protegido contra edicion desde panel,
  URL directa o POST manual por usuarios que no sean `licenciante`.

## 2026-07-28

### Demo comercial y licenciamiento

- Se agrego rol `licenciante` y modulo `/licencia` para controlar vigencia de
  demo, bloqueo manual, mensajes y reinicio de contador.
- El login bloquea usuarios operativos cuando la demo vence o se bloquea
  manualmente; `licenciante` queda exento para reactivar la instalacion.

### Punto de venta e inventario

- Se agrego punto de venta de refacciones con buscador asincrono, soporte para
  SKU/lector de barras, carrito persistente en navegador, modal de cobro y
  ticket interno.
- Las cotizaciones se conectaron con inventario y permiten multiples piezas o
  conceptos en una misma cotizacion.
- El flujo de reparacion descuenta inventario al aplicar refacciones y registra
  movimientos de stock.
- Se agrego corte de caja operativo por turno.

### Catalogos y ordenes

- Se agregaron catalogos de marcas/modelos con busqueda asincrona y aprendizaje
  automatico al guardar marcas/modelos nuevos.
- Se optimizo alta rapida de orden con cliente/equipo nuevo o existente.
- Se agrego selector visual de patron/PIN para equipos.
- Se agrego entrega por clave/codigo de barras y registro de quien entrega.

### UI y mobile

- Se agrego tema Blueprint neon y mejoras visuales en inputs, selects, tablas y
  formularios.
- Se agrego barra inferior mobile con accesos rapidos por rol.
- Se corrigieron detalles de tema dark y modal de punto de venta.

### Documentos

- Se redisenaron comprobantes de recepcion y entrega en hoja carta y tickets
  termicos 80/58 mm.
- Se ajusto PDF de orden con mejor layout, acentos, garantia y enlaces
  clicables.
- Se agrego etiqueta de equipo con codigo de barras.

## 2026-07-08

### Operacion de taller

- Se consolidaron modulos principales: clientes, equipos, ordenes,
  diagnosticos, cotizaciones, pagos, garantias, agenda, reportes, configuracion,
  auditoria y portal publico.
- Se agregaron notificaciones internas, mensajes WhatsApp por contexto y
  dashboard operativo.

## 2026-06

### Base MVP

- Proyecto PHP/MySQL con arquitectura MVC ligera propia.
- Autenticacion, sesiones seguras, CSRF, permisos por rol, PDO con prepared
  statements, rutas amigables y vistas Bootstrap.
