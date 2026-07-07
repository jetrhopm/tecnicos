<?php

declare(strict_types=1);

namespace App\Repositories;

final class InventarioRepository extends BaseRepository
{
    public function stockBajo(): array
    {
        return $this->fetchAll("SELECT * FROM refacciones WHERE deleted_at IS NULL AND stock_actual <= stock_minimo AND estatus = 'activo' ORDER BY stock_actual ASC");
    }
}
