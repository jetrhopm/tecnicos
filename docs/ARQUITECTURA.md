# Arquitectura del sistema

Documento técnico para cualquier programador que vaya a mantener o extender el
**Sistema Web de Gestión de Servicios Técnicos y Reparaciones**. Explica cómo
está construido, cómo fluye una petición, qué convenciones seguir y cómo agregar
funcionalidad sin romper lo existente.

> Complemento: el historial de cambios está en [CHANGELOG.md](../CHANGELOG.md) y
> la guía de uso en [docs/MANUAL_USUARIO.md](MANUAL_USUARIO.md).

---

## 1. Resumen

Aplicación web monolítica en **PHP puro, sin framework**, con una arquitectura
**MVC ligera propia** por capas (Controllers → Services → Repositories). Renderiza
HTML en el servidor (Bootstrap 5 + JavaScript vanilla) y persiste en
**MySQL/MariaDB vía PDO**. Corre sobre **Apache** (probado en XAMPP/Windows).

Principios:

- **Sin dependencias externas obligatorias.** Composer es opcional; hay autoload
  propio. No hay build tools (npm/webpack): los assets se sirven tal cual.
- **Capas con una sola responsabilidad.** Los controladores no arman SQL; los
  repositorios no aplican reglas de negocio.
- **Todo dato externo es sospechoso.** Validación en servicios/controladores,
  PDO con *prepared statements*, escape de salida con `e()`.

---

## 2. Stack tecnológico

| Área | Tecnología |
| --- | --- |
| Lenguaje | PHP 8 (composer.json pide ≥ 8.4; usa `match`, `readonly`, promoción de constructor, `declare(strict_types=1)`) |
| Base de datos | MySQL 8 / MariaDB, acceso con **PDO** (`ATTR_EMULATE_PREPARES = false`) |
| Servidor web | Apache con `mod_rewrite` (URLs amigables por `.htaccess`) |
| Autoload | Propio (`spl_autoload_register`, estilo PSR-4). Composer opcional |
| UI | Vistas PHP server-side + **Bootstrap 5.3** (CDN) |
| JS | **Vanilla**, sin framework ni bundler |
| CSS | Propio, con **variables CSS** para 5 temas |
| Escaneo | `html5-qrcode` (vendorizada) para códigos de barras/QR |
| PDF | Generador **hecho a mano** (`OrdenPdfService`), sin librería |
| Sesiones | Archivos en `storage/sessions` |
| Config | `.env` (parser propio) + `config/*.php` |

---

## 3. Cómo correrlo (local)

```bash
# 1. Colocar el proyecto en el docroot (ej. XAMPP)
C:\xampp\htdocs\tecnico

# 2. Copiar variables de entorno
cp .env.example .env      # ajustar credenciales de MySQL

# 3. Instalar base de datos + datos demo
php database/install.php
php database/check.php

# 4. Abrir
http://localhost/tecnico
```

Requiere extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `openssl`. Ver
[README.md](../README.md) para instalación por phpMyAdmin y credenciales demo.

---

## 4. Estructura de carpetas

```text
app/
  bootstrap.php        Arranque: autoload, helpers, .env, manejador de errores, sesión
  Core/                Infraestructura: Router, Request, Response, Auth, Session,
                       Csrf, Middleware, Database, View, JsonResponse, Logger, Validator
  Controllers/         Entrada HTTP/API: permisos, leer request, delegar, responder
  Services/            Reglas de negocio y transacciones
  Repositories/        Acceso a datos (solo SQL con PDO)
  Helpers/             Funciones globales (dinero, fechas, cadenas, seguridad, etc.)
  Validators/          Validación por módulo
  Policies/            Reglas de permiso especiales por módulo
  DTO/ Models/         Estructuras de datos (uso ligero; se trabaja con arrays)
config/                app.php, database.php, permissions.php
database/              schema.sql, seed.sql, install.php y scripts upgrade_*.php
docs/                  Manuales y este documento
public/                Front controller (index.php) y assets públicos
  assets/css|js|vendor
resources/views/       Vistas y layouts (HTML/PHP + Bootstrap)
storage/               uploads, logs, backups, sessions (privado, protegido por .htaccess)
tests/                 Runner propio de pruebas de funciones puras
```

---

## 5. Ciclo de vida de una petición

```text
Navegador
   │  GET /tecnico/ordenes/8
   ▼
.htaccess (raíz)           reescribe todo lo que no es archivo/carpeta real → public/index.php
   ▼
public/index.php           require app/bootstrap.php
   │                         ├─ define BASE_PATH
   │                         ├─ registra autoloader (App\ → app/)
   │                         ├─ carga TODOS los app/Helpers/*.php
   │                         ├─ define env_value() y lee .env
   │                         ├─ set_exception_handler (log + respuesta segura)
   │                         └─ Session::start()
   │
   ├─ new Request()
   ├─ Middleware::securityHeaders()   cabeceras de seguridad
   ├─ Middleware::enforceSession()    cierre por inactividad (respeta "recordarme")
   ├─ Middleware::csrf($request)      valida token en POST/PUT/PATCH/DELETE (incl. API)
   ├─ new Router()  +  ~72 rutas registradas
   └─ $router->dispatch($request)
          ▼
   Controller::metodo($request, ...params)
          ├─ Auth::requirePermission('ordenes','ver')   ← control de acceso
          ├─ lee datos con $request->input()/all()
          ├─ delega en un Service (reglas + transacción)
          │        └─ Repository (SQL con PDO)
          └─ responde:
                 ├─ View::render('ordenes/show', $data, 'layouts/app')  (HTML)
                 └─ JsonResponse::success()/error()                     (API)
```

Puntos de entrada alternos: `public/login.php` y `public/consulta.php` son *shims*
que fijan `REQUEST_URI` y delegan en `index.php` (compatibilidad de URLs).

---

## 6. Capas y responsabilidades

### Core (`app/Core/`)
Infraestructura reutilizable:

- **Router** — registra rutas (`get/post/patch/delete`) y las despacha. Los
  parámetros `{id}` se compilan a regex con captura nombrada y llegan como
  argumentos al método del controlador.
- **Request** — agrupa entrada: `method()`, `path()` (quita el prefijo base),
  `input()`, `all()` (mezcla `$_GET`, `$_POST` y JSON), `file()`. **No valida.**
- **Response** — `redirect()`, `back()` (solo al mismo host: anti *open redirect*),
  `status()`.
- **JsonResponse** — respuestas JSON con forma fija `{success,message,data,errors}`.
- **Auth** — identidad y permisos (ver §8).
- **Session** — sesión por archivos con carpeta propia, cookie *remember-me* (ver §9).
- **Csrf / Middleware** — token CSRF y filtros previos al router.
- **Database** — conexión PDO única (singleton).
- **View** — renderiza `resources/views/<vista>.php` dentro de un layout.
- **Logger** — escribe JSON por línea en `storage/logs/app.log`.
- **Validator** — utilidades de validación.

### Controllers (`app/Controllers/`)
Delgados. Patrón fijo de cada acción:

```php
public function show(Request $request, string $id): void
{
    Auth::requirePermission('ordenes', 'ver');          // 1. acceso
    $orden = (new OrdenService())->obtener((int) $id);  // 2. delegar
    if (!$orden) { /* 404 */ }
    View::render('ordenes/show', ['orden' => $orden]);  // 3. responder
}
```

### Services (`app/Services/`)
Concentran **reglas de negocio y transacciones**. Ejemplo típico:

```php
$db = Database::connection();
$db->beginTransaction();
try {
    // ... varias escrituras coherentes ...
    $db->commit();
} catch (\Throwable $e) {
    $db->rollBack();
    throw $e;
}
```

Inyección de dependencias "de pobre": el constructor promovido usa valores por
defecto, así que se puede hacer `new OrdenService()` sin contenedor:

```php
public function __construct(
    private readonly OrdenRepository $ordenes = new OrdenRepository(),
    private readonly FolioService $folios = new FolioService(),
    private readonly AuditoriaService $auditoria = new AuditoriaService()
) {}
```

### Repositories (`app/Repositories/`)
Solo SQL. Extienden **BaseRepository**, que ofrece `fetch()`, `fetchAll()`,
`execute()` e `insert()` (devuelve `lastInsertId`). Toda variable externa viaja
como **parámetro PDO con nombre**, nunca concatenada.

### Helpers (`app/Helpers/`)
Funciones globales cargadas en el arranque:

- `money.php` — `formatearMoneda`, cálculos (`calcularSubtotal`, `calcularTotal`, `calcularSaldo`, IVA).
- `dates.php` — `fechaHumana`, `calcularDiasGarantia`.
- `strings.php` — `normalizarTelefono`, `slugSeguro`, `generarFolio`.
- `whatsapp.php` — `crearMensajeWhatsapp`, `linkWhatsapp`.
- `files.php` — `extensionPermitida`, `nombreArchivoSeguro`.
- `barcode.php` — `codigoBarras39Svg` (Code 39 como SVG).
- `security.php` — `e()` (escape HTML), `url()`, `asset()`, `absolute_url()`, `request_base_url()`, `csrf_field()`.
- `print.php` — `patronDesbloqueo()` (interpreta el patrón/clave del equipo) y `patronSvg()`.

**Validators / Policies / DTO / Models** completan el modelo, pero el sistema
trabaja mayormente con **arrays asociativos** que salen de los repositorios.

---

## 7. Enrutamiento

Las rutas se registran **a mano** en `public/index.php` (unas 72). El orden
importa: rutas exactas antes que las de parámetro.

```php
$router->get('/inventario', [InventarioController::class, 'index']);
$router->get('/inventario/create', [InventarioController::class, 'create']); // antes que {id}
$router->get('/inventario/{id}', [InventarioController::class, 'show']);
$router->post('/inventario/{id}/movimiento', [InventarioController::class, 'movimiento']);
```

Métodos soportados: GET, POST, PATCH, DELETE. En formularios HTML se puede simular
PUT/PATCH/DELETE con un campo `_method` (solo desde un POST real).

---

## 8. Autenticación, roles y permisos

- **Login** (`AuthService::attempt`): `password_verify` + regenerar ID de sesión.
  Con freno de fuerza bruta (5 intentos por email/IP en 15 min) y opción
  "No cerrar sesión".
- **Autorización** basada en base de datos:

```text
users ──< user_roles >── roles ──< role_permissions >── permissions(module, action)
```

- `Auth::requireLogin()` — exige sesión (redirige a `/login` o responde 401 en API).
- `Auth::requirePermission('modulo','accion')` — exige un permiso concreto; si
  falla, registra auditoría y responde 403 (o JSON en API).
- `Auth::can('modulo','accion')` — chequeo booleano.
- El rol **`superadmin`** tiene acceso total (bypass en la consulta de permisos).

Los módulos y acciones válidos se definen en `config/permissions.php`:

```php
'acciones' => ['ver','crear','editar','eliminar','autorizar','cambiar_estado','exportar','imprimir','administrar'],
'modulos'  => ['dashboard','clientes','equipos','ordenes', ... ,'usuarios','auditoria'],
```

Roles base: `superadmin`, `admin`, `recepcion`, `tecnico`, `tecnico_senior`,
`almacen`, `caja`, `cliente_consulta`. La asignación de permisos por rol vive en
`seed.sql`.

---

## 9. Sesiones

- Basadas en archivos, con **carpeta propia** `storage/sessions` (evita que la
  limpieza del `C:\xampp\tmp` compartido cierre sesiones).
- **Cierre por inactividad** configurable (`SESSION_IDLE_MINUTES`, 2 h por
  defecto), aplicado por `Middleware::enforceSession()` — no por el GC de PHP.
- **"No cerrar sesión"**: cookie persistente (`SESSION_REMEMBER_DAYS`, 30 días) y
  sin expiración por inactividad.
- Cookie `HttpOnly`, `SameSite=Lax`, `Secure` bajo HTTPS, `use_strict_mode`.

---

## 10. Seguridad

- **CSRF** en formularios y en la API (campo `_csrf` o header `X-CSRF-TOKEN`),
  validado por `Middleware::csrf()`.
- **Prepared statements** en todo acceso a datos.
- **Escape de salida** con `e()` en las vistas.
- **Cabeceras**: `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`,
  `Permissions-Policy`, HSTS en HTTPS; se elimina `X-Powered-By`.
- **Manejador global de excepciones**: registra el detalle en `storage/logs` y
  responde genérico salvo `APP_DEBUG=true`.
- **`.env` fuera del control de versiones**; `storage/` protegido por `.htaccess`.
- `X-Forwarded-*` solo se aceptan con `APP_TRUST_PROXY=true`.
- Auditoría de acciones críticas (tabla `auditoria`).

---

## 11. Configuración

- **`.env`** (parser propio en `bootstrap.php`, función `env_value()`):
  `APP_ENV`, `APP_DEBUG`, `APP_URL` (`auto` o dominio fijo), `APP_TRUST_PROXY`,
  `DB_*`, `SESSION_*`, `UPLOAD_MAX_MB`.
- **`config/app.php`** — expone la config de app leyendo `.env`.
- **`config/database.php`** — credenciales de la conexión PDO.
- **`config/permissions.php`** — catálogo de módulos y acciones.
- **Configuración editable en runtime**: tabla `configuraciones` (clave/valor por
  grupo), administrada desde el panel (`/configuracion`) y leída con
  `ConfiguracionService::get()` (datos del negocio, logo, plantillas de WhatsApp,
  texto de garantía, etc.).

---

## 12. Base de datos

28 tablas (definidas en `database/schema.sql`), agrupadas por dominio:

- **Acceso**: `users`, `roles`, `permissions`, `role_permissions`, `user_roles`,
  `sesiones`, `password_resets`.
- **Operación**: `clientes`, `equipos`, `ordenes_servicio`, `entregas`,
  `diagnosticos`, `cotizaciones`, `cotizacion_items`, `reparaciones`,
  `reparacion_avances`, `pagos`, `garantias`.
- **Almacén**: `proveedores`, `refacciones`, `refacciones_ordenes`,
  `inventario_movimientos`.
- **Soporte**: `configuraciones`, `archivos`, `mensajes`, `auditoria`,
  `notificaciones`, `agenda_eventos`.

Convenciones importantes:

- Borrado lógico con `deleted_at` en las tablas principales.
- **Prepared statements nativos** (`ATTR_EMULATE_PREPARES=false`): **no se puede
  reutilizar el mismo placeholder** dos veces en una consulta. Si necesitas el
  mismo valor en dos lugares, usa nombres distintos (`:id_a`, `:id_b`) y pásalo
  dos veces.

---

## 13. Vistas, layouts y temas (frontend)

- `View::render($vista, $datos, $layout)` extrae `$datos` como variables y ejecuta
  la vista dentro del layout.
- **Layouts** (`resources/views/layouts/`): `app` (panel autenticado), `guest`
  (login), `public` (portal del cliente), `print` (documentos imprimibles).
- **Temas**: se aplican con `data-theme` en `<html>` y variables CSS. Hay 5
  (Original, Crystal, Dark, Live, Blueprint neón). `theme-switcher.js` guarda la
  elección en `localStorage`. Para agregar un tema: nuevo archivo en
  `public/assets/css/themes/`, un `<link>` en el layout y una opción en el selector.
- **Iconos** vía atributo `data-icon` (glyph Unicode) renderizado por CSS.
- **JS** por página: la vista define `$pageScripts = [...]` y el layout los incluye.

---

## 14. URLs y assets

- `url('/ruta')` y `asset('css/app.css')` devuelven **rutas relativas a la raíz**
  (p. ej. `/tecnico/...`), que heredan esquema y host de la página → evitan
  *contenido mixto* en HTTPS y funcionan por IP/dominio.
- `absolute_url('/ruta')` devuelve URL **absoluta con host**; úsalo solo para
  enlaces que salen del navegador (mensaje de WhatsApp, portal público, QR).

---

## 15. Módulos funcionales

Autenticación · Usuarios/roles/permisos · Clientes · Equipos (con patrón/PIN de
desbloqueo) · Órdenes de servicio · Diagnósticos · Cotizaciones · Pagos/caja ·
Entregas (por clave/código de barras) · **Almacén** (refacciones, movimientos,
proveedores) · Garantías · Portal público de consulta · Notificaciones ·
Comunicación por WhatsApp · Dashboard/Reportes · Configuración · Auditoría ·
API JSON interna.

---

## 16. Notificaciones

- Tabla `notificaciones` (por usuario). `NotificacionService` crea avisos y los
  consulta; una campana en la barra superior muestra el contador y el menú.
- Los avisos se disparan desde los servicios del dominio (orden nueva → técnicos;
  cotización autorizada → técnico asignado; stock bajo → almacén). La creación de
  notificaciones **nunca interrumpe** la operación principal (va en try/catch).

---

## 17. Impresión y PDF

- **HTML imprimible** (`resources/views/print/` + `public/assets/css/print.css`):
  documentos de orden y de entrega en **carta / ticket 80 mm / 58 mm**; el layout
  de impresión fija `@page` según el formato. El patrón de desbloqueo se dibuja
  con `patronSvg()`.
- **PDF**: `OrdenPdfService` genera el PDF **en memoria** (sin librería) y se envía
  al navegador; **no se almacena**.

---

## 18. Migraciones y scripts (`database/`)

- `install.php` / `seed.php` — instalación e inicialización.
- `check.php`, `*_check.php` — verificaciones de estado.
- `upgrade_*.php` — **migraciones incrementales**, idempotentes, que se corren a
  mano en instalaciones existentes (claves de entrega, config de tickets,
  plantillas de WhatsApp, notificaciones, permisos de almacén, etc.). El listado
  y el orden recomendado están en el [CHANGELOG](../CHANGELOG.md).

Al agregar una tabla o columna: actualiza `schema.sql` (instalación nueva) **y**
crea un `upgrade_*.php` idempotente (instalación existente).

---

## 19. Convenciones y trampas a evitar

- **No reutilices un placeholder PDO** en la misma consulta (ver §12).
- **Default de `estatus`/enums**: `in_array(($data['x'] ?? 'activo'), [...], true) ? $data['x'] : 'activo'`
  es un bug (la rama verdadera devuelve el valor sin el `??`, que puede ser null).
  Resuélvelo con una variable: `$x = $data['x'] ?? 'activo';` y úsala.
- **Escapa siempre** la salida con `e()` en las vistas.
- **Transacciones** para operaciones con varias escrituras; `rollBack()` en `catch`.
- **Permisos primero**: toda acción de controlador arranca con
  `Auth::requirePermission()` (o `requireLogin()` para vistas generales).
- **Auditoría**: registra acciones críticas con `AuditoriaService::registrar()`.
- Enlaces internos con `url()`/`asset()`; externos con `absolute_url()`.

---

## 20. Cómo agregar un módulo nuevo (receta)

1. **Base de datos**: agrega la tabla en `schema.sql` y crea `database/upgrade_<modulo>.php` idempotente.
2. **Permiso**: el módulo debe existir en `config/permissions.php` (`modulos`);
   asigna permisos a los roles en `seed.sql` + una migración.
3. **Repository**: `app/Repositories/<Modulo>Repository.php` extendiendo `BaseRepository` (solo SQL).
4. **Service**: `app/Services/<Modulo>Service.php` con reglas y transacciones.
5. **Controller**: `app/Controllers/<Modulo>Controller.php`; cada acción empieza con `Auth::requirePermission()`.
6. **Vistas**: `resources/views/<modulo>/` (index, form, show...) usando `e()` y `csrf_field()`.
7. **Rutas**: regístralas en `public/index.php` (exactas antes que `{id}`).
8. **Menú**: agrega el enlace en `resources/views/layouts/app.php`.
9. **Verifica**: `php -l` en cada archivo y una prueba de extremo a extremo (login → acción → BD).

---

## 21. Pruebas

```bash
php tests/run.php
```

Runner propio que cubre **funciones puras** (cálculos de dinero, folios, teléfono,
WhatsApp, garantía). No hay pruebas de integración automatizadas; la verificación
de flujos se hace manualmente contra la app.

---

## 22. Notas de despliegue

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tudominio.com`.
- Usuario de MySQL propio (no `root`), contraseñas cambiadas, HTTPS forzado.
- Apuntar el *document root* a `public/` en hosting dedicado (en XAMPP el
  `.htaccess` raíz ya enruta hacia `public/`).
- Permisos de escritura en `storage/` (`uploads`, `logs`, `backups`, `sessions`).
- Reemplazar el CDN de Bootstrap por assets locales si se requiere operación sin
  internet.
