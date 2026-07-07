<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\InventarioRepository;

final class InventarioService
{
    public function stockBajo(): array
    {
        return (new InventarioRepository())->stockBajo();
    }
}
