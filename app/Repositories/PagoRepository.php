<?php

declare(strict_types=1);

namespace App\Repositories;

final class PagoRepository extends BaseRepository
{
    public function forOrder(int $ordenId): array
    {
        return $this->fetchAll(
            "SELECT p.*, u.name usuario_nombre FROM pagos p JOIN users u ON u.id = p.usuario_id WHERE p.orden_id = :orden_id ORDER BY p.created_at DESC",
            ['orden_id' => $ordenId]
        );
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO pagos (orden_id, monto, metodo, referencia, usuario_id, notas)
             VALUES (:orden_id, :monto, :metodo, :referencia, :usuario_id, :notas)",
            $data
        );
    }

    public function totalHoy(): float
    {
        $row = $this->fetch("SELECT COALESCE(SUM(monto), 0) total FROM pagos WHERE estado = 'activo' AND DATE(created_at) = CURDATE()");
        return (float) ($row['total'] ?? 0);
    }

    public function porPeriodo(?string $inicio, ?string $fin): array
    {
        $params = [];
        $sql = "SELECT p.*, o.folio, u.name usuario_nombre
                FROM pagos p
                JOIN ordenes_servicio o ON o.id = p.orden_id
                JOIN users u ON u.id = p.usuario_id
                WHERE p.estado = 'activo'";
        if ($inicio) {
            $sql .= " AND DATE(p.created_at) >= :inicio";
            $params['inicio'] = $inicio;
        }
        if ($fin) {
            $sql .= " AND DATE(p.created_at) <= :fin";
            $params['fin'] = $fin;
        }
        $sql .= " ORDER BY p.created_at DESC";
        return $this->fetchAll($sql, $params);
    }
}
