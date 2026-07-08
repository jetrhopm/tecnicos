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
use App\Services\AuditoriaService;
use App\Services\ClienteService;
use App\Services\ConfiguracionService;
use App\Services\CotizacionService;
use App\Services\EntregaService;
use App\Services\EvidenciaService;
use App\Services\DiagnosticoService;
use App\Services\EquipoService;
use App\Services\MensajeService;
use App\Services\OrdenService;
use App\Services\OrdenPdfService;
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
            'whatsappMensajes' => (function () use ($orden) {
                // Mensajes de WhatsApp segun el momento de la orden; el usuario
                // elige cual enviar desde el menu de la ficha.
                $m = new MensajeService();
                return [
                    'recibido' => $m->whatsappOrden($orden, 'whatsapp.orden_recibida'),
                    'cotizacion' => $m->whatsappOrden($orden, 'whatsapp.diagnostico_listo'),
                    'listo' => $m->whatsappOrden($orden, 'whatsapp.equipo_listo'),
                ];
            })(),
            'tecnicos' => (new UserRepository())->activeTechnicians(),
            'evidencias' => (new EvidenciaService())->listar((int) $id),
            'bitacora' => (new AuditoriaService())->historial('ordenes', (int) $id),
            'entrega' => (new EntregaService())->ultimaPorOrden((int) $id),
        ]);
    }

    public function subirEvidencia(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'editar');

        // Guarda la foto del ticket firmado como evidencia y, si se marco,
        // registra que el cliente acepto presupuesto y terminos.
        try {
            (new EvidenciaService())->subir(
                (int) $id,
                $request->file('evidencia'),
                (bool) $request->input('acepta_terminos'),
                trim((string) $request->input('nota_evidencia', ''))
            );
            Session::flash('success', 'Evidencia guardada.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/ordenes/' . $id);
    }

    public function verEvidencia(Request $request, string $id, string $archivo): void
    {
        Auth::requirePermission('ordenes', 'ver');

        // Sirve la evidencia guardada en storage (fuera del webroot) validando
        // que pertenezca a la orden y con la sesion autenticada.
        $registro = (new EvidenciaService())->archivoDeOrden((int) $id, (int) $archivo);
        if (!$registro) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Evidencia no encontrada']);
            return;
        }

        $ruta = BASE_PATH . '/storage/uploads/' . $registro['ruta'];
        if (!is_file($ruta)) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Archivo no encontrado']);
            return;
        }

        header('Content-Type: ' . $registro['mime']);
        header('Content-Disposition: inline; filename="' . $registro['nombre_original'] . '"');
        header('Content-Length: ' . (string) filesize($ruta));
        header('X-Content-Type-Options: nosniff');
        readfile($ruta);
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

        // Salida HTML preparada para impresion. Formato carta o termico 80/58mm
        // segun ?formato=; los datos del negocio/logo salen de configuracion.
        $orden = (new OrdenService())->obtener((int) $id);
        if (!$orden) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Orden no encontrada']);
            return;
        }

        $formato = (string) $request->input('formato', 'carta');
        if (!in_array($formato, ['carta', '80', '58'], true)) {
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

        View::render('print/recepcion', [
            'title' => 'Orden ' . ($orden['folio'] ?? ''),
            'orden' => $orden,
            'formato' => $formato,
            'config' => $config,
        ], 'layouts/print');
    }

    public function pdf(Request $request, string $id): void
    {
        Auth::requirePermission('ordenes', 'imprimir');

        $orden = (new OrdenService())->obtener((int) $id);
        if (!$orden) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Orden no encontrada']);
            return;
        }

        $diagnostico = (new DiagnosticoService())->obtenerPorOrden((int) $id);
        $cotizacion = (new CotizacionService())->obtenerPorOrden((int) $id);
        $pdf = (new OrdenPdfService())->recepcion($orden, $diagnostico, $cotizacion);
        $filename = 'orden-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $orden['folio']) . '.pdf';

        // El PDF no se guarda; solo se deja constancia en la bitacora de que se genero.
        (new AuditoriaService())->registrar('pdf_generado', 'ordenes', (int) $id);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }
}
