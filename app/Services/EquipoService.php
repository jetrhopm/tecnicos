<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EquipoRepository;

final class EquipoService
{
    public function __construct(
        private readonly EquipoRepository $equipos = new EquipoRepository(),
        private readonly EquipoCatalogoService $catalogo = new EquipoCatalogoService(),
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
        $tipo = in_array(($data['tipo'] ?? 'otro'), $tipos, true) ? $data['tipo'] : 'otro';
        $catalogo = $this->catalogo->resolver(
            (string) ($data['marca'] ?? ''),
            (string) ($data['modelo'] ?? ''),
            $tipo
        );

        return [
            'cliente_id' => (int) ($data['cliente_id'] ?? 0),
            'tipo' => $tipo,
            'marca_id' => $catalogo['marca_id'],
            'modelo_id' => $catalogo['modelo_id'],
            'marca' => $catalogo['marca'],
            'modelo' => $catalogo['modelo'],
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
