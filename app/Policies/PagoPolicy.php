<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;

final class PagoPolicy
{
    public function puedeCancelar(): bool
    {
        return Auth::can('pagos', 'autorizar') || Auth::can('pagos', 'administrar');
    }
}
