<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\ConfiguracionService;
use App\Services\EntregaService;

final class EntregaController
{
    public function index(Request $request): void
    {
        Auth::requireLogin();

        // Cualquier usuario autenticado puede abrir esta pantalla. El codigo
        // llega desde teclado, lector de barras o camara; solo se usa para
        // buscar la orden candidata, no para entregarla automaticamente.
        $codigo = strtoupper(trim((string) $request->input('codigo', '')));
        $service = new EntregaService();
        $orden = $codigo ? $service->buscar($codigo) : null;

        // Si la orden ya se entrego, se ubica su ultima entrega para poder
        // reimprimir el comprobante/ticket desde este mismo modulo.
        $entregaId = null;
        if ($orden && ($orden['estado'] ?? '') === 'entregada') {
            $ultima = $service->ultimaPorOrden((int) $orden['id']);
            $entregaId = $ultima['id'] ?? null;
        }

        View::render('entregas/index', [
            'title' => 'Entrega de equipo',
            'codigo' => $codigo,
            'orden' => $orden,
            'entregaId' => $entregaId,
        ]);
    }

    public function buscar(Request $request): void
    {
        Auth::requireLogin();

        // El POST de busqueda se convierte a GET para que la recepcion/caja
        // pueda recargar o compartir la pantalla sin repetir acciones.
        $codigo = strtoupper(trim((string) $request->input('codigo_entrega', '')));
        Response::redirect('/entregas' . ($codigo !== '' ? '?codigo=' . urlencode($codigo) : ''));
    }

    public function entregar(Request $request): void
    {
        Auth::requireLogin();

        // La entrega si modifica datos: el servicio valida codigo, saldo,
        // pagos finales y registra quien libero el equipo en auditoria.
        try {
            $entregaId = (new EntregaService())->entregar($request->all());
            Session::flash('success', 'Equipo entregado correctamente.');
            Response::redirect('/entregas/' . $entregaId . '/comprobante');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::redirect('/entregas?codigo=' . urlencode((string) $request->input('codigo_entrega', '')));
        }
    }

    public function comprobante(Request $request, string $id): void
    {
        Auth::requireLogin();

        // El comprobante se consulta por id de entrega y sale en layout de
        // impresion. Sirve como prueba operativa de quien entrego y cuando.
        $entrega = (new EntregaService())->comprobante((int) $id);
        if (!$entrega) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Entrega no encontrada']);
            return;
        }

        $formato = (string) $request->input('formato', 'carta');
        if (!in_array($formato, ['carta', '80', '56'], true)) {
            $formato = 'carta';
        }

        $cfg = new ConfiguracionService();
        $config = [
            'negocio.nombre' => $cfg->get('negocio.nombre', 'Servicio Tecnico'),
            'negocio.telefono' => $cfg->get('negocio.telefono', ''),
            'negocio.whatsapp' => $cfg->get('negocio.whatsapp', ''),
            'negocio.direccion' => $cfg->get('negocio.direccion', ''),
            'negocio.logo_url' => $cfg->get('negocio.logo_url', ''),
            'ticket.garantia' => $cfg->get('ticket.garantia', ''),
            'legal.politica_garantia' => $cfg->get('legal.politica_garantia', ''),
        ];

        View::render('print/entrega', [
            'title' => 'Comprobante de entrega',
            'entrega' => $entrega,
            'formato' => $formato,
            'config' => $config,
        ], 'layouts/print');
    }
}
