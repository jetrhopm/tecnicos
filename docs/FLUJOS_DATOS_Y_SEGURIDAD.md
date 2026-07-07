# Flujos de datos y seguridad

Este documento explica de forma practica de donde toma informacion el sistema,
que capa la procesa y hacia donde la envia. Tambien deja criterios para
comentar el codigo sin exponer detalles que faciliten ataques.

## Es buena idea comentar todo el codigo?

No conviene comentar cada linea. Es mejor comentar las fronteras importantes:
entrada de datos, permisos, CSRF, consultas a base de datos, archivos subidos,
salidas publicas, pagos, entregas y auditoria.

Comentarios utiles:

- Explican por que existe una validacion o regla de negocio.
- Indican que datos llegan desde el navegador y que servicio los procesa.
- Avisan que una vista publica no debe recibir notas internas o costos privados.
- Documentan decisiones de seguridad que no son obvias.

Comentarios peligrosos:

- Credenciales, tokens, claves privadas o rutas secretas.
- Instrucciones demasiado exactas para saltar controles.
- Detalles de configuracion del servidor que no necesita conocer un tercero.
- Comentarios obsoletos que contradicen el codigo real.

Si el repositorio sera publico en GitHub, los comentarios deben ayudar a
mantener el sistema, no revelar informacion sensible del negocio.

## Flujo general

```text
Navegador / lector / formulario
        -> App\Core\Request
        -> Controller
        -> Service
        -> Repository
        -> MySQL por PDO
        -> View HTML o JsonResponse
```

- `Request` agrupa datos de `GET`, `POST` y `FILES`.
- `Controller` valida sesion/permisos y decide si responde vista o JSON.
- `Service` aplica reglas de negocio y transacciones.
- `Repository` ejecuta SQL con PDO y prepared statements.
- `View` renderiza HTML y debe escapar salidas con `e()`.
- `JsonResponse` entrega respuestas estandar para API.

## Flujos principales

| Proceso | Entrada | Capa que decide | Destino | Riesgo principal | Control aplicado |
| --- | --- | --- | --- | --- | --- |
| Login | Email y password | `AuthService` | Sesion PHP | Robo de cuenta | `password_verify`, regenerar sesion, auditoria |
| Alta rapida de orden | Cliente/equipo/falla/anticipo | `OrdenService::crearRapida` | `clientes`, `equipos`, `ordenes_servicio`, `pagos` | Datos incompletos o duplicados | Validacion, transaccion, folio/token/codigo |
| Busqueda de ordenes | Texto de busqueda | `OrdenRepository::all` | Listado HTML | Error SQL o enumeracion | Placeholders unicos, permisos de vista |
| Diagnostico | Datos tecnicos | `DiagnosticoService` | `diagnosticos` | Ver notas internas al cliente | Separar campos internos y visibles |
| Cotizacion publica | Folio, token, estado | `PublicController`, `CotizacionService` | `cotizaciones` | Autorizar orden ajena | Validar folio + token + id de cotizacion |
| Pago | Monto, metodo, referencia | `PagoService` | `pagos`, saldo de orden | Borrar/cambiar dinero sin rastro | No borrar pagos, cancelar con motivo, auditoria |
| Entrega | Codigo de barras/clave | `EntregaService` | `entregas`, orden, garantia | Entregar equipo equivocado | Codigo de entrega, saldo, usuario autenticado |
| Portal cliente | Folio y token | `OrdenService::portal` | Vista publica | Exponer datos privados | Solo campos visibles para cliente |
| WhatsApp manual | Plantilla + datos de orden | `MensajeService` | Link `wa.me` | Enviar datos de mas | Plantillas controladas, envio manual |
| Busqueda en tablas | Texto local | `public/assets/js/app.js` | Solo navegador | Creer que filtra permisos | Backend sigue validando permisos |
| Escaner camara | Codigo leido | `entregas-scanner.js` | Formulario de busqueda | Confiar en el codigo del cliente | PHP valida codigo y sesion |

## Notas por seguridad

### Entradas

Todo dato que llega desde navegador, lector de barras, camara, URL o API debe
considerarse no confiable. El JavaScript solo mejora la experiencia; no debe ser
la unica defensa.

### Base de datos

Las consultas deben pasar por repositorios y usar prepared statements. Evitar
concatenar valores recibidos del usuario dentro del SQL.

### Sesion y permisos

Las pantallas administrativas deben llamar `Auth::requireLogin()` o
`Auth::requirePermission()`. Los permisos se revisan por accion, no solo por
menu visible.

### Vistas HTML

Las vistas deben escapar textos con `e()` antes de imprimir datos de clientes,
ordenes, diagnosticos o notas. Esto reduce riesgo de XSS.

### Portal publico

El portal de cliente nunca debe recibir:

- Notas internas.
- Costos internos.
- Usuarios internos.
- Datos de otros clientes.
- Bitacora privada.

El token publico debe tratarse como una llave de consulta. Si se comparte, quien
lo tenga puede ver la vista publica de esa orden.

### Codigos de barras y QR

El codigo de barras interno no debe contener datos sensibles. Debe contener solo
una llave o folio operativo. El sistema busca la orden y decide si se puede
mostrar o entregar.

El QR del cliente puede abrir el portal publico con folio y token. No debe abrir
paneles internos ni permitir acciones administrativas.

### Archivos

Los archivos subidos deben validarse por tamano, extension y MIME. No deben
ejecutarse desde `storage`. Si se publican evidencias al cliente, solo deben ser
las marcadas como visibles.

### Produccion

Antes de subir a Hostinger o cualquier hosting:

- Usar HTTPS.
- Cambiar todas las credenciales iniciales.
- Configurar `.env` fuera del repositorio.
- Desactivar errores detallados en pantalla.
- Revisar permisos de escritura de `storage`.
- Mantener backups de base de datos.
- Usar contrasenas fuertes por usuario.
- Revisar logs y auditoria.

## Recomendacion final

Si el sistema sera publico en GitHub, documentar flujos y reglas es buena idea.
Lo que no es buena idea es llenar el codigo con comentarios repetitivos o incluir
secretos. La mejor defensa es una combinacion de codigo claro, comentarios en
puntos criticos, README, auditoria, validaciones del backend y configuracion de
produccion segura.
