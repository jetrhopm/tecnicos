<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ClienteRepository;
use RuntimeException;

final class ClienteService
{
    public function __construct(
        private readonly ClienteRepository $clientes = new ClienteRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(string $term = ''): array
    {
        return $this->clientes->search($term);
    }

    public function obtener(int $id): ?array
    {
        return $this->clientes->find($id);
    }

    public function guardar(array $data, ?int $id = null): int
    {
        $payload = $this->normalizar($data);

        // Validacion minima en el servicio: la API llama directo aqui sin
        // pasar por ClienteValidator, y no deben existir clientes sin datos.
        if ($payload['nombre_completo'] === '') {
            throw new RuntimeException('El nombre del cliente es obligatorio.');
        }
        if ($payload['telefono'] === '') {
            throw new RuntimeException('El telefono del cliente es obligatorio.');
        }
        if ($payload['email'] !== null && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El email del cliente no es valido.');
        }

        if ($id) {
            $anterior = $this->clientes->find($id);
            if (!$anterior) {
                throw new RuntimeException('Cliente no encontrado.');
            }

            $telefonoAnterior = normalizarTelefono((string) ($anterior['telefono'] ?? ''));
            $whatsappAnterior = normalizarTelefono((string) ($anterior['whatsapp'] ?? ''));
            $whatsappEnviado = normalizarTelefono((string) ($data['whatsapp'] ?? ''));
            $usuarioNoCambioWhatsapp = $whatsappEnviado === '' || $whatsappEnviado === $whatsappAnterior || $whatsappEnviado === $telefonoAnterior;

            if ($payload['telefono'] !== $telefonoAnterior && $usuarioNoCambioWhatsapp) {
                $payload['whatsapp'] = $payload['telefono'];
            }

            $duplicado = $this->clientes->findDuplicate($payload['telefono'], $payload['email'], $id);
            if ($duplicado) {
                throw new RuntimeException('Ya existe un cliente con ese telefono o email.');
            }

            $this->clientes->update($id, $payload);
            $this->auditoria->registrar('editar', 'clientes', $id, $anterior, $payload);
            return $id;
        }

        $duplicado = $this->clientes->findDuplicate($payload['telefono'], $payload['email'], null);
        if ($duplicado) {
            throw new RuntimeException('Ya existe un cliente con ese telefono o email.');
        }

        $newId = $this->clientes->create($payload);
        $this->auditoria->registrar('crear', 'clientes', $newId, null, $payload);
        return $newId;
    }

    public function historial(int $clienteId): array
    {
        return $this->clientes->historial($clienteId);
    }

    private function normalizar(array $data): array
    {
        $estatus = $data['estatus'] ?? 'activo';

        return [
            'nombre_completo' => trim((string) ($data['nombre_completo'] ?? '')),
            'telefono' => normalizarTelefono((string) ($data['telefono'] ?? '')),
            'whatsapp' => normalizarTelefono((string) ($data['whatsapp'] ?? $data['telefono'] ?? '')),
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'domicilio' => trim((string) ($data['domicilio'] ?? '')) ?: null,
            'ciudad' => trim((string) ($data['ciudad'] ?? '')) ?: null,
            'estado' => trim((string) ($data['estado'] ?? '')) ?: null,
            'codigo_postal' => trim((string) ($data['codigo_postal'] ?? '')) ?: null,
            'rfc' => strtoupper(trim((string) ($data['rfc'] ?? ''))) ?: null,
            'notas_internas' => trim((string) ($data['notas_internas'] ?? '')) ?: null,
            'estatus' => in_array($estatus, ['activo', 'inactivo'], true) ? $estatus : 'activo',
        ];
    }
}
