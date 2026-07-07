<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\GarantiaRepository;

final class GarantiaService
{
    public function activas(): array
    {
        return (new GarantiaRepository())->active();
    }
}
