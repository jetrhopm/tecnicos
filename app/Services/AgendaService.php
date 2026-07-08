<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\AgendaRepository;
use App\Repositories\OrdenRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use RuntimeException;

final class AgendaService
{
    public const TIPOS = ['visita', 'entrega', 'recordatorio', 'trabajo', 'otro'];
    public const ESTADOS = ['programado', 'realizado', 'cancelado'];

    public function __construct(
        private readonly AgendaRepository $agenda = new AgendaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(array $filtros): array
    {
        return $this->agenda->all($filtros);
    }

    public function porOrden(int $ordenId): array
    {
        return $this->agenda->forOrder($ordenId);
    }

    public function crear(array $data): int
    {
        $titulo = trim((string) ($data['titulo'] ?? ''));
        if ($titulo === '') {
            throw new RuntimeException('El titulo del evento es obligatorio.');
        }

        $inicio = $this->normalizarFechaHora((string) ($data['inicio'] ?? ''));
        $fin = $this->normalizarFechaHora((string) ($data['fin'] ?? ''), true);
        if ($fin !== null && $fin <= $inicio) {
            throw new RuntimeException('La fecha fin debe ser posterior al inicio.');
        }

        $tipo = (string) ($data['tipo'] ?? 'otro');
        if (!in_array($tipo, self::TIPOS, true)) {
            $tipo = 'otro';
        }

        $tecnicoId = !empty($data['tecnico_id']) ? (int) $data['tecnico_id'] : null;
        if ($tecnicoId !== null && !(new UserRepository())->find($tecnicoId)) {
            throw new RuntimeException('Tecnico no encontrado.');
        }

        $ordenId = $this->resolverOrdenId($data);

        $id = $this->agenda->create([
            'orden_id' => $ordenId,
            'tecnico_id' => $tecnicoId,
            'titulo' => mb_substr($titulo, 0, 190),
            'descripcion' => trim((string) ($data['descripcion'] ?? '')) ?: null,
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'fin' => $fin?->format('Y-m-d H:i:s'),
            'tipo' => $tipo,
            'estado' => 'programado',
            'created_by' => Auth::id() ?? 1,
        ]);

        $this->auditoria->registrar('crear', 'agenda', $id, null, [
            'orden_id' => $ordenId,
            'tecnico_id' => $tecnicoId,
            'inicio' => $inicio->format('Y-m-d H:i:s'),
            'tipo' => $tipo,
        ]);

        return $id;
    }

    public function cambiarEstado(int $id, string $estado): void
    {
        if (!in_array($estado, self::ESTADOS, true)) {
            throw new RuntimeException('Estado de agenda no valido.');
        }

        $evento = $this->agenda->find($id);
        if (!$evento) {
            throw new RuntimeException('Evento no encontrado.');
        }

        $this->agenda->changeStatus($id, $estado);
        $this->auditoria->registrar('cambiar_estado', 'agenda', $id, ['estado' => $evento['estado']], ['estado' => $estado]);
    }

    public function rangoDesdeVista(string $vista, string $fecha): array
    {
        $base = DateTimeImmutable::createFromFormat('Y-m-d', $fecha) ?: new DateTimeImmutable('today');
        if ($vista === 'semana') {
            $desde = $base->modify('monday this week')->setTime(0, 0);
            $hasta = $desde->modify('+7 days');
            return [$desde, $hasta];
        }

        $desde = $base->setTime(0, 0);
        return [$desde, $desde->modify('+1 day')];
    }

    private function resolverOrdenId(array $data): ?int
    {
        if (!empty($data['orden_id'])) {
            $ordenId = (int) $data['orden_id'];
            if (!(new OrdenRepository())->find($ordenId)) {
                throw new RuntimeException('Orden no encontrada.');
            }
            return $ordenId;
        }

        $referencia = trim((string) ($data['orden_ref'] ?? ''));
        if ($referencia === '') {
            return null;
        }

        $ordenId = (new OrdenService())->buscarIdExacto($referencia);
        if (!$ordenId) {
            throw new RuntimeException('No se encontro una orden con ese folio o clave.');
        }

        return $ordenId;
    }

    private function normalizarFechaHora(string $valor, bool $nullable = false): ?DateTimeImmutable
    {
        $valor = trim($valor);
        if ($valor === '') {
            if ($nullable) {
                return null;
            }
            throw new RuntimeException('La fecha de inicio es obligatoria.');
        }

        $fecha = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $valor)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $valor)
            ?: DateTimeImmutable::createFromFormat('Y-m-d H:i', $valor);

        if (!$fecha) {
            throw new RuntimeException('Formato de fecha no valido.');
        }

        return $fecha;
    }
}
