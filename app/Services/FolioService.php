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

    public function codigoEntrega(): string
    {
        /*
         * Clave de entrega para la nota del cliente.
         * Aleatoria y no derivable del folio: sin la nota impresa (o su codigo
         * de barras) no se conoce la clave. Corta para teclearse a mano y
         * compatible con Code 39; el alfabeto omite 0/O e 1/I para evitar
         * confusiones al leerla o dictarla.
         */
        $alfabeto = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        do {
            $codigo = 'ENT-';
            for ($i = 0; $i < 8; $i++) {
                $codigo .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
        } while ($this->ordenes->deliveryCodeExists($codigo));

        return $codigo;
    }
}
