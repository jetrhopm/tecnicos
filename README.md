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
- Clientes, equipos y ordenes de servicio.
- Alta rapida de orden con cliente/equipo nuevo o existente.
- Edicion controlada de datos del cliente/equipo al crear orden.
- Opcion de crear equipo nuevo tomando como base un equipo existente.
- Selector de tipo de servicio con busqueda.
- Patron/PIN del equipo en registro de orden.
- Diagnosticos, cotizaciones y pagos.
- Entrega de equipos por clave/codigo de barras.
- Registro de quien entrega el equipo.
- Portal publico de consulta por folio/token.
- Dashboard, reportes iniciales, configuracion y auditoria.
- API JSON interna con formato consistente.
- Seed con usuarios, roles, cliente demo, equipos demo, orden demo y pagos demo.

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
4. Ejecuta instalacion:

   ```bash
   php database/install.php
   php database/check.php
   ```

5. Abre:

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
   database/seed.sql
   ```

3. Copia `.env.example` a `.env`.
4. Ajusta credenciales si tu MySQL no usa `root / rufles123`.

## Credenciales iniciales

Administrador principal:

| Rol | Correo | Contrasena |
| --- | --- | --- |
| superadmin | `admin@local.test` | `password` |

Todos los usuarios demo usan la misma contrasena:

```text
password
```

## Roles y usuarios demo

El archivo `database/seed.sql` crea todos los roles base y un usuario demo por
rol. Estos usuarios son para pruebas locales, capturas, capacitacion y revision
funcional.

| Rol tecnico | Nombre demo | Correo | Contrasena | Uso principal |
| --- | --- | --- | --- | --- |
| `superadmin` | Superadmin Demo | `superadmin@local.test` | `password` | Acceso total, permisos, configuracion y usuarios. |
| `admin` | Administrador Demo | `administrador@local.test` | `password` | Administracion general sin eliminaciones sensibles. |
| `recepcion` | Recepcion Demo | `recepcion@local.test` | `password` | Clientes, equipos, ordenes, recepcion y mensajes. |
| `tecnico` | Tecnico Demo | `tecnico@local.test` | `password` | Ordenes, diagnosticos, reparaciones y avances. |
| `tecnico_senior` | Tecnico Senior Demo | `tecnico_senior@local.test` | `password` | Diagnostico avanzado, cotizaciones, autorizaciones e inventario. |
| `almacen` | Almacen Demo | `almacen@local.test` | `password` | Inventario, proveedores y refacciones. |
| `caja` | Caja Demo | `caja@local.test` | `password` | Pagos, caja, reportes y entrega operativa. |
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
- `admin`: permisos amplios, excepto eliminaciones y administracion de usuarios.
- `recepcion`: clientes, equipos, ordenes, mensajes e inicio de pagos.
- `tecnico`: ordenes, diagnosticos y reparaciones.
- `tecnico_senior`: tecnico mas autorizaciones, cotizaciones e inventario.
- `almacen`: inventario, proveedores y consulta de ordenes.
- `caja`: pagos, reportes, impresion y consulta de ordenes.
- `cliente_consulta`: reservado para consulta limitada futura.

## Datos demo incluidos

El seed crea:

- Roles base.
- Permisos base.
- Usuarios demo con contrasena `password`.
- Cliente demo: `Cliente Demo Taller`.
- Nueve equipos demo, uno por tipo.
- Proveedor demo.
- Refaccion demo.
- Orden demo con folio:

  ```text
  ST-DEMO-00001
  ```

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
7. Tecnico registra diagnostico.
8. Se genera cotizacion.
9. Cliente acepta o rechaza.
10. Tecnico repara o marca resultado.
11. Caja registra anticipo, pago parcial o liquidacion.
12. Entrega libera el equipo usando la clave/codigo de barras.
13. El sistema registra quien entrego.
14. Se genera comprobante y garantia cuando aplica.

## Consulta publica del cliente

Rutas disponibles:

```text
/consulta.php?folio=FOLIO&token=TOKEN
/consulta/FOLIO/TOKEN
```

El cliente puede ver estado, equipo, diagnostico visible, cotizacion visible,
comentarios visibles, saldo y datos de contacto. No ve notas internas, usuarios
internos, costos internos ni auditoria privada.

## Entrega por codigo de barras

La entrega se hace desde:

```text
/entregas
```

El usuario escanea o teclea la clave de entrega de la nota del cliente. Esa clave
no es el folio y no se deriva del folio. Esto reduce entregas equivocadas y deja
registro de quien libero el equipo.

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
database/          schema.sql, seed.sql e instaladores locales
docs/              Manuales y documentos generados
public/            Front controller y assets publicos
resources/views/   Vistas HTML/Bootstrap
storage/           Uploads, logs y backups privados
tests/             Pruebas de funciones puras
```

## Seguridad aplicada

- PDO con prepared statements.
- CSRF en formularios.
- Escape HTML con `e()`.
- Sesiones con cookie `HttpOnly` y `SameSite=Lax`.
- Regeneracion de ID de sesion al iniciar login.
- Passwords hasheadas con `password_hash`.
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
