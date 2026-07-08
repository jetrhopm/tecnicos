<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\ApiController;
use App\Controllers\AuthController;
use App\Controllers\ClienteController;
use App\Controllers\ConfiguracionController;
use App\Controllers\CotizacionController;
use App\Controllers\DashboardController;
use App\Controllers\DiagnosticoController;
use App\Controllers\EquipoController;
use App\Controllers\EntregaController;
use App\Controllers\GarantiaController;
use App\Controllers\InventarioController;
use App\Controllers\NotificacionController;
use App\Controllers\OrdenController;
use App\Controllers\ProveedorController;
use App\Controllers\PagoController;
use App\Controllers\PublicController;
use App\Controllers\ReporteController;
use App\Controllers\UsuarioController;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Router;

$request = new Request();
Middleware::securityHeaders();
Middleware::enforceSession();
Middleware::csrf($request);

$router = new Router();

$router->get('/', [DashboardController::class, 'index']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/clientes', [ClienteController::class, 'index']);
$router->get('/clientes/create', [ClienteController::class, 'create']);
$router->post('/clientes', [ClienteController::class, 'storeWeb']);
$router->get('/clientes/{id}', [ClienteController::class, 'show']);
$router->get('/clientes/{id}/edit', [ClienteController::class, 'edit']);
$router->post('/clientes/{id}', [ClienteController::class, 'update']);

$router->get('/equipos', [EquipoController::class, 'index']);
$router->get('/equipos/create', [EquipoController::class, 'create']);
$router->post('/equipos', [EquipoController::class, 'storeWeb']);
$router->get('/equipos/{id}', [EquipoController::class, 'show']);
$router->get('/equipos/{id}/edit', [EquipoController::class, 'edit']);
$router->post('/equipos/{id}', [EquipoController::class, 'update']);

$router->get('/ordenes', [OrdenController::class, 'index']);
$router->get('/ordenes/create', [OrdenController::class, 'create']);
$router->post('/ordenes', [OrdenController::class, 'storeWeb']);
$router->get('/ordenes/{id}', [OrdenController::class, 'show']);
$router->post('/ordenes/{id}/estado', [OrdenController::class, 'cambiarEstado']);
$router->post('/ordenes/{id}/tecnico', [OrdenController::class, 'asignarTecnico']);
$router->get('/ordenes/{id}/imprimir', [OrdenController::class, 'imprimir']);
$router->get('/ordenes/{id}/pdf', [OrdenController::class, 'pdf']);
$router->post('/ordenes/{id}/evidencia', [OrdenController::class, 'subirEvidencia']);
$router->get('/ordenes/{id}/evidencia/{archivo}', [OrdenController::class, 'verEvidencia']);

$router->post('/diagnosticos', [DiagnosticoController::class, 'store']);
$router->post('/cotizaciones', [CotizacionController::class, 'store']);
$router->post('/cotizaciones/{id}/autorizar', [CotizacionController::class, 'autorizar']);
$router->post('/pagos', [PagoController::class, 'store']);

$router->get('/entregas', [EntregaController::class, 'index']);
$router->post('/entregas/buscar', [EntregaController::class, 'buscar']);
$router->post('/entregas/entregar', [EntregaController::class, 'entregar']);
$router->get('/entregas/{id}/comprobante', [EntregaController::class, 'comprobante']);

$router->get('/notificaciones', [NotificacionController::class, 'index']);
$router->post('/notificaciones/leer-todas', [NotificacionController::class, 'leerTodas']);
$router->get('/notificaciones/{id}', [NotificacionController::class, 'abrir']);

$router->get('/inventario', [InventarioController::class, 'index']);
$router->get('/inventario/create', [InventarioController::class, 'create']);
$router->post('/inventario', [InventarioController::class, 'store']);
$router->get('/inventario/{id}', [InventarioController::class, 'show']);
$router->get('/inventario/{id}/edit', [InventarioController::class, 'edit']);
$router->post('/inventario/{id}', [InventarioController::class, 'update']);
$router->post('/inventario/{id}/movimiento', [InventarioController::class, 'movimiento']);

$router->get('/proveedores', [ProveedorController::class, 'index']);
$router->get('/proveedores/create', [ProveedorController::class, 'create']);
$router->post('/proveedores', [ProveedorController::class, 'store']);
$router->get('/proveedores/{id}/edit', [ProveedorController::class, 'edit']);
$router->post('/proveedores/{id}', [ProveedorController::class, 'update']);

$router->get('/garantias', [GarantiaController::class, 'index']);
$router->get('/reportes', [ReporteController::class, 'index']);
$router->get('/configuracion', [ConfiguracionController::class, 'index']);
$router->post('/configuracion', [ConfiguracionController::class, 'update']);
$router->get('/usuarios', [UsuarioController::class, 'index']);
$router->get('/usuarios/create', [UsuarioController::class, 'create']);
$router->post('/usuarios', [UsuarioController::class, 'store']);

$router->get('/consulta', [PublicController::class, 'consulta']);
$router->get('/consulta.php', [PublicController::class, 'consulta']);
$router->get('/consulta/{folio}/{token}', [PublicController::class, 'consulta']);
$router->get('/consulta/{folio}/{token}/pdf', [PublicController::class, 'pdf']);
$router->post('/consulta/{folio}/{token}/cotizacion/{id}', [PublicController::class, 'cotizacion']);

$router->get('/api/clientes', [ApiController::class, 'clientes']);
$router->post('/api/clientes', [ApiController::class, 'crearCliente']);
$router->get('/api/ordenes', [ApiController::class, 'ordenes']);
$router->post('/api/ordenes', [ApiController::class, 'crearOrden']);
$router->get('/api/ordenes/{id}', [ApiController::class, 'orden']);
$router->patch('/api/ordenes/{id}/estado', [ApiController::class, 'estadoOrden']);
$router->post('/api/cotizaciones', [ApiController::class, 'crearCotizacion']);
$router->post('/api/pagos', [ApiController::class, 'crearPago']);
$router->get('/api/reportes/dashboard', [ApiController::class, 'dashboard']);
$router->get('/api/inventario/stock-bajo', [ApiController::class, 'stockBajo']);

$router->dispatch($request);
