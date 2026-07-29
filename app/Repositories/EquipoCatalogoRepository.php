<?php

declare(strict_types=1);

namespace App\Repositories;

final class EquipoCatalogoRepository extends BaseRepository
{
    public function buscarMarcas(string $q, int $limit = 12): array
    {
        $q = trim($q);
        $limit = max(1, min($limit, 40));

        return $this->fetchAll(
            "SELECT id, nombre
             FROM equipo_marcas
             WHERE estatus = 'activo'
             AND (:q = '' OR nombre LIKE :like)
             ORDER BY
                CASE WHEN nombre = :exact THEN 0 WHEN nombre LIKE :starts THEN 1 ELSE 2 END,
                nombre ASC
             LIMIT {$limit}",
            [
                'q' => $q,
                'like' => '%' . $q . '%',
                'exact' => $q,
                'starts' => $q . '%',
            ]
        );
    }

    public function buscarModelos(?int $marcaId, string $marcaTexto, string $q, int $limit = 12): array
    {
        $q = trim($q);
        $marcaTexto = trim($marcaTexto);
        $limit = max(1, min($limit, 40));
        $params = [
            'q' => $q,
            'like' => '%' . $q . '%',
            'exact' => $q,
            'starts' => $q . '%',
        ];

        $whereMarca = '';
        if ($marcaId !== null && $marcaId > 0) {
            $whereMarca = 'AND em.id = :marca_id';
            $params['marca_id'] = $marcaId;
        } elseif ($marcaTexto !== '') {
            $whereMarca = 'AND em.nombre LIKE :marca_texto';
            $params['marca_texto'] = '%' . $marcaTexto . '%';
        }

        return $this->fetchAll(
            "SELECT mo.id, mo.marca_id, mo.nombre, mo.tipo_equipo, em.nombre AS marca
             FROM equipo_modelos mo
             JOIN equipo_marcas em ON em.id = mo.marca_id
             WHERE mo.estatus = 'activo'
             {$whereMarca}
             AND (:q = '' OR mo.nombre LIKE :like)
             ORDER BY
                CASE WHEN mo.nombre = :exact THEN 0 WHEN mo.nombre LIKE :starts THEN 1 ELSE 2 END,
                mo.nombre ASC
             LIMIT {$limit}",
            $params
        );
    }

    public function buscarMarcaPorSlug(string $slug): ?array
    {
        return $this->fetch(
            "SELECT id, nombre, slug FROM equipo_marcas WHERE slug = :slug LIMIT 1",
            ['slug' => $slug]
        );
    }

    public function crearMarca(string $nombre, string $slug): int
    {
        $this->execute(
            "INSERT INTO equipo_marcas (nombre, slug, estatus)
             VALUES (:nombre, :slug, 'activo')
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estatus = 'activo'",
            ['nombre' => $nombre, 'slug' => $slug]
        );

        $row = $this->buscarMarcaPorSlug($slug);
        return (int) ($row['id'] ?? 0);
    }

    public function buscarModeloPorSlug(int $marcaId, string $slug): ?array
    {
        return $this->fetch(
            "SELECT id, marca_id, nombre, slug, tipo_equipo
             FROM equipo_modelos
             WHERE marca_id = :marca_id AND slug = :slug
             LIMIT 1",
            ['marca_id' => $marcaId, 'slug' => $slug]
        );
    }

    public function crearModelo(int $marcaId, string $nombre, string $slug, ?string $tipo): int
    {
        $this->execute(
            "INSERT INTO equipo_modelos (marca_id, nombre, slug, tipo_equipo, estatus)
             VALUES (:marca_id, :nombre, :slug, :tipo_equipo, 'activo')
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo_equipo = VALUES(tipo_equipo), estatus = 'activo'",
            [
                'marca_id' => $marcaId,
                'nombre' => $nombre,
                'slug' => $slug,
                'tipo_equipo' => $tipo,
            ]
        );

        $row = $this->buscarModeloPorSlug($marcaId, $slug);
        return (int) ($row['id'] ?? 0);
    }
}
