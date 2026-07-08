<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProveedorRepository;
use RuntimeException;

final class ProveedorService
{
    public function __construct(
        private readonly ProveedorRepository $proveedores = new ProveedorRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(string $term = ''): array
    {
        return $this->proveedores->all($term);
    }

    public function activos(): array
    {
        return $this->proveedores->activos();
    }

    public function obtener(int $id): ?array
    {
        return $this->proveedores->find($id);
    }

    public function guardar(array $data, ?int $id = null): int
    {
        $estatus = $data['estatus'] ?? 'activo';
        $payload = [
            'nombre' => trim((string) ($data['nombre'] ?? '')),
            'contacto' => trim((string) ($data['contacto'] ?? '')) ?: null,
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'domicilio' => trim((string) ($data['domicilio'] ?? '')) ?: null,
            'sitio_web' => trim((string) ($data['sitio_web'] ?? '')) ?: null,
            'notas' => trim((string) ($data['notas'] ?? '')) ?: null,
            'estatus' => in_array($estatus, ['activo', 'inactivo'], true) ? $estatus : 'activo',
        ];

        if ($payload['nombre'] === '') {
            throw new RuntimeException('El nombre del proveedor es obligatorio.');
        }
        if ($payload['email'] !== null && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El email del proveedor no es valido.');
        }

        if ($id) {
            if (!$this->proveedores->find($id)) {
                throw new RuntimeException('Proveedor no encontrado.');
            }
            $this->proveedores->update($id, $payload);
            $this->auditoria->registrar('editar', 'proveedores', $id, null, $payload);
            return $id;
        }

        $newId = $this->proveedores->create($payload);
        $this->auditoria->registrar('crear', 'proveedores', $newId, null, $payload);
        return $newId;
    }
}
