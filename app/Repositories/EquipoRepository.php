<?php

declare(strict_types=1);

namespace App\Repositories;

final class EquipoRepository extends BaseRepository
{
    public function all(?int $clienteId = null): array
    {
        $params = [];
        $sql = "SELECT e.*, c.nombre_completo cliente_nombre
                FROM equipos e JOIN clientes c ON c.id = e.cliente_id
                WHERE e.deleted_at IS NULL";
        if ($clienteId) {
            $sql .= " AND e.cliente_id = :cliente_id";
            $params['cliente_id'] = $clienteId;
        }
        $sql .= " ORDER BY e.created_at DESC LIMIT 100";

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT e.*, c.nombre_completo cliente_nombre
             FROM equipos e JOIN clientes c ON c.id = e.cliente_id
             WHERE e.id = :id AND e.deleted_at IS NULL",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO equipos (cliente_id, tipo, marca, modelo, numero_serie, imei, color, password_equipo, accesorios_recibidos, estado_fisico, observaciones)
             VALUES (:cliente_id, :tipo, :marca, :modelo, :numero_serie, :imei, :color, :password_equipo, :accesorios_recibidos, :estado_fisico, :observaciones)",
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            "UPDATE equipos SET cliente_id = :cliente_id, tipo = :tipo, marca = :marca, modelo = :modelo, numero_serie = :numero_serie,
             imei = :imei, color = :color, password_equipo = :password_equipo, accesorios_recibidos = :accesorios_recibidos,
             estado_fisico = :estado_fisico, observaciones = :observaciones WHERE id = :id",
            $data
        );
    }
}
