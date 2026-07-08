<?php

declare(strict_types=1);

namespace App\Repositories;

final class ReporteRepository extends BaseRepository
{
    public function ordenesPorEstado(): array
    {
        return $this->fetchAll("SELECT estado, COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL GROUP BY estado ORDER BY total DESC");
    }

    public function ordenesPorTecnico(): array
    {
        return $this->fetchAll(
            "SELECT COALESCE(u.name, 'Sin asignar') tecnico, COUNT(*) total
             FROM ordenes_servicio o LEFT JOIN users u ON u.id = o.tecnico_id
             WHERE o.deleted_at IS NULL GROUP BY tecnico ORDER BY total DESC"
        );
    }

    public function corteCajaResumen(?string $inicio, ?string $fin): array
    {
        [$where, $params] = $this->rangoFechas('p.created_at', $inicio, $fin);

        return $this->fetchAll(
            "SELECT DATE(p.created_at) fecha, u.name usuario, p.metodo, COUNT(*) operaciones, SUM(p.monto) total
             FROM pagos p
             JOIN users u ON u.id = p.usuario_id
             WHERE p.estado = 'activo' {$where}
             GROUP BY DATE(p.created_at), u.id, u.name, p.metodo
             ORDER BY fecha DESC, usuario, p.metodo",
            $params
        );
    }

    public function saldosPendientes(?string $inicio, ?string $fin): array
    {
        [$where, $params] = $this->rangoFechas('o.fecha_recepcion', $inicio, $fin);

        return $this->fetchAll(
            "SELECT o.folio, c.nombre_completo cliente, c.telefono, o.estado, o.fecha_recepcion,
                    o.costo_final, o.anticipo, o.saldo_pendiente
             FROM ordenes_servicio o
             JOIN clientes c ON c.id = o.cliente_id
             WHERE o.deleted_at IS NULL
               AND o.estado NOT IN ('entregada','cancelada')
               AND o.saldo_pendiente > 0
               {$where}
             ORDER BY o.saldo_pendiente DESC, o.fecha_recepcion ASC",
            $params
        );
    }

    public function refaccionesMasUsadas(?string $inicio, ?string $fin): array
    {
        [$where, $params] = $this->rangoFechas('ro.created_at', $inicio, $fin);

        return $this->fetchAll(
            "SELECT r.sku, r.nombre, r.categoria, SUM(ro.cantidad) cantidad_usada,
                    SUM(ro.cantidad * ro.precio_unitario) venta_total,
                    SUM(ro.cantidad * r.costo) costo_total,
                    SUM(ro.cantidad * (ro.precio_unitario - r.costo)) utilidad_estimada
             FROM refacciones_ordenes ro
             JOIN refacciones r ON r.id = ro.refaccion_id
             WHERE ro.estado = 'activa' {$where}
             GROUP BY r.id, r.sku, r.nombre, r.categoria
             ORDER BY cantidad_usada DESC, venta_total DESC",
            $params
        );
    }

    public function utilidadEstimada(?string $inicio, ?string $fin): array
    {
        [$where, $params] = $this->rangoFechas('o.fecha_recepcion', $inicio, $fin);

        return $this->fetchAll(
            "SELECT o.folio, c.nombre_completo cliente, o.estado, o.fecha_recepcion,
                    o.costo_final total_orden,
                    COALESCE(SUM(CASE WHEN ro.estado = 'activa' THEN ro.cantidad * ro.precio_unitario ELSE 0 END), 0) venta_refacciones,
                    COALESCE(SUM(CASE WHEN ro.estado = 'activa' THEN ro.cantidad * r.costo ELSE 0 END), 0) costo_refacciones,
                    GREATEST(o.costo_final - COALESCE(SUM(CASE WHEN ro.estado = 'activa' THEN ro.cantidad * ro.precio_unitario ELSE 0 END), 0), 0) mano_obra_estimada,
                    (GREATEST(o.costo_final - COALESCE(SUM(CASE WHEN ro.estado = 'activa' THEN ro.cantidad * ro.precio_unitario ELSE 0 END), 0), 0)
                     + COALESCE(SUM(CASE WHEN ro.estado = 'activa' THEN ro.cantidad * (ro.precio_unitario - r.costo) ELSE 0 END), 0)) utilidad_estimada
             FROM ordenes_servicio o
             JOIN clientes c ON c.id = o.cliente_id
             LEFT JOIN refacciones_ordenes ro ON ro.orden_id = o.id
             LEFT JOIN refacciones r ON r.id = ro.refaccion_id
             WHERE o.deleted_at IS NULL
               AND o.estado <> 'cancelada'
               {$where}
             GROUP BY o.id, o.folio, c.nombre_completo, o.estado, o.fecha_recepcion, o.costo_final
             ORDER BY utilidad_estimada DESC, o.fecha_recepcion DESC",
            $params
        );
    }

    private function rangoFechas(string $campo, ?string $inicio, ?string $fin): array
    {
        $where = '';
        $params = [];

        if ($inicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
            $where .= " AND DATE({$campo}) >= :inicio";
            $params['inicio'] = $inicio;
        }
        if ($fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
            $where .= " AND DATE({$campo}) <= :fin";
            $params['fin'] = $fin;
        }

        return [$where, $params];
    }
}
