<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\UserRepository;
use App\Services\ClienteService;
use App\Services\CotizacionService;
use App\Services\DiagnosticoService;
use App\Services\EquipoService;
use App\Services\MensajeService;
use App\Services\OrdenService;
use App\Services\PagoService;
use App\Validators\OrdenValidator;

final class OrdenController
{
    public function index(Request $request): void
    {
        Auth::requirePermission('ordenes', 'ver');

        // La busqueda llega desde el formulario/listado. Primero intentamos una
        // coincidencia exacta de folio/codigo para mandar al usuario directo a
        // la ficha; si no existe, se mantiene el listado filtrado.
        $q = trim((string) $request->input('q', ''));
        if ($q !== '') {
            $exactId = (new OrdenService())->buscarIdExacto($q);
            if ($exactId) {
                Response::redirect('/ordenes/' . $exactId);
            }
        }

        // El controlador no arma SQL: entrega los filtros al servicio y envia
        // los resultados limpios a la vista HTML.
        View::render('ordenes/index', [
            'title' => 'Ordenes',
            'ordenes' => (new OrdenService())->listar($request->all()),
            'estados' => OrdenService::ESTADOS,
        ]);
    }

    public function create(): void
    {
        Auth::requirePermission('ordenes', 'crear');

        // La vista de alta rapida necesita catalogos para elegir registros
        // existentes; si el usuario no selecciona uno, el servicio creara
        // cliente/equipo dentro de una transaccion.
        View::render('ordenes/form', [
            'title' => 'Nueva orden',
            'clientes' => (new ClienteService())->listar(),
            'equipos' => (new EquipoService())->listar(),
            'tecnicos' => (new UserRepository())->activeTechnicians(),
        ]);
    }

    public function store(Request $request): void
    {
        Auth::requirePermission('ordenes', 'crear');

        // Endpoint JSON interno: valida la entrada antes de enviarla al
        // servicio. La respuesta mantiene el formato estandar de la API.
        $errors = OrdenValidator::validate($request->all());
        if ($errors) {
            JsonResponse::error('Datos invalidos', $errors);
        }
        $id = (new OrdenService())->crear($request->all());
        JsonResponse::success('Orden creada correctamente', ['id' => $id]);
    }

    public function storeWeb(Request $request): void
    {
        Auth::requirePermission('ordenes', 'crear');

        // Alta desde formulario web. Los datos viajan al servicio de ordenes,
        // donde se generan folio/token/codigo y se guardan cliente, equipo,
        // orden y anticipo si aplica.
        try {
            $id = (new OrdenService())->crearRapida($request->all());
            Session::flash('success', 'Orden creada.');
            Response::redirect('/ordenes/' . $id);
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
            Response::back();
        }
    }

    public function show(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'ver');

        // La ficha de orden junta informacion relacionada para una sola vista:
        // diagnostico, cotizacion, pagos, WhatsApp y tecnicos. Las notas
        // internas solo se muestran dentro del panel autenticado.
        $orden = (new OrdenService())->obtener((int) $id);
        if (!$orden) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Orden no encontrada']);
            return;
        }

        View::render('ordenes/show', [
            'title' => 'Orden ' . $orden['folio'],
            'orden' => $orden,
            'estados' => OrdenService::ESTADOS,
            'diagnostico' => (new DiagnosticoService())->obtenerPorOrden((int) $id),
            'cotizacion' => (new CotizacionService())->obtenerPorOrden((int) $id),
            'pagos' => (new PagoService())->listarOrden((int) $id),
            'whatsapp' => (new MensajeService())->whatsappOrden($orden),
            'tecnicos' => (new UserRepository())->activeTechnicians(),
        ]);
    }

    public function cambiarEstado(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'cambiar_estado');

        // El nuevo estado viene del formulario. El servicio aplica reglas de
        // negocio y registra auditoria; el permiso "autorizar" habilita saltos
        // especiales cuando el rol lo permite.
        try {
            (new OrdenService())->cambiarEstado((int) $id, (string) $request->input('estado'), Auth::can('ordenes', 'autorizar'));
            Session::flash('success', 'Estado actualizado.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/ordenes/' . $id);
    }

    public function asignarTecnico(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'editar');

        // El tecnico asignado se guarda en la orden para agenda/carga de
        // trabajo; se permite null cuando se quiere dejar sin asignar.
        (new OrdenService())->asignarTecnico((int) $id, $request->input('tecnico_id') ? (int) $request->input('tecnico_id') : null);
        Session::flash('success', 'Tecnico asignado.');
        Response::redirect('/ordenes/' . $id);
    }

    public function imprimir(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'imprimir');

        // Salida HTML preparada para impresion. La informacion se obtiene por
        // id interno y pasa a un layout separado para no mezclar comprobantes
        // con la navegacion del panel.
        $orden = (new OrdenService())->obtener((int) $id);
        View::render('print/recepcion', ['title' => 'Comprobante', 'orden' => $orden], 'layouts/print');
    }
}
