<?php

declare(strict_types=1);

namespace App\Repositories;

final class CajaRepository extends BaseRepository
{
    public function turnoAbierto(): ?array
    {
        return $this->fetch(
            "SELECT t.*, ua.name abierto_por_nombre, uc.name cerrado_por_nombre
             FROM caja_turnos t
             JOIN users ua ON ua.id = t.abierto_por
             LEFT JOIN users uc ON uc.id = t.cerrado_por
             WHERE t.estado = 'abierto'
             ORDER BY t.id DESC
             LIMIT 1"
        );
    }

    public function turnoAbiertoForUpdate(): ?array
    {
        return $this->fetch(
            "SELECT *
             FROM caja_turnos
             WHERE estado = 'abierto'
             ORDER BY id DESC
             LIMIT 1
             FOR UPDATE"
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT t.*, ua.name abierto_por_nombre, uc.name cerrado_por_nombre
             FROM caja_turnos t
             JOIN users ua ON ua.id = t.abierto_por
             LEFT JOIN users uc ON uc.id = t.cerrado_por
             WHERE t.id = :id",
            ['id' => $id]
        );
    }

    public function recientes(int $limit = 8): array
    {
        $limit = max(1, min(30, $limit));
        return $this->fetchAll(
            "SELECT t.*, ua.name abierto_por_nombre, uc.name cerrado_por_nombre
             FROM caja_turnos t
             JOIN users ua ON ua.id = t.abierto_por
             LEFT JOIN users uc ON uc.id = t.cerrado_por
             ORDER BY t.id DESC
             LIMIT {$limit}"
        );
    }

    public function crearTurno(array $data): int
    {
        return $this->insert(
            "INSERT INTO caja_turnos (folio, fondo_inicial, abierto_por, opened_at)
             VALUES (:folio, :fondo_inicial, :abierto_por, NOW())",
            $data
        );
    }

    public function cerrarTurno(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            "UPDATE caja_turnos
             SET estado = 'cerrado',
                 efectivo_contado = :efectivo_contado,
                 transferencia_contado = :transferencia_contado,
                 tarjeta_contado = :tarjeta_contado,
                 otro_contado = :otro_contado,
                 total_esperado = :total_esperado,
                 total_contado = :total_contado,
                 diferencia = :diferencia,
                 cerrado_por = :cerrado_por,
                 observaciones = :observaciones,
                 closed_at = NOW()
             WHERE id = :id",
            $data
        );
    }

    public function registrarMovimiento(array $data): int
    {
        return $this->insert(
            "INSERT INTO caja_movimientos (turno_id, tipo, monto, metodo, concepto, usuario_id)
             VALUES (:turno_id, :tipo, :monto, :metodo, :concepto, :usuario_id)",
            $data
        );
    }

    public function movimientos(int $turnoId): array
    {
        return $this->fetchAll(
            "SELECT m.*, u.name usuario_nombre
             FROM caja_movimientos m
             JOIN users u ON u.id = m.usuario_id
             WHERE m.turno_id = :turno_id
             ORDER BY m.created_at DESC, m.id DESC",
            ['turno_id' => $turnoId]
        );
    }

    public function resumenIngresos(string $desde, ?string $hasta = null): array
    {
        $hasta = $hasta ?: date('Y-m-d H:i:s');
        return $this->fetchAll(
            "SELECT metodo, COUNT(*) operaciones, SUM(total) total
             FROM (
                SELECT p.metodo, p.monto total
                FROM pagos p
                WHERE p.estado = 'activo'
                  AND p.created_at >= :p_desde
                  AND p.created_at <= :p_hasta
                UNION ALL
                SELECT v.metodo_pago metodo, v.total
                FROM ventas_refacciones v
                WHERE v.estado = 'activa'
                  AND v.created_at >= :v_desde
                  AND v.created_at <= :v_hasta
             ) ingresos
             GROUP BY metodo
             ORDER BY metodo",
            [
                'p_desde' => $desde,
                'p_hasta' => $hasta,
                'v_desde' => $desde,
                'v_hasta' => $hasta,
            ]
        );
    }

    public function operaciones(string $desde, ?string $hasta = null): array
    {
        $hasta = $hasta ?: date('Y-m-d H:i:s');
        return $this->fetchAll(
            "SELECT *
             FROM (
                SELECT p.id, o.folio, p.monto, p.metodo, p.referencia, p.created_at, u.name usuario_nombre, 'orden' origen
                FROM pagos p
                JOIN ordenes_servicio o ON o.id = p.orden_id
                JOIN users u ON u.id = p.usuario_id
                WHERE p.estado = 'activo'
                  AND p.created_at >= :p_desde
                  AND p.created_at <= :p_hasta
                UNION ALL
                SELECT v.id, v.folio, v.total monto, v.metodo_pago metodo, v.referencia, v.created_at, u.name usuario_nombre, 'punto_venta' origen
                FROM ventas_refacciones v
                JOIN users u ON u.id = v.usuario_id
                WHERE v.estado = 'activa'
                  AND v.created_at >= :v_desde
                  AND v.created_at <= :v_hasta
             ) caja
             ORDER BY created_at DESC, id DESC",
            [
                'p_desde' => $desde,
                'p_hasta' => $hasta,
                'v_desde' => $desde,
                'v_hasta' => $hasta,
            ]
        );
    }

    public function totalRetiros(int $turnoId): float
    {
        $row = $this->fetch(
            "SELECT COALESCE(SUM(monto), 0) total
             FROM caja_movimientos
             WHERE turno_id = :turno_id AND tipo = 'retiro'",
            ['turno_id' => $turnoId]
        );

        return (float) ($row['total'] ?? 0);
    }

    public function existsFolio(string $folio): bool
    {
        return $this->fetch('SELECT id FROM caja_turnos WHERE folio = :folio LIMIT 1', ['folio' => $folio]) !== null;
    }
}
