<?php

declare(strict_types=1);

namespace App\Repositories;

final class AgendaRepository extends BaseRepository
{
    public function all(array $filtros = []): array
    {
        $params = [
            'desde' => (string) $filtros['desde'],
            'hasta' => (string) $filtros['hasta'],
        ];
        $sql = "SELECT a.*, o.folio, o.estado orden_estado,
                       c.nombre_completo cliente_nombre,
                       e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo,
                       t.name tecnico_nombre, u.name creado_por_nombre
                FROM agenda_eventos a
                LEFT JOIN ordenes_servicio o ON o.id = a.orden_id
                LEFT JOIN clientes c ON c.id = o.cliente_id
                LEFT JOIN equipos e ON e.id = o.equipo_id
                LEFT JOIN users t ON t.id = a.tecnico_id
                LEFT JOIN users u ON u.id = a.created_by
                WHERE a.inicio >= :desde AND a.inicio < :hasta";

        if (!empty($filtros['tecnico_id'])) {
            $sql .= ' AND a.tecnico_id = :tecnico_id';
            $params['tecnico_id'] = (int) $filtros['tecnico_id'];
        }
        if (!empty($filtros['estado'])) {
            $sql .= ' AND a.estado = :estado';
            $params['estado'] = (string) $filtros['estado'];
        }
        if (!empty($filtros['tipo'])) {
            $sql .= ' AND a.tipo = :tipo';
            $params['tipo'] = (string) $filtros['tipo'];
        }
        if (!empty($filtros['q'])) {
            $sql .= ' AND (a.titulo LIKE :q_titulo OR a.descripcion LIKE :q_desc OR o.folio LIKE :q_folio OR c.nombre_completo LIKE :q_cliente)';
            $like = '%' . trim((string) $filtros['q']) . '%';
            $params['q_titulo'] = $like;
            $params['q_desc'] = $like;
            $params['q_folio'] = $like;
            $params['q_cliente'] = $like;
        }

        $sql .= ' ORDER BY a.inicio ASC, a.id ASC';
        return $this->fetchAll($sql, $params);
    }

    public function forOrder(int $ordenId, int $limite = 8): array
    {
        $limite = max(1, min(30, $limite));
        return $this->fetchAll(
            "SELECT a.*, t.name tecnico_nombre
             FROM agenda_eventos a
             LEFT JOIN users t ON t.id = a.tecnico_id
             WHERE a.orden_id = :orden_id
             ORDER BY a.inicio DESC
             LIMIT {$limite}",
            ['orden_id' => $ordenId]
        );
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO agenda_eventos (orden_id, tecnico_id, titulo, descripcion, inicio, fin, tipo, estado, created_by)
             VALUES (:orden_id, :tecnico_id, :titulo, :descripcion, :inicio, :fin, :tipo, :estado, :created_by)",
            $data
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM agenda_eventos WHERE id = :id', ['id' => $id]);
    }

    public function changeStatus(int $id, string $estado): void
    {
        $this->execute('UPDATE agenda_eventos SET estado = :estado WHERE id = :id', ['id' => $id, 'estado' => $estado]);
    }
}
