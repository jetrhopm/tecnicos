<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EquipoRepository;

final class EquipoService
{
    public function __construct(
        private readonly EquipoRepository $equipos = new EquipoRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(?int $clienteId = null): array
    {
        return $this->equipos->all($clienteId);
    }

    public function obtener(int $id): ?array
    {
        return $this->equipos->find($id);
    }

    public function guardar(array $data, ?int $id = null): int
    {
        $payload = $this->normalizar($data);
        if ($id) {
            $anterior = $this->equipos->find($id);
            $this->equipos->update($id, $payload);
            $this->auditoria->registrar('editar', 'equipos', $id, $anterior, $payload);
            return $id;
        }

        $newId = $this->equipos->create($payload);
        $this->auditoria->registrar('crear', 'equipos', $newId, null, $payload);
        return $newId;
    }

    private function normalizar(array $data): array
    {
        $tipos = ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'];
        return [
            'cliente_id' => (int) ($data['cliente_id'] ?? 0),
            'tipo' => in_array(($data['tipo'] ?? 'otro'), $tipos, true) ? $data['tipo'] : 'otro',
            'marca' => trim((string) ($data['marca'] ?? '')) ?: null,
            'modelo' => trim((string) ($data['modelo'] ?? '')) ?: null,
            'numero_serie' => trim((string) ($data['numero_serie'] ?? '')) ?: null,
            'imei' => trim((string) ($data['imei'] ?? '')) ?: null,
            'color' => trim((string) ($data['color'] ?? '')) ?: null,
            'password_equipo' => trim((string) ($data['password_equipo'] ?? '')) ?: null,
            'accesorios_recibidos' => trim((string) ($data['accesorios_recibidos'] ?? '')) ?: null,
            'estado_fisico' => trim((string) ($data['estado_fisico'] ?? '')) ?: null,
            'observaciones' => trim((string) ($data['observaciones'] ?? '')) ?: null,
        ];
    }
}
