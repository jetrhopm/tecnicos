<?php

declare(strict_types=1);

namespace App\Repositories;

final class ReporteRepository extends BaseRepository
{
    public function ordenesPorEstado(): array
    {
        return $this->fetchAll("SELECT estado, COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL GROUP BY estado ORDER BY total DESC");
    }

    public function ordenesPorTecnico(): array
    {
        return $this->fetchAll(
            "SELECT COALESCE(u.name, 'Sin asignar') tecnico, COUNT(*) total
             FROM ordenes_servicio o LEFT JOIN users u ON u.id = o.tecnico_id
             WHERE o.deleted_at IS NULL GROUP BY tecnico ORDER BY total DESC"
        );
    }
}
