# Sistema Web de Gestion de Servicios Tecnicos y Reparaciones

Sistema web PHP/MySQL para negocios de reparacion de celulares, computadoras,
electrodomesticos, electronica, impresoras, motos, herramientas y otros equipos.

El objetivo es controlar el ciclo completo de una reparacion: cliente, equipo,
orden, diagnostico, cotizacion, autorizacion, pago, entrega, garantia,
comunicacion y consulta publica del avance.

> Importante: este repositorio incluye credenciales locales y datos demo para
> instalar rapido en XAMPP/Laragon/WAMP. Son credenciales de prueba. Antes de
> usar el sistema en Hostinger, hosting publico o produccion, cambia todas las
> contrasenas, `APP_DEBUG`, usuario MySQL y datos del negocio.

## Estado del proyecto

MVP funcional con arquitectura modular propia tipo MVC ligero:

- PHP 8.4+, MySQL/MariaDB y PDO.
- HTML5, CSS3, JavaScript vanilla y Bootstrap 5.
- Rutas amigables con `.htaccess`.
- Login, logout, sesiones seguras, CSRF y hashes con `password_hash`.
- Roles y permisos configurables por modulo/accion.
- Usuarios y roles desde panel.
- Rol `licenciante` para administrar demo comercial, días activos y cuentas de
  usuario/superadmin sin acceso operativo al taller.
- Clientes, equipos y ordenes de servicio.
- Catalogo de marcas/modelos para equipos con busqueda asincrona; los modelos
  se filtran por marca y el sistema aprende marcas/modelos nuevos al guardar.
- Alta rapida de orden con cliente/equipo nuevo o existente.
- Edicion controlada de datos del cliente/equipo al crear orden.
- Opcion de crear equipo nuevo tomando como base un equipo existente.
- Selector de tipo de servicio con busqueda.
- Patron/PIN del equipo en registro de orden (rejilla 3x3 o clave).
- Diagnosticos, cotizaciones y pagos.
- Cotizaciones conectadas con inventario: una cotizacion puede llevar varios
  conceptos y cada refaccion cotizada puede descontar stock cuando ya fue
  aceptada.
- Punto de venta de refacciones para venta de mostrador sin crear orden.
- Entrega de equipos por clave/codigo de barras aleatoria.
- Registro de quien entrega el equipo.
- Documentos imprimibles de orden y de entrega en tamano carta y ticket
  termico 80/58 mm, con logo del negocio configurable.
- PDF de la orden generado al vuelo (no se almacena).
- Evidencia de aceptacion del cliente (foto del ticket firmado) y bitacora
  por orden.
- Temas de diseno seleccionables, incluido "Blueprint neon".
- Portal publico de consulta por folio/token.
- Dashboard, caja/corte operativo, reportes iniciales, configuracion y auditoria.
- API JSON interna con formato consistente (con CSRF).
- SQL separados para estructura, roles/usuarios demo y datos operativos demo.

> El historial detallado de cambios esta en [CHANGELOG.md](CHANGELOG.md).
> La arquitectura del sistema (capas, ciclo de peticion, convenciones y como
> extenderlo) esta en [docs/ARQUITECTURA.md](docs/ARQUITECTURA.md).

## Requisitos

- PHP 8.4 o superior.
- MySQL 8 o MariaDB compatible.
- Apache con `mod_rewrite` o Nginx configurado para enrutar a `public/index.php`.
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`.
- Composer es opcional. El sistema puede correr con el autoload propio incluido.

## Instalacion local rapida

Ruta recomendada en Windows/XAMPP:

```text
C:\xampp\htdocs\tecnico
```

Credenciales locales incluidas para demo:

```ini
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=servicio_tecnico_db
DB_USERNAME=root
DB_PASSWORD=rufles123
APP_URL=auto
```

Pasos:

1. Copia el proyecto a `C:\xampp\htdocs\tecnico`.
2. Copia `.env.example` a `.env`.
3. Verifica que `.env` tenga las credenciales anteriores.
4. Crea la base de datos desde phpMyAdmin:

   ```sql
   CREATE DATABASE servicio_tecnico_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

5. Importa los SQL en este orden:

   ```text
   database/schema.sql
   database/seed_roles_demo.sql
   database/seed_catalogos_base.sql
   database/seed_demo_data.sql
   ```

   `seed_catalogos_base.sql` carga marcas/modelos base. El cuarto archivo es
   opcional; carga datos demo de operacion para probar ordenes, inventario,
   pagos y agenda.

6. Abre:

   ```text
   http://localhost/tecnico
   ```

Si pruebas desde celular en la misma red, entra con la IP de la computadora:

```text
http://192.168.1.130/tecnico
```

Con `APP_URL=auto`, las ligas se generan usando el host real desde donde entras.
En hosting puedes cambiarlo a tu dominio:

```ini
APP_URL=https://tudominio.com
```

## Instalacion manual por phpMyAdmin

1. Crea la base de datos:

   ```sql
   CREATE DATABASE servicio_tecnico_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. Importa en este orden:

   ```text
   database/schema.sql
   database/seed_roles_demo.sql
   database/seed_catalogos_base.sql
   database/seed_demo_data.sql
   ```

   `schema.sql` contiene solo estructura. `seed_roles_demo.sql` contiene roles,
   permisos, configuracion base y usuarios demo. `seed_catalogos_base.sql`
   contiene marcas/modelos base. `seed_demo_data.sql` contiene datos demo
   operativos, pero no crea roles ni usuarios.

3. Copia `.env.example` a `.env`.
4. Ajusta credenciales si tu MySQL no usa `root / rufles123`.

## Instalacion en Hostinger

En Hostinger normalmente primero creas la base de datos desde el panel y luego
importas los SQL en phpMyAdmin con la base ya seleccionada:

```text
database/schema.sql
database/seed_roles_demo.sql
database/seed_catalogos_base.sql
database/seed_demo_data.sql
```

Estos archivos no incluyen `CREATE DATABASE` ni `USE`, por eso son compatibles
con phpMyAdmin de Hostinger cuando ya seleccionaste la base. Usan `utf8mb4` para
conservar acentos, enie, simbolos y compatibilidad UTF-8 completa en
MySQL/MariaDB. Si quieres una base sin datos operativos demo, importa
`schema.sql`, `seed_roles_demo.sql` y `seed_catalogos_base.sql`, omitiendo
`seed_demo_data.sql`.

Despues de importarlo, configura `.env` con los datos reales que te da
Hostinger: `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` y `APP_URL`.

Para armar un ZIP/FTP de produccion revisa `.hostingerignore`: ahi se listan
archivos y carpetas que no conviene subir al hosting, como `.git`, `tests`,
`docs`, SQL, logs, sesiones, backups y uploads locales. El `.htaccess` raiz
tambien bloquea acceso web a carpetas internas si por error se suben.

## Credenciales iniciales

Administrador principal:

| Rol | Correo | Contrasena |
| --- | --- | --- |
| superadmin | `admin@local.test` | `password` |

Todos los usuarios demo usan la misma contrasena:

```text
password
```

## SQL incluidos

- `database/schema.sql`: estructura limpia de tablas, llaves, indices y
  relaciones. No carga datos.
- `database/seed_roles_demo.sql`: roles, permisos, configuracion base y usuarios
  demo necesarios para iniciar sesion.
- `database/seed_catalogos_base.sql`: catalogo base de marcas y modelos para
  usar el sistema desde la primera configuracion sin cargar datos demo.
- `database/seed_demo_data.sql`: datos demo operativos, como cliente, equipos,
  proveedor, inventario, orden, diagnostico, cotizacion, pago, garantia,
  mensajes y agenda. No crea roles ni usuarios.

## Roles y usuarios demo

El archivo `database/seed_roles_demo.sql` crea todos los roles base y un usuario
demo por rol. Estos usuarios son para pruebas locales, capturas, capacitacion y
revision funcional.

| Rol tecnico | Nombre demo | Correo | Contrasena | Uso principal |
| --- | --- | --- | --- | --- |
| `superadmin` | Superadmin Demo | `superadmin@local.test` | `password` | Acceso total, permisos, configuracion y usuarios. |
| `licenciante` | Administrador de Licencias | `admin@gocentersuplementos.com.mx` | `Maquinaria256*` | Control comercial de demo, usuarios y superadmins. |
| `admin` | Administrador Demo | `administrador@local.test` | `password` | Administracion general sin eliminaciones sensibles. |
| `recepcion` | Recepcion Demo | `recepcion@local.test` | `password` | Clientes, equipos, ordenes, recepcion y mensajes. |
| `tecnico` | Tecnico Demo | `tecnico@local.test` | `password` | Ordenes, diagnosticos, reparaciones y avances. |
| `tecnico_senior` | Tecnico Senior Demo | `tecnico_senior@local.test` | `password` | Diagnostico avanzado, cotizaciones, autorizaciones e inventario. |
| `almacen` | Almacen Demo | `almacen@local.test` | `password` | Inventario, proveedores y refacciones. |
| `caja` | Caja Demo | `caja@local.test` | `password` | Pagos, punto de venta, corte de caja y entrega operativa. |
| `cliente_consulta` | Cliente Consulta Demo | `cliente_consulta@local.test` | `password` | Rol reservado para consulta/portal; no debe tener acceso administrativo amplio. |

Nota sobre `cliente_consulta`: el portal publico actual no requiere login; usa
folio y token. Este rol queda sembrado para evolucion futura, por ejemplo app
de cliente o acceso autenticado limitado.

## Permisos por rol

Los permisos se guardan en base de datos:

- `roles`
- `permissions`
- `role_permissions`
- `user_roles`

Permisos disponibles por modulo:

```text
ver, crear, editar, eliminar, autorizar, cambiar_estado, exportar, imprimir, administrar
```

Resumen operativo:

- `superadmin`: todos los permisos.
- `licenciante`: administra la demo comercial y usuarios/superadmins; no opera
  módulos de taller como órdenes, caja, inventario o reportes.
- `admin`: permisos amplios, excepto eliminaciones y administracion de usuarios.
- `recepcion`: clientes, equipos, ordenes, mensajes e inicio de pagos.
- `tecnico`: ordenes, diagnosticos, reparaciones y creacion de cotizaciones.
- `tecnico_senior`: tecnico mas autorizaciones, cotizaciones e inventario.
- `almacen`: inventario, proveedores y consulta de ordenes.
- `caja`: pagos, punto de venta, corte de caja, impresion y consulta de ordenes.
- `cliente_consulta`: reservado para consulta limitada futura.

Desde el panel **Usuarios y roles**, un usuario con permiso de administracion
puede crear cuentas, editar perfil, reasignar roles, activar/desactivar/bloquear
usuarios y restablecer contrasenas. El sistema impide dejar la instalacion sin
un `superadmin` activo y registra estos cambios en auditoria.

## Demo comercial y licenciante

La versión demo incluye un control de vigencia en `/licencia`. Por defecto:

- La demo queda activa.
- El contador inicia el día de instalación/importación.
- La duración inicial es de 14 días.
- Al vencer, los usuarios operativos no pueden iniciar sesión y ven el mensaje
  configurado de demostración terminada.
- El rol `licenciante` queda exento del bloqueo para poder extender días,
  reiniciar contador o desactivar el bloqueo demo.
- El bloqueo manual permite pausar el acceso operativo de inmediato aunque la
  demo todavía tenga días disponibles.

Este rol no es una puerta trasera: está documentado, versionado y auditado. En
instalaciones reales cambia la contraseña inicial o reemplaza esta cuenta por la
cuenta comercial que vaya a administrar licencias.

## Datos demo incluidos

El seed crea:

- Roles base.
- Permisos base.
- Catalogo inicial de marcas y modelos comunes del mercado para celulares,
  laptops, consolas, impresoras, electrodomesticos, herramientas y motos.
- Usuarios demo con contrasena `password`.
- Cliente demo: `Cliente Demo Taller`.
- Nueve equipos demo, uno por tipo.
- Proveedor demo.
- Inventario demo para probar cotizaciones y punto de venta con SKU, lector de
  barras, busqueda por categoria, marca y modelo compatible.
- Orden demo con folio:

  ```text
  ST-DEMO-00001
  ```

## Reportes y exportaciones

El modulo **Reportes** permite filtrar por fecha y exportar CSV de:

- Corte de caja operativo por turno en `/caja`.
- Reportes administrativos por fecha, usuario y metodo de pago en `/reportes`.
- Saldos pendientes.
- Refacciones mas usadas.
- Utilidad estimada por orden.

Los CSV se generan al momento desde la base de datos; no se guardan en el
servidor.

## Etiquetas y codigos de barras

Cada orden genera una `codigo_entrega` unico. Desde la ficha de orden, en
**Imprimir > Etiqueta equipo**, se puede imprimir una etiqueta Code 39 para
pegar al dispositivo. Ese codigo funciona con lectores USB como si fuera
teclado y tambien puede leerse desde el modulo de entregas con camara cuando el
navegador lo permita. La etiqueta no contiene patron, costos ni notas internas;
solo sirve como llave operativa para localizar y liberar el equipo correcto.

## Catalogo de marcas y modelos

Los formularios de **Equipos** y **Nueva orden** usan busqueda asincrona en los
campos Marca y Modelo:

- Al escribir una marca, el sistema consulta `/api/catalogos/marcas`.
- Al escribir un modelo, el sistema consulta `/api/catalogos/modelos` filtrando
  por la marca seleccionada o escrita.
- Si la marca o modelo no existe, se puede dejar el texto capturado; al guardar
  se crea en `equipo_marcas` o `equipo_modelos` para futuras ordenes.
- La tabla `equipos` conserva los textos `marca` y `modelo` por compatibilidad,
  y tambien guarda `marca_id` y `modelo_id` cuando existe relacion con catalogo.
- El catalogo base de marcas/modelos se carga desde `seed_catalogos_base.sql`.

## Checklist de produccion

En **Configuracion** aparece un checklist de seguridad para revisar antes de
subir a hosting: `APP_DEBUG`, `APP_ENV`, dominio/HTTPS, usuarios demo activos,
permisos de `storage`, proteccion `.htaccess` y respaldos recientes en
`storage/backups`. Las credenciales demo y locales deben cambiarse o bloquearse
antes de usar el sistema con clientes reales.

- Diagnostico demo.
- Cotizacion demo.
- Anticipo demo.
- Garantia demo.
- Mensaje demo.
- Evento de agenda demo.

Clave demo para probar entrega por codigo de barras:

```text
ENT-DEMO2468
```

## Flujo principal de operacion

1. Recepcion registra o selecciona cliente.
2. Recepcion registra o selecciona equipo.
3. Si el equipo existente cambia, el sistema obliga a elegir:
   - actualizar equipo seleccionado, o
   - crear nuevo equipo usando esos datos como base.
4. Se crea la orden.
5. El sistema genera folio, token publico y clave de entrega.
6. Se imprime nota o comprobante.
7. Tecnico registra diagnostico tecnico sin capturar importes.
8. Se genera cotizacion con mano de obra, servicios y/o refacciones tomadas del
   inventario.
9. Cliente acepta o rechaza.
10. Se programa seguimiento en agenda si hace falta.
11. Tecnico repara, aplica las refacciones cotizadas o captura refacciones
    adicionales si hacen falta y marca resultado.
12. Caja registra anticipo, pago parcial o liquidacion.
13. Entrega libera el equipo usando la clave/codigo de barras.
14. El sistema registra quien entrego.
15. Se genera comprobante y garantia cuando aplica.

Reglas de cotizacion:

- Solo puede existir una cotizacion pendiente por orden.
- Una cotizacion aceptada, rechazada o vencida no se modifica; se genera una
  nueva version.
- Las cotizaciones vencidas no se pueden autorizar y quedan marcadas como
  `vencida`.
- El diagnostico describe la falla, pruebas y piezas necesarias; los precios de
  mano de obra, servicios y refacciones se capturan en Cotizacion.
- Una misma cotizacion puede incluir varios renglones, por ejemplo bateria,
  display y mano de obra en una sola autorizacion.
- Si se selecciona una refaccion de inventario en la cotizacion, el sistema toma
  su precio de venta como base y guarda el costo de inventario como snapshot.

Reglas de refacciones:

- Las refacciones se aplican desde la ficha de la orden.
- Las refacciones que vienen de una cotizacion aceptada se pueden aplicar en
  bloque desde la orden y quedan ligadas al concepto cotizado.
- Al aplicar una refaccion se descuenta stock y queda movimiento ligado a la
  orden.
- En **Punto de venta**, una venta de mostrador descuenta stock, genera folio
  propio y emite ticket imprimible sin asociarse a una orden de reparacion.
- El punto de venta usa buscador asincrono: al escanear o escribir un SKU unico
  agrega el producto directo a la tabla; por nombre, marca o modelo muestra
  coincidencias para seleccionar.
- El cobro abre una ventana modal donde se elige metodo de pago y se captura
  referencia y cliente opcional para el ticket.
- El ticket se muestra en una ventana dentro del sistema y la lista de venta se
  guarda temporalmente en el navegador hasta confirmar el cobro o usar
  **Limpiar venta**.
- Al recuperar una venta guardada por recarga o cierre accidental, el sistema
  conserva ID y SKU; si falta el ID local, el backend busca la refaccion por SKU
  y descuenta inventario en la misma transaccion.
- Si se aplico por error, se cancela con motivo y el stock se devuelve.
- No se permite stock negativo ni aplicar refacciones a ordenes entregadas o
  canceladas.

Agenda operativa:

- La ruta `/agenda` permite vista diaria o semanal.
- Los eventos pueden ligarse a una orden por folio, clave o id.
- Desde la ficha de orden se puede programar seguimiento, entrega, visita o
  recordatorio.
- El dashboard muestra la agenda programada del dia.

## Consulta publica del cliente

Rutas disponibles:

```text
/consulta.php?folio=FOLIO&token=TOKEN
/consulta/FOLIO/TOKEN
```

El cliente puede ver estado, equipo, diagnostico visible, cotizacion visible,
comentarios visibles, saldo y datos de contacto. No ve notas internas, usuarios
internos, costos internos ni auditoria privada.

## Base de datos limpia

Los scripts PHP de migracion fueron eliminados para mantener la entrega limpia.
Para una instalacion nueva importa siempre los SQL en orden:

```text
database/schema.sql
database/seed_roles_demo.sql
database/seed_catalogos_base.sql
database/seed_demo_data.sql
```

Si ya existe una base con datos reales, no importes `schema.sql` encima porque
contiene `DROP TABLE`. En ese caso conviene respaldar primero y crear una
migracion SQL especifica para el cambio que necesites.

Opcional en `.env`:

```ini
# Solo si corres detras de un proxy/balanceador
APP_TRUST_PROXY=false
# Sesion: minutos de inactividad y dias que dura "No cerrar sesion"
SESSION_IDLE_MINUTES=120
SESSION_REMEMBER_DAYS=30
```

Tras migrar, en Configuracion puedes cambiar el nombre del sistema
(`sistema.nombre`), subir el logo del taller (`negocio.logo_url`) y ajustar el
texto de garantia del ticket (`ticket.garantia`) o la politica legal
(`legal.politica_garantia`). La duracion de la garantia automatica al entregar
un equipo se controla con `garantia.dias_default`; usa `0` si no quieres que se
genere garantia automatica.

La moneda visible en importes se controla con `sistema.moneda` usando una clave
ISO de 3 letras, por ejemplo `MXN`, `USD` o `COP`. El prefijo usado en links de
WhatsApp se controla con `whatsapp.codigo_pais`; guarda solo el codigo numerico,
por ejemplo `52` para Mexico o `1` para Estados Unidos/Canada.

La zona horaria se controla con `sistema.zona_horaria` usando nombres IANA, por
ejemplo `America/Mexico_City`, `America/Bogota` o `Europe/Madrid`. El valor
`APP_TIMEZONE` del `.env` queda como respaldo si la base de datos todavia no
esta disponible.

## Entrega por codigo de barras

La entrega se hace desde:

```text
/entregas
```

El usuario escanea o teclea la clave de entrega de la nota del cliente. Esa clave
es aleatoria (`ENT-XXXXXXXX`), no es el folio y no se deriva de el. Esto reduce
entregas equivocadas y deja registro de quien libero el equipo.

## Impresion de documentos

Tanto la orden de recepcion como el comprobante de entrega se imprimen en tres
formatos, elegibles desde la ficha de la orden (boton Imprimir / Comprobante):

- Hoja carta (recuadros, garantia y firmas).
- Ticket termico 80 mm.
- Ticket termico 58 mm.

El encabezado usa el logo y los datos del negocio de Configuracion. El patron de
desbloqueo se dibuja en el documento a partir del campo del equipo. El PDF de la
orden se genera al momento y no se almacena.

## Evidencia y bitacora

En la ficha de la orden se puede subir la foto del ticket firmado como evidencia
y marcar que el cliente acepto presupuesto y terminos. La foto se guarda en
`storage/uploads` (fuera del webroot) y se sirve por ruta autenticada; el PDF no
se guarda. La bitacora muestra el historico de la orden (creacion, cambios de
estado, evidencia, aceptacion, PDF generado y entrega).

## API JSON

Formato exitoso:

```json
{
  "success": true,
  "message": "Operacion realizada correctamente",
  "data": {},
  "errors": []
}
```

Formato de error:

```json
{
  "success": false,
  "message": "No se pudo completar la operacion",
  "data": null,
  "errors": [
    {
      "field": "telefono",
      "message": "El telefono es obligatorio"
    }
  ]
}
```

Endpoints iniciales:

- `GET /api/clientes`
- `POST /api/clientes`
- `GET /api/ordenes`
- `POST /api/ordenes`
- `GET /api/ordenes/{id}`
- `PATCH /api/ordenes/{id}/estado`
- `POST /api/cotizaciones`
- `POST /api/pagos`
- `GET /api/reportes/dashboard`
- `GET /api/inventario/stock-bajo`

## Estructura de carpetas

```text
app/
  Controllers/     Controladores HTTP/API
  Services/        Reglas de negocio y transacciones
  Repositories/    Acceso a base de datos con PDO
  Core/            Router, Request, Response, Auth, Session, CSRF, View
  Helpers/         Funciones reutilizables
  Validators/      Validacion por modulo
  Policies/        Reglas especiales por modulo
config/            Configuracion PHP
database/          SQL de estructura, roles demo, catalogos base y datos demo
docs/              Manuales y documentos generados
public/            Front controller y assets publicos
resources/views/   Vistas HTML/Bootstrap
storage/           Uploads, logs y backups privados
tests/             Pruebas de funciones puras
```

## Seguridad aplicada

- PDO con prepared statements.
- CSRF en formularios y en la API (`_csrf` o header `X-CSRF-TOKEN`).
- Escape HTML con `e()`.
- Sesiones con cookie `HttpOnly` y `SameSite=Lax`, y `session.use_strict_mode`.
- Cierre de sesion por inactividad (2 h por defecto) con opcion "No cerrar
  sesion" y carpeta de sesiones propia en `storage/sessions`.
- Regeneracion de ID de sesion al iniciar login.
- Passwords hasheadas con `password_hash` (minimo 8 caracteres al crear).
- Freno de fuerza bruta en login (5 intentos por email/IP en 15 minutos).
- Manejador global de excepciones: registra en `storage/logs` y no expone
  trazas ni SQL al navegador salvo con `APP_DEBUG=true`.
- Cabeceras de seguridad (nosniff, X-Frame-Options, Referrer-Policy,
  Permissions-Policy, HSTS en HTTPS) y sin `X-Powered-By`.
- URLs internas relativas a la raiz (evita contenido mixto en HTTPS).
- Cabeceras `X-Forwarded-*` solo se aceptan con `APP_TRUST_PROXY=true`.
- Clave de entrega aleatoria (no derivada del folio).
- Validacion de entrada en servicios/controladores.
- Auditoria para acciones criticas.
- `storage` protegido con `.htaccess`.
- `.env` real ignorado por Git.
- `.env.example` versionado solo como plantilla local/demo.

## Recomendaciones obligatorias para produccion/hosting

Antes de subir a Hostinger o publicar el sistema:

1. Cambia todas las contrasenas demo.
2. Cambia `DB_PASSWORD`.
3. Usa un usuario MySQL propio, no `root`.
4. Configura:

   ```ini
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://tudominio.com
   ```

5. Fuerza HTTPS.
6. Protege backups y uploads.
7. Revisa permisos de `storage/`.
8. Cambia datos del negocio en Configuracion.
9. Borra o reemplaza datos demo si ya no los necesitas.
10. Configura respaldos automaticos de base de datos.

## Pruebas

Ejecuta:

```bash
php tests/run.php
```

Pruebas actuales:

- `calcularSubtotal`
- `calcularIVA`
- `calcularTotal`
- `calcularSaldo`
- `generarFolio`
- `normalizarTelefono`
- `crearMensajeWhatsapp`
- `validarEmail`
- `calcularDiasGarantia`

## Subir a GitHub

Repositorio usado en esta instalacion:

```text
https://github.com/jetrhopm/tecnicos
```

Comandos manuales:

```bash
git status
git add .
git commit -m "Documenta instalacion y usuarios demo"
git remote add origin https://github.com/jetrhopm/tecnicos.git
git push -u origin master
```

Si el repositorio ya tiene `origin`, usa:

```bash
git remote -v
git push -u origin master
```

## Licencia

MIT.
