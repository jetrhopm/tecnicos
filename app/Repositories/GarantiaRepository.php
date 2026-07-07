<?php

declare(strict_types=1);

namespace App\Repositories;

final class GarantiaRepository extends BaseRepository
{
    public function active(): array
    {
        return $this->fetchAll(
            "SELECT g.*, o.folio, c.nombre_completo cliente_nombre
             FROM garantias g
             JOIN ordenes_servicio o ON o.id = g.orden_id
             JOIN clientes c ON c.id = o.cliente_id
             WHERE g.estado = 'activa' AND g.fecha_fin >= CURDATE()
             ORDER BY g.fecha_fin ASC"
        );
    }
}
