<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Core\Database;
use App\Services\CotizacionService;
use App\Services\ConfiguracionService;
use App\Services\DiagnosticoService;
use App\Services\OrdenService;
use App\Services\OrdenPdfService;

final class PublicController
{
    public function consulta(Request $request, ?string $folio = null, ?string $token = null): void
    {
        // Ruta publica: acepta folio/token desde URL amigable o query string.
        // El servicio debe filtrar por ambos datos para evitar que alguien vea
        // una orden solo adivinando el folio.
        $folio = $folio ?: (string) $request->input('folio', '');
        $token = $token ?: (string) $request->input('token', '');
        $orden = $folio && $token ? (new OrdenService())->portal($folio, $token) : null;

        // Esta vista solo debe recibir datos visibles para cliente. No se
        // mandan usuarios internos, costos internos ni notas privadas.
        View::render('public/consulta', [
            'title' => 'Consulta de orden',
            'orden' => $orden,
            'folio' => $folio,
            'token' => $token,
            'diagnostico' => $orden ? (new DiagnosticoService())->obtenerPorOrden((int) $orden['id']) : null,
            'cotizacion' => $orden ? (new CotizacionService())->obtenerPorOrden((int) $orden['id']) : null,
            'garantia' => $orden ? $this->garantiaPublica((int) $orden['id']) : null,
        ], 'layouts/public');
    }

    public function cotizacion(Request $request, string $folio, string $token, string $id): void
    {
        // Accion publica de aceptar/rechazar cotizacion. Primero se valida el
        // token de la orden y despues que la cotizacion pertenezca a esa orden.
        $orden = (new OrdenService())->portal($folio, $token);
        if (!$orden) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Orden no encontrada'], 'layouts/public');
            return;
        }

        try {
            $cotizacion = (new CotizacionService())->obtenerPorOrden((int) $orden['id']);
            if (!$cotizacion || (int) $cotizacion['id'] !== (int) $id) {
                throw new \RuntimeException('La cotizacion no pertenece a esta orden.');
            }

            // El estado/motivo vienen del cliente; el servicio registra la
            // respuesta y conserva el historial de la cotizacion.
            (new CotizacionService())->autorizar((int) $id, (string) $request->input('estado'), (string) $request->input('motivo'));
            Session::flash('success', 'Gracias, registramos tu respuesta.');
        } catch (\Throwable $exception) {
            Session::flash('error', $exception->getMessage());
        }
        Response::redirect('/consulta/' . urlencode($folio) . '/' . urlencode($token));
    }

    public function pdf(Request $request, string $folio, string $token): void
    {
        // PDF publico protegido por folio + token. No requiere login, pero no
        // expone notas internas ni informacion de otros clientes.
        $orden = (new OrdenService())->portal($folio, $token);
        if (!$orden) {
            Response::status(404);
            View::render('errors/404', ['title' => 'Orden no encontrada'], 'layouts/public');
            return;
        }

        $diagnostico = (new DiagnosticoService())->obtenerPorOrden((int) $orden['id']);
        $cotizacion = (new CotizacionService())->obtenerPorOrden((int) $orden['id']);
        $pdf = (new OrdenPdfService())->recepcion($orden, $diagnostico, $cotizacion, $this->printConfig());
        $filename = 'orden-' . preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) $orden['folio']) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        echo $pdf;
    }

    private function printConfig(): array
    {
        $cfg = new ConfiguracionService();
        return [
            'negocio.nombre' => $cfg->get('negocio.nombre', 'Servicio Tecnico'),
            'negocio.telefono' => $cfg->get('negocio.telefono', ''),
            'negocio.whatsapp' => $cfg->get('negocio.whatsapp', ''),
            'negocio.direccion' => $cfg->get('negocio.direccion', ''),
            'negocio.logo_url' => $cfg->get('negocio.logo_url', ''),
            'ticket.garantia' => $cfg->get('ticket.garantia', ''),
            'legal.politica_garantia' => $cfg->get('legal.politica_garantia', ''),
        ];
    }

    private function garantiaPublica(int $ordenId): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT fecha_inicio, fecha_fin, condiciones, estado
             FROM garantias
             WHERE orden_id = :orden_id
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute(['orden_id' => $ordenId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
