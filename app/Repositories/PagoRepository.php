<?php

declare(strict_types=1);

namespace App\Repositories;

final class PagoRepository extends BaseRepository
{
    public function forOrder(int $ordenId): array
    {
        return $this->fetchAll(
            "SELECT p.*, u.name usuario_nombre, cu.name cancelado_por_nombre
             FROM pagos p
             JOIN users u ON u.id = p.usuario_id
             LEFT JOIN users cu ON cu.id = p.cancelado_por
             WHERE p.orden_id = :orden_id
             ORDER BY p.created_at DESC",
            ['orden_id' => $ordenId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT p.*, o.folio, o.estado orden_estado, o.costo_final, o.saldo_pendiente
             FROM pagos p
             JOIN ordenes_servicio o ON o.id = p.orden_id
             WHERE p.id = :id",
            ['id' => $id]
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

    public function cancel(int $id, int $userId, string $motivo): void
    {
        $this->execute(
            "UPDATE pagos
             SET estado = 'cancelado',
                 motivo_cancelacion = :motivo,
                 cancelado_por = :cancelado_por,
                 cancelado_at = NOW()
             WHERE id = :id AND estado = 'activo'",
            ['id' => $id, 'cancelado_por' => $userId, 'motivo' => $motivo]
        );
    }

    public function totalHoy(): float
    {
        $row = $this->fetch(
            "SELECT
                (SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE estado = 'activo' AND DATE(created_at) = CURDATE())
                +
                (SELECT COALESCE(SUM(total), 0) FROM ventas_refacciones WHERE estado = 'activa' AND DATE(created_at) = CURDATE())
                total"
        );
        return (float) ($row['total'] ?? 0);
    }

    public function porPeriodo(?string $inicio, ?string $fin): array
    {
        $params = [];
        $wherePagos = '';
        $whereVentas = '';
        if ($inicio) {
            $wherePagos .= " AND DATE(p.created_at) >= :inicio";
            $whereVentas .= " AND DATE(v.created_at) >= :inicio";
            $params['inicio'] = $inicio;
        }
        if ($fin) {
            $wherePagos .= " AND DATE(p.created_at) <= :fin";
            $whereVentas .= " AND DATE(v.created_at) <= :fin";
            $params['fin'] = $fin;
        }
        $sql = "SELECT *
                FROM (
                    SELECT p.id, p.orden_id, p.monto, p.metodo, p.referencia, p.usuario_id, p.notas,
                           p.estado, p.created_at, o.folio, u.name usuario_nombre, 'orden' origen
                    FROM pagos p
                    JOIN ordenes_servicio o ON o.id = p.orden_id
                    JOIN users u ON u.id = p.usuario_id
                    WHERE p.estado = 'activo' {$wherePagos}
                    UNION ALL
                    SELECT v.id, NULL orden_id, v.total monto, v.metodo_pago metodo, v.referencia, v.usuario_id, v.notas,
                           v.estado, v.created_at, v.folio, u.name usuario_nombre, 'punto_venta' origen
                    FROM ventas_refacciones v
                    JOIN users u ON u.id = v.usuario_id
                    WHERE v.estado = 'activa' {$whereVentas}
                ) caja
                ORDER BY created_at DESC";
        return $this->fetchAll($sql, $params);
    }
}
