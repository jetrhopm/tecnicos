<?php

declare(strict_types=1);

namespace App\Policies;

use App\Core\Auth;

final class OrdenPolicy
{
    public function puedeEntregarConSaldo(): bool
    {
        return Auth::can('ordenes', 'autorizar');
    }
}
