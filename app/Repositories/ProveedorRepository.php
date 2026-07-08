<?php

declare(strict_types=1);

namespace App\Repositories;

final class ProveedorRepository extends BaseRepository
{
    public function all(string $term = ''): array
    {
        $term = trim($term);
        if ($term !== '') {
            $like = '%' . $term . '%';
            return $this->fetchAll(
                "SELECT * FROM proveedores
                 WHERE deleted_at IS NULL AND (nombre LIKE :n OR contacto LIKE :c OR telefono LIKE :t)
                 ORDER BY nombre",
                ['n' => $like, 'c' => $like, 't' => $like]
            );
        }

        return $this->fetchAll('SELECT * FROM proveedores WHERE deleted_at IS NULL ORDER BY nombre');
    }

    public function activos(): array
    {
        return $this->fetchAll("SELECT id, nombre FROM proveedores WHERE deleted_at IS NULL AND estatus = 'activo' ORDER BY nombre");
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM proveedores WHERE id = :id AND deleted_at IS NULL', ['id' => $id]);
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO proveedores (nombre, contacto, telefono, email, domicilio, sitio_web, notas, estatus)
             VALUES (:nombre, :contacto, :telefono, :email, :domicilio, :sitio_web, :notas, :estatus)",
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            "UPDATE proveedores SET nombre = :nombre, contacto = :contacto, telefono = :telefono, email = :email,
                    domicilio = :domicilio, sitio_web = :sitio_web, notas = :notas, estatus = :estatus
             WHERE id = :id",
            $data
        );
    }
}
