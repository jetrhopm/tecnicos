<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\ClienteRepository;
use App\Repositories\EquipoRepository;
use App\Repositories\OrdenRepository;
use RuntimeException;

final class OrdenService
{
    public const ESTADOS = [
        'recibida', 'en_revision', 'diagnosticada', 'esperando_autorizacion', 'autorizada',
        'rechazada', 'en_reparacion', 'esperando_refaccion', 'reparada', 'no_reparable',
        'lista_para_entrega', 'entregada', 'cancelada', 'garantia'
    ];

    public function __construct(
        private readonly OrdenRepository $ordenes = new OrdenRepository(),
        private readonly FolioService $folios = new FolioService(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(array $filters = []): array
    {
        return $this->ordenes->all($filters);
    }

    public function obtener(int $id): ?array
    {
        return $this->ordenes->find($id);
    }

    public function crear(array $data): int
    {
        $db = Database::connection();
        $db->beginTransaction();
        try {
            $total = (float) ($data['costo_estimado'] ?? 0);
            $anticipo = (float) ($data['anticipo'] ?? 0);
            $folio = $this->folios->generar();
            $payload = [
                'folio' => $folio,
                'cliente_id' => (int) $data['cliente_id'],
                'equipo_id' => (int) $data['equipo_id'],
                'tecnico_id' => !empty($data['tecnico_id']) ? (int) $data['tecnico_id'] : null,
                'recibido_por' => Auth::id() ?? 1,
                'tipo_servicio' => trim((string) $data['tipo_servicio']),
                'falla_reportada' => trim((string) $data['falla_reportada']),
                'diagnostico_inicial' => trim((string) ($data['diagnostico_inicial'] ?? '')) ?: null,
                'prioridad' => in_array(($data['prioridad'] ?? 'normal'), ['baja','normal','alta','urgente'], true) ? $data['prioridad'] : 'normal',
                'estado' => 'recibida',
                'fecha_estimada_entrega' => $data['fecha_estimada_entrega'] ?: null,
                'costo_estimado' => $total,
                'costo_final' => $total,
                'anticipo' => $anticipo,
                'saldo_pendiente' => calcularSaldo($total, $anticipo),
                'garantia_ofrecida' => trim((string) ($data['garantia_ofrecida'] ?? '')) ?: null,
                'observaciones_internas' => trim((string) ($data['observaciones_internas'] ?? '')) ?: null,
                'observaciones_cliente' => trim((string) ($data['observaciones_cliente'] ?? '')) ?: null,
                'codigo_entrega' => $this->folios->codigoEntrega(),
                'ubicacion_actual' => 'Recepcion',
                'token_publico' => $this->folios->tokenPublico(),
            ];

            $id = $this->ordenes->create($payload);
            if ($anticipo > 0) {
                (new PagoService())->registrar([
                    'orden_id' => $id,
                    'monto' => $anticipo,
                    'metodo' => $data['metodo_anticipo'] ?? 'efectivo',
                    'referencia' => $data['referencia_anticipo'] ?? null,
                    'notas' => 'Anticipo registrado al crear orden',
                ], false);
            }

            $this->auditoria->registrar('crear', 'ordenes', $id, null, $payload);
            $db->commit();
            return $id;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function crearRapida(array $data): int
    {
        /*
         * Flujo de recepcion rapida.
         * Fuente: formulario /ordenes/create.
         * Revisa/crea: cliente, equipo y orden.
         * Destino: tablas clientes, equipos, ordenes_servicio y pagos si hay anticipo.
         * Seguridad: transaccion unica; si algo falla, no quedan registros a medias.
         */
        $db = Database::connection();
        $db->beginTransaction();

        try {
            $clienteId = (int) ($data['cliente_id'] ?? 0);
            $clientePayload = null;
            $equipoId = (int) ($data['equipo_id'] ?? 0);
            $equipoSeleccionado = null;

            if ($equipoId > 0) {
                // Si llega equipo existente, se valida que exista y se usa su cliente si no se mando cliente_id.
                $equipoSeleccionado = (new EquipoRepository())->find($equipoId);
                if (!$equipoSeleccionado) {
                    throw new RuntimeException('El equipo seleccionado no existe.');
                }
                if ($clienteId <= 0) {
                    $clienteId = (int) $equipoSeleccionado['cliente_id'];
                }
            }

            if ($clienteId > 0) {
                // Cliente existente: solo se referencia, no se sobreescriben sus datos desde este formulario.
                $cliente = (new ClienteRepository())->find($clienteId);
                if (!$cliente) {
                    throw new RuntimeException('El cliente seleccionado no existe.');
                }
            } else {
                // Cliente nuevo: se normaliza telefono/email y se bloquea duplicado antes de insertar.
                $clientePayload = $this->normalizarClienteRapido($data);
                if ($clientePayload['nombre_completo'] === '' || $clientePayload['telefono'] === '') {
                    throw new RuntimeException('Selecciona un cliente existente o captura nombre y telefono del cliente nuevo.');
                }
                if ($clientePayload['email'] && !filter_var($clientePayload['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException('El email del cliente no es valido.');
                }

                $clientes = new ClienteRepository();
                if ($clientes->findDuplicate($clientePayload['telefono'], $clientePayload['email'], null)) {
                    throw new RuntimeException('Ya existe un cliente con ese telefono o email. Buscalo y seleccionalo para reutilizarlo.');
                }

                $clienteId = $clientes->create($clientePayload);
                $this->auditoria->registrar('crear', 'clientes', $clienteId, null, $clientePayload);
            }

            $equipoPayload = null;

            if ($equipoId > 0) {
                // Evita asociar una orden a un equipo que pertenece a otro cliente.
                if ((int) $equipoSeleccionado['cliente_id'] !== $clienteId) {
                    throw new RuntimeException('El equipo seleccionado no pertenece al cliente de la orden.');
                }
            } else {
                // Equipo nuevo: queda ligado al cliente resuelto arriba.
                $equipoPayload = $this->normalizarEquipoRapido($data, $clienteId);
                if ($equipoPayload['tipo'] === '') {
                    throw new RuntimeException('Selecciona un equipo existente o captura el tipo de equipo nuevo.');
                }

                $equipoId = (new EquipoRepository())->create($equipoPayload);
                $this->auditoria->registrar('crear', 'equipos', $equipoId, null, $equipoPayload);
            }

            $ordenPayload = $this->normalizarOrdenRapida($data, $clienteId, $equipoId);
            $ordenId = $this->ordenes->create($ordenPayload);

            if ((float) $ordenPayload['anticipo'] > 0) {
                // El anticipo se registra como pago dentro de la misma transaccion de la orden.
                (new PagoService())->registrar([
                    'orden_id' => $ordenId,
                    'monto' => (float) $ordenPayload['anticipo'],
                    'metodo' => $data['metodo_anticipo'] ?? 'efectivo',
                    'referencia' => $data['referencia_anticipo'] ?? null,
                    'notas' => 'Anticipo registrado al crear orden rapida',
                ], false);
            }

            $this->auditoria->registrar('crear', 'ordenes', $ordenId, null, [
                'orden' => $ordenPayload,
                'cliente_creado' => $clientePayload,
                'equipo_creado' => $equipoPayload,
            ]);

            $db->commit();
            return $ordenId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function cambiarEstado(int $id, string $estado, bool $forzar = false): void
    {
        /*
         * Cambio de estado controlado.
         * Fuente: formulario de orden.
         * Revisa: estado permitido y reglas de negocio.
         * Destino: ordenes_servicio + auditoria.
         */
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new RuntimeException('Estado de orden no valido.');
        }

        $orden = $this->ordenes->find($id);
        if (!$orden) {
            throw new RuntimeException('Orden no encontrada.');
        }

        if ($estado === 'entregada') {
            // La entrega se fuerza por modulo Entregas para exigir clave/codigo y registrar responsable.
            throw new RuntimeException('Para entregar un equipo usa el modulo Entregas y la clave del codigo de barras.');
        }

        if ($estado === 'en_reparacion' && !in_array($orden['estado'], ['autorizada','en_reparacion'], true) && !$forzar) {
            throw new RuntimeException('La orden no puede pasar a reparacion sin autorizacion.');
        }

        if ($estado === 'entregada' && (float) $orden['saldo_pendiente'] > 0 && !$forzar) {
            throw new RuntimeException('No se puede entregar una orden con saldo pendiente.');
        }

        $fechaEntrega = $estado === 'entregada' ? date('Y-m-d H:i:s') : null;
        $this->ordenes->updateState($id, $estado, $fechaEntrega);
        $this->auditoria->registrar('cambiar_estado', 'ordenes', $id, ['estado' => $orden['estado']], ['estado' => $estado]);
    }

    public function asignarTecnico(int $id, ?int $tecnicoId): void
    {
        $orden = $this->ordenes->find($id);
        $this->ordenes->assignTechnician($id, $tecnicoId);
        $this->auditoria->registrar('asignar_tecnico', 'ordenes', $id, ['tecnico_id' => $orden['tecnico_id'] ?? null], ['tecnico_id' => $tecnicoId]);
    }

    public function actualizarTotales(int $id, float $total): void
    {
        $pagos = $this->ordenes->pagosActivos($id);
        $this->ordenes->updateTotals($id, $total, $pagos);
    }

    public function portal(string $folio, string $token): ?array
    {
        return $this->ordenes->findByPublicToken($folio, $token);
    }

    public function buscarPorCodigoEntrega(string $codigo): ?array
    {
        $codigo = strtoupper(trim($codigo));
        return $codigo === '' ? null : $this->ordenes->findByDeliveryCode($codigo);
    }

    public function buscarIdExacto(string $codigo): ?int
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            return null;
        }

        $row = $this->ordenes->findByLookupCode($codigo);
        return $row ? (int) $row['id'] : null;
    }

    private function normalizarClienteRapido(array $data): array
    {
        // Limpia datos del cliente antes de enviarlos al repositorio/MySQL.
        return [
            'nombre_completo' => trim((string) ($data['nombre_completo'] ?? '')),
            'telefono' => normalizarTelefono((string) ($data['telefono'] ?? '')),
            'whatsapp' => normalizarTelefono((string) ($data['whatsapp'] ?? $data['telefono'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'domicilio' => trim((string) ($data['domicilio'] ?? '')) ?: null,
            'ciudad' => trim((string) ($data['ciudad'] ?? '')) ?: null,
            'estado' => trim((string) ($data['estado_cliente'] ?? $data['estado'] ?? '')) ?: null,
            'codigo_postal' => trim((string) ($data['codigo_postal'] ?? '')) ?: null,
            'rfc' => strtoupper(trim((string) ($data['rfc'] ?? ''))) ?: null,
            'notas_internas' => trim((string) ($data['notas_cliente'] ?? '')) ?: null,
            'estatus' => 'activo',
        ];
    }

    private function normalizarEquipoRapido(array $data, int $clienteId): array
    {
        // Limpia datos del equipo y limita tipo a valores conocidos.
        $tipos = ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'];
        $tipo = (string) ($data['tipo'] ?? 'otro');

        return [
            'cliente_id' => $clienteId,
            'tipo' => in_array($tipo, $tipos, true) ? $tipo : 'otro',
            'marca' => trim((string) ($data['marca'] ?? '')) ?: null,
            'modelo' => trim((string) ($data['modelo'] ?? '')) ?: null,
            'numero_serie' => trim((string) ($data['numero_serie'] ?? '')) ?: null,
            'imei' => trim((string) ($data['imei'] ?? '')) ?: null,
            'color' => trim((string) ($data['color'] ?? '')) ?: null,
            'password_equipo' => trim((string) ($data['password_equipo'] ?? '')) ?: null,
            'accesorios_recibidos' => trim((string) ($data['accesorios_recibidos'] ?? '')) ?: null,
            'estado_fisico' => trim((string) ($data['estado_fisico'] ?? '')) ?: null,
            'observaciones' => trim((string) ($data['observaciones_equipo'] ?? $data['observaciones'] ?? '')) ?: null,
        ];
    }

    private function normalizarOrdenRapida(array $data, int $clienteId, int $equipoId): array
    {
        // Construye la orden final: folio/token/codigo se generan del lado servidor, nunca desde el navegador.
        if (trim((string) ($data['tipo_servicio'] ?? '')) === '') {
            throw new RuntimeException('El tipo de servicio es obligatorio.');
        }
        if (trim((string) ($data['falla_reportada'] ?? '')) === '') {
            throw new RuntimeException('La falla reportada es obligatoria.');
        }

        $total = (float) ($data['costo_estimado'] ?? 0);
        $anticipo = (float) ($data['anticipo'] ?? 0);
        $folio = $this->folios->generar();

        return [
            'folio' => $folio,
            'cliente_id' => $clienteId,
            'equipo_id' => $equipoId,
            'tecnico_id' => !empty($data['tecnico_id']) ? (int) $data['tecnico_id'] : null,
            'recibido_por' => Auth::id() ?? 1,
            'tipo_servicio' => trim((string) $data['tipo_servicio']),
            'falla_reportada' => trim((string) $data['falla_reportada']),
            'diagnostico_inicial' => trim((string) ($data['diagnostico_inicial'] ?? '')) ?: null,
            'prioridad' => in_array(($data['prioridad'] ?? 'normal'), ['baja','normal','alta','urgente'], true) ? $data['prioridad'] : 'normal',
            'estado' => 'recibida',
            'fecha_estimada_entrega' => $data['fecha_estimada_entrega'] ?: null,
            'costo_estimado' => $total,
            'costo_final' => $total,
            'anticipo' => $anticipo,
            'saldo_pendiente' => calcularSaldo($total, $anticipo),
            'garantia_ofrecida' => trim((string) ($data['garantia_ofrecida'] ?? '')) ?: null,
            'observaciones_internas' => trim((string) ($data['observaciones_internas'] ?? '')) ?: null,
            'observaciones_cliente' => trim((string) ($data['observaciones_cliente'] ?? '')) ?: null,
            'codigo_entrega' => $this->folios->codigoEntrega(),
            'ubicacion_actual' => 'Recepcion',
            'token_publico' => $this->folios->tokenPublico(),
        ];
    }
}
