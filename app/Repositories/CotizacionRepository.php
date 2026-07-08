<?php

declare(strict_types=1);

namespace App\Repositories;

final class CotizacionRepository extends BaseRepository
{
    public function latestForOrder(int $ordenId): ?array
    {
        return $this->fetch('SELECT * FROM cotizaciones WHERE orden_id = :orden_id ORDER BY version DESC, id DESC LIMIT 1', ['orden_id' => $ordenId]);
    }

    public function latestForOrderForUpdate(int $ordenId): ?array
    {
        return $this->fetch('SELECT * FROM cotizaciones WHERE orden_id = :orden_id ORDER BY version DESC, id DESC LIMIT 1 FOR UPDATE', ['orden_id' => $ordenId]);
    }

    public function findForUpdate(int $id): ?array
    {
        return $this->fetch('SELECT * FROM cotizaciones WHERE id = :id FOR UPDATE', ['id' => $id]);
    }

    public function items(int $cotizacionId): array
    {
        return $this->fetchAll('SELECT * FROM cotizacion_items WHERE cotizacion_id = :id ORDER BY id', ['id' => $cotizacionId]);
    }

    public function nextVersion(int $ordenId): int
    {
        $row = $this->fetch('SELECT COALESCE(MAX(version), 0) + 1 version FROM cotizaciones WHERE orden_id = :orden_id', ['orden_id' => $ordenId]);
        return (int) ($row['version'] ?? 1);
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO cotizaciones (orden_id, version, subtotal, descuento, iva, total, vigencia, terminos, estado, created_by)
             VALUES (:orden_id, :version, :subtotal, :descuento, :iva, :total, :vigencia, :terminos, :estado, :created_by)",
            $data
        );
    }

    public function addItem(array $data): int
    {
        return $this->insert(
            "INSERT INTO cotizacion_items (cotizacion_id, tipo, descripcion, cantidad, precio_unitario, subtotal)
             VALUES (:cotizacion_id, :tipo, :descripcion, :cantidad, :precio_unitario, :subtotal)",
            $data
        );
    }

    public function expirePending(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE cotizaciones SET estado = 'vencida' WHERE id = :id AND estado = 'pendiente'");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() === 1;
    }

    public function changePendingStatus(int $id, string $estado, ?string $motivo = null): bool
    {
        $fields = ['estado = :estado'];
        $params = ['id' => $id, 'estado' => $estado];
        if ($estado === 'aceptada') {
            $fields[] = 'aceptada_at = NOW()';
        }
        if ($estado === 'rechazada') {
            $fields[] = 'rechazada_at = NOW()';
            $fields[] = 'motivo_rechazo = :motivo';
            $params['motivo'] = $motivo;
        }
        $stmt = $this->db->prepare('UPDATE cotizaciones SET ' . implode(', ', $fields) . " WHERE id = :id AND estado = 'pendiente'");
        $stmt->execute($params);
        return $stmt->rowCount() === 1;
    }
}
