# Sistema Web de Gestion de Servicios Tecnicos y Reparaciones

Sistema PHP/MySQL para talleres y negocios de reparacion de celulares, computadoras, electrodomesticos, electronica, motos, impresoras, herramientas y otros equipos.

## Estado del proyecto

Esta entrega incluye un MVP instalable con arquitectura modular propia tipo MVC ligero:

- Login, logout, sesiones seguras, CSRF y `password_hash`/`password_verify`.
- Roles y permisos configurables por modulo/accion en base de datos.
- Clientes con busqueda, alta, edicion e historial.
- Equipos asociados a cliente.
- Ordenes de servicio con folio unico, token publico, estado, tecnico, diagnostico, cotizacion, pagos, WhatsApp e impresion.
- Portal publico: `/consulta.php?folio=FOLIO&token=TOKEN` y `/consulta/FOLIO/TOKEN`.
- Ligas amigables con `.htaccess`: `/clientes`, `/ordenes`, `/configuracion`, `/consulta/FOLIO/TOKEN`.
- API JSON basica con formato consistente.
- Dashboard, reportes iniciales, configuracion, usuarios, inventario stock bajo y garantias activas.
- Auditoria para acciones criticas.
- SQL completo y seed inicial.
- Entrega de equipos con clave de codigo de barras desde `/entregas`; cualquier usuario logueado puede liberar, pero el sistema registra quien entrego.

Los modulos de inventario avanzado, agenda, garantias profundas, archivos/evidencias, PDF, firma digital, QR, multi-sucursal y WhatsApp Business API quedan preparados en estructura y base de datos para segunda/tercera version.

## Requisitos

- PHP 8.4 o superior.
- MySQL 8 o MariaDB compatible.
- Apache o Nginx. Funciona en XAMPP, Laragon o WAMP.
- Extensiones PHP: PDO, pdo_mysql, mbstring, openssl.
- Composer es opcional. El sistema trae autoload propio.

## Instalacion local en XAMPP/Laragon

1. Coloca la carpeta del proyecto en:

   `C:\xampp\htdocs\tecnico`

2. Crea la base de datos e importa el esquema. Opcion recomendada por consola:

   ```bash
   php database/install.php
   php database/check.php
   ```

   O importacion manual:

   ```sql
   SOURCE C:/xampp/htdocs/tecnico/database/schema.sql;
   SOURCE C:/xampp/htdocs/tecnico/database/seed.sql;
   ```

   Tambien puedes importarlos desde phpMyAdmin en este orden:

   1. `database/schema.sql`
   2. `database/seed.sql`

3. Copia `.env.example` a `.env` si no existe y ajusta credenciales:

   ```ini
   DB_HOST=localhost
   DB_DATABASE=servicio_tecnico_db
   DB_USERNAME=root
   DB_PASSWORD=rufles123
   APP_URL=auto
   ```

4. Asegura permisos de escritura en:

   - `storage/uploads`
   - `storage/logs`
   - `storage/backups`

5. Abre:

   `http://localhost/tecnico`

   Si quieres probar desde celular en la misma red, entra con la IP de tu computadora, por ejemplo:

   `http://192.168.1.130/tecnico`

   Con `APP_URL=auto`, el sistema genera ligas usando el host real desde donde entras. En produccion tambien puedes fijarlo a tu dominio, por ejemplo `APP_URL=https://tudominio.com`.

   El proyecto incluye un `.htaccess` raiz que redirige internamente hacia `public/index.php`, protege carpetas privadas y permite URLs amigables sin mostrar `/public`.

## Credenciales iniciales

- Email: `admin@local.test`
- Contrasena: `password`
- Rol: `superadmin`

Cambia esta contrasena antes de usar el sistema fuera de local.

## Usuarios demo

Todos usan contrasena `password`:

- `superadmin@local.test`
- `administrador@local.test`
- `recepcion@local.test`
- `tecnico@local.test`
- `tecnico_senior@local.test`
- `almacen@local.test`
- `caja@local.test`
- `cliente_consulta@local.test`

El seed tambien crea un cliente demo, nueve equipos demo, una orden con diagnostico, cotizacion, pago, garantia, mensaje y evento de agenda.

Clave demo para probar entrega por codigo de barras:

- `ENT-ST-DEMO-00001`

## Estructura

```text
app/
  Controllers/     Controladores HTTP y API
  Services/        Reglas de negocio y transacciones
  Repositories/    Acceso a base de datos con PDO
  Core/            Router, Request, Response, Auth, CSRF, Session, View
  Helpers/         Funciones puras reutilizables
  Validators/      Validacion por modulo
  Policies/        Reglas de permisos especiales
config/            Configuracion de app, DB y permisos
database/          schema.sql y seed.sql
public/            Front controller y assets publicos
resources/views/   Vistas HTML Bootstrap
storage/           Uploads, logs y backups privados
tests/             Pruebas de funciones puras
```

## Modulos incluidos

- Autenticacion.
- Usuarios, roles y permisos.
- Clientes.
- Equipos.
- Ordenes de servicio.
- Diagnosticos.
- Cotizaciones y conceptos.
- Pagos y caja basica.
- Portal publico.
- Comunicacion WhatsApp mediante link `wa.me`.
- Dashboard.
- Reportes iniciales.
- Configuracion.
- Auditoria.
- API JSON interna.

## API JSON

Todas las respuestas usan:

```json
{
  "success": true,
  "message": "Operacion realizada correctamente",
  "data": {},
  "errors": []
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

La API usa la sesion autenticada del panel en esta version. Para app movil futura se recomienda agregar tokens personales o OAuth2 ligero.

## Reglas de negocio aplicadas

- Las funciones de calculo son puras y no consultan base de datos.
- Los repositorios solo ejecutan SQL.
- Los servicios concentran reglas y transacciones.
- Los controladores validan acceso, reciben peticiones y devuelven vistas o JSON.
- Una orden no pasa a reparacion sin autorizacion salvo permiso especial.
- Una orden no se entrega con saldo pendiente.
- El estado `entregada` no se cambia manualmente desde la orden; se libera desde `/entregas` usando la clave del codigo de barras de la nota del cliente.
- Una cotizacion aceptada/rechazada registra auditoria.
- El portal publico solo muestra datos visibles para cliente.
- No se borran pagos desde el flujo; se prepara cancelacion con motivo para evolucion.

## Seguridad

- PDO con prepared statements.
- CSRF en formularios.
- Escape HTML con `e()`.
- Sesiones con cookie `HttpOnly` y `SameSite=Lax`.
- Regeneracion de ID al iniciar sesion.
- Passwords hasheadas.
- `.env` fuera del codigo.
- `storage` protegido por `.htaccess`.
- Auditoria de acciones criticas.

## Pruebas

Ejecuta:

```bash
php tests/run.php
```

Las pruebas cubren funciones puras como calculos, folios, telefono, WhatsApp y garantia.

## Produccion

- Cambiar `APP_DEBUG=false`.
- Usar usuario MySQL sin permisos globales.
- Cambiar contrasena del administrador.
- En hosting dedicado, puedes apuntar el document root a `public/`. En XAMPP dentro de `htdocs/tecnico`, el `.htaccess` raiz ya enruta hacia `public`.
- Forzar HTTPS.
- Revisar permisos de escritura en `storage/`.
- Configurar backups de base de datos.
- Reemplazar Bootstrap CDN por assets locales si se requiere operacion sin internet.

## Subir a GitHub

```bash
git init
git add .
git commit -m "Initial MVP servicio tecnico"
git branch -M main
git remote add origin https://github.com/usuario/servicio-tecnico.git
git push -u origin main
```

No subas `.env`; usa `.env.example`.

## Licencia

MIT.

