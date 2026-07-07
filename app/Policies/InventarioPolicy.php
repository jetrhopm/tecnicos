<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;

final class InventarioPolicy
{
    public function puedeStockNegativo(): bool
    {
        return Auth::can('inventario', 'autorizar') || Auth::can('inventario', 'administrar');
    }
}
