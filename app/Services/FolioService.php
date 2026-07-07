<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OrdenRepository;

final class FolioService
{
    public function __construct(
        private readonly OrdenRepository $ordenes = new OrdenRepository(),
        private readonly ConfiguracionService $config = new ConfiguracionService()
    ) {
    }

    public function generar(): string
    {
        $prefijo = (string) $this->config->get('ordenes.prefijo_folio', 'ST');
        $hoy = date('Y-m-d');
        $consecutivo = $this->ordenes->nextConsecutiveForDate($hoy);
        return generarFolio($prefijo, $consecutivo, $hoy);
    }

    public function tokenPublico(): string
    {
        return bin2hex(random_bytes(24));
    }

    public function codigoEntrega(string $folio): string
    {
        return 'ENT-' . strtoupper(preg_replace('/[^A-Z0-9-]/', '', $folio) ?? $folio);
    }
}
