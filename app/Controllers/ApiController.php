<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Services\ClienteService;
use App\Services\CotizacionService;
use App\Services\InventarioService;
use App\Services\OrdenService;
use App\Services\PagoService;
use App\Services\ReporteService;

final class ApiController
{
    public function clientes(Request $request): void
    {
        Auth::requirePermission('clientes', 'ver');
        JsonResponse::success('Clientes encontrados', (new ClienteService())->listar((string) $request->input('q', '')));
    }

    public function crearCliente(Request $request): void
    {
        Auth::requirePermission('clientes', 'crear');
        $id = (new ClienteService())->guardar($request->all());
        JsonResponse::success('Cliente creado correctamente', ['id' => $id]);
    }

    public function ordenes(Request $request): void
    {
        Auth::requirePermission('ordenes', 'ver');
        JsonResponse::success('Ordenes encontradas', (new OrdenService())->listar($request->all()));
    }

    public function crearOrden(Request $request): void
    {
        Auth::requirePermission('ordenes', 'crear');
        $id = (new OrdenService())->crear($request->all());
        JsonResponse::success('Orden creada correctamente', ['id' => $id]);
    }

    public function orden(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'ver');
        JsonResponse::success('Orden encontrada', (new OrdenService())->obtener((int) $id));
    }

    public function estadoOrden(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'cambiar_estado');
        (new OrdenService())->cambiarEstado((int) $id, (string) $request->input('estado'), Auth::can('ordenes', 'autorizar'));
        JsonResponse::success('Estado actualizado');
    }

    public function crearCotizacion(Request $request): void
    {
        Auth::requirePermission('cotizaciones', 'crear');
        $id = (new CotizacionService())->crear($request->all());
        JsonResponse::success('Cotizacion creada correctamente', ['id' => $id]);
    }

    public function crearPago(Request $request): void
    {
        Auth::requirePermission('pagos', 'crear');
        $id = (new PagoService())->registrar($request->all());
        JsonResponse::success('Pago registrado correctamente', ['id' => $id]);
    }

    public function dashboard(): void
    {
        Auth::requirePermission('dashboard', 'ver');
        JsonResponse::success('Dashboard', (new ReporteService())->dashboard());
    }

    public function stockBajo(): void
    {
        Auth::requirePermission('inventario', 'ver');
        JsonResponse::success('Stock bajo', (new InventarioService())->stockBajo());
    }
}
