<?php

declare(strict_types=1);

namespace App\Repositories;

final class ArchivoRepository extends BaseRepository
{
    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO archivos
             (entidad_tipo, entidad_id, categoria, nombre_original, nombre_archivo, ruta, mime, tamano, visible_cliente, uploaded_by)
             VALUES
             (:entidad_tipo, :entidad_id, :categoria, :nombre_original, :nombre_archivo, :ruta, :mime, :tamano, :visible_cliente, :uploaded_by)",
            $data
        );
    }

    public function forEntidad(string $tipo, int $id): array
    {
        return $this->fetchAll(
            'SELECT * FROM archivos WHERE entidad_tipo = :tipo AND entidad_id = :id ORDER BY id DESC',
            ['tipo' => $tipo, 'id' => $id]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM archivos WHERE id = :id', ['id' => $id]);
    }
}
