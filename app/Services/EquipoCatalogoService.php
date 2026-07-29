<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\EquipoCatalogoRepository;

final class EquipoCatalogoService
{
    private const TIPOS = ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'];

    public function __construct(
        private readonly EquipoCatalogoRepository $catalogo = new EquipoCatalogoRepository()
    ) {
    }

    public function buscarMarcas(string $q, int $limit = 12): array
    {
        return $this->catalogo->buscarMarcas($this->limpiarNombre($q), $limit);
    }

    public function buscarModelos(?int $marcaId, string $marcaTexto, string $q, int $limit = 12): array
    {
        return $this->catalogo->buscarModelos($marcaId, $this->limpiarNombre($marcaTexto), $this->limpiarNombre($q), $limit);
    }

    public function resolver(?string $marca, ?string $modelo, ?string $tipo): array
    {
        $marcaNombre = $this->limpiarNombre((string) $marca);
        $modeloNombre = $this->limpiarNombre((string) $modelo);
        $tipoNormalizado = in_array($tipo, self::TIPOS, true) ? $tipo : null;
        $marcaId = null;
        $modeloId = null;

        if ($marcaNombre !== '') {
            $marcaSlug = $this->slug($marcaNombre);
            $marcaRow = $this->catalogo->buscarMarcaPorSlug($marcaSlug);
            $marcaId = $marcaRow ? (int) $marcaRow['id'] : $this->catalogo->crearMarca($marcaNombre, $marcaSlug);
            $marcaNombre = (string) ($marcaRow['nombre'] ?? $marcaNombre);
        }

        if ($marcaId !== null && $modeloNombre !== '') {
            $modeloSlug = $this->slug($modeloNombre);
            $modeloRow = $this->catalogo->buscarModeloPorSlug($marcaId, $modeloSlug);
            $modeloId = $modeloRow ? (int) $modeloRow['id'] : $this->catalogo->crearModelo($marcaId, $modeloNombre, $modeloSlug, $tipoNormalizado);
            $modeloNombre = (string) ($modeloRow['nombre'] ?? $modeloNombre);
        }

        return [
            'marca_id' => $marcaId,
            'modelo_id' => $modeloId,
            'marca' => $marcaNombre !== '' ? $marcaNombre : null,
            'modelo' => $modeloNombre !== '' ? $modeloNombre : null,
        ];
    }

    private function limpiarNombre(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        return mb_substr($value, 0, 120);
    }

    private function slug(string $value): string
    {
        $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
        $ascii = $ascii === false ? $value : $ascii;
        $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii) ?? '', '-'));
        return $slug !== '' ? $slug : hash('sha256', $value);
    }
}
