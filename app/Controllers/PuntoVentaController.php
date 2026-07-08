<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\ConfiguracionService;
use App\Services\InventarioService;
use App\Services\VentaRefaccionService;
use App\Validators\VentaRefaccionValidator;

final class PuntoVentaController
{
    public function index(): void
    {
        Auth::requirePermission('punto_venta', 'ver');
        $ticketId = isset($_GET['ticket']) && ctype_digit((string) $_GET['ticket']) ? (int) $_GET['ticket'] : 0;
        View::render('punto_venta/index', [
            'title' => 'Punto de venta',
            'refacciones' => array_values(array_filter(
                (new InventarioService())->listar(),
                static fn (array $refaccion): bool => $refaccion['estatus'] === 'activo'
            )),
            'ventas' => (new VentaRefaccionService())->recientes(),
            'ticketUrl' => $ticketId > 0 ? url('/punto-venta/' . $ticketId . '/ticket') : '',
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('punto_venta', 'crear');
        $errors = VentaRefaccionValidator::validate($request->all());
        if ($errors) {
            Session::flash('error', $errors[0]['message']);
            Response::back();
        }

        try {
            $ventaId = (new VentaRefaccionService())->vender($request->all());
            Session::flash('success', 'Venta registrada y stock descontado.');
            Response::redirect('/punto-venta?ticket=' . $ventaId);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function buscar(Request $request): void
    {
        Auth::requirePermission('punto_venta', 'ver');
        $query = trim((string) $request->input('q', ''));
        if ($query === '') {
            JsonResponse::success('Busqueda vacia', ['items' => [], 'exact' => false]);
        }

        $items = (new InventarioService())->buscarParaVenta($query);
        $exacto = null;
        foreach ($items as $item) {
            if ((int) ($item['exact_sku'] ?? 0) === 1) {
                $exacto = $item;
                break;
            }
        }
        if ($exacto !== null) {
            JsonResponse::success('SKU encontrado', ['items' => [$exacto], 'exact' => true]);
        }

        JsonResponse::success('Resultados', [
            'items' => $items,
            'exact' => false,
        ]);
    }

    public function ticket(Request $request, string $id): void
    {
        Auth::requirePermission('punto_venta', 'imprimir');
        $venta = (new VentaRefaccionService())->obtener((int) $id);
        if (!$venta) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Venta no encontrada']);
            return;
        }

        $cfg = new ConfiguracionService();
        View::render('punto_venta/ticket', [
            'title' => 'Ticket ' . $venta['folio'],
            'venta' => $venta,
            'negocio' => [
                'nombre' => $cfg->get('negocio.nombre', $cfg->get('sistema.nombre', 'Servicio Tecnico')),
                'telefono' => $cfg->get('negocio.telefono', ''),
                'whatsapp' => $cfg->get('negocio.whatsapp', ''),
                'email' => $cfg->get('negocio.email', ''),
                'direccion' => $cfg->get('negocio.direccion', ''),
            ],
        ], '');
    }
}
