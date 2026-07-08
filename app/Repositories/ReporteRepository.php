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
        [$wherePagos, $paramsPagos] = $this->rangoFechas('p.created_at', $inicio, $fin, 'p_');
        [$whereVentas, $paramsVentas] = $this->rangoFechas('v.created_at', $inicio, $fin, 'v_');

        return $this->fetchAll(
            "SELECT fecha, usuario, metodo, COUNT(*) operaciones, SUM(total) total
             FROM (
                SELECT DATE(p.created_at) fecha, u.name usuario, p.metodo, p.monto total
                FROM pagos p
                JOIN users u ON u.id = p.usuario_id
                WHERE p.estado = 'activo' {$wherePagos}
                UNION ALL
                SELECT DATE(v.created_at) fecha, u.name usuario, v.metodo_pago metodo, v.total
                FROM ventas_refacciones v
                JOIN users u ON u.id = v.usuario_id
                WHERE v.estado = 'activa' {$whereVentas}
             ) caja
             GROUP BY fecha, usuario, metodo
             ORDER BY fecha DESC, usuario, metodo",
            array_merge($paramsPagos, $paramsVentas)
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
        [$whereOrdenes, $paramsOrdenes] = $this->rangoFechas('ro.created_at', $inicio, $fin, 'ro_');
        [$whereVentas, $paramsVentas] = $this->rangoFechas('vi.created_at', $inicio, $fin, 'vi_');

        return $this->fetchAll(
            "SELECT sku, nombre, categoria, SUM(cantidad_usada) cantidad_usada,
                    SUM(venta_total) venta_total,
                    SUM(costo_total) costo_total,
                    SUM(utilidad_estimada) utilidad_estimada
             FROM (
                SELECT r.sku, r.nombre, r.categoria, SUM(ro.cantidad) cantidad_usada,
                        SUM(ro.cantidad * ro.precio_unitario) venta_total,
                        SUM(ro.cantidad * r.costo) costo_total,
                        SUM(ro.cantidad * (ro.precio_unitario - r.costo)) utilidad_estimada
                 FROM refacciones_ordenes ro
                 JOIN refacciones r ON r.id = ro.refaccion_id
                 WHERE ro.estado = 'activa' {$whereOrdenes}
                 GROUP BY r.id, r.sku, r.nombre, r.categoria
                 UNION ALL
                 SELECT vi.sku, vi.descripcion nombre, r.categoria, SUM(vi.cantidad) cantidad_usada,
                        SUM(vi.subtotal) venta_total,
                        SUM(vi.cantidad * vi.costo_unitario) costo_total,
                        SUM(vi.subtotal - (vi.cantidad * vi.costo_unitario)) utilidad_estimada
                 FROM venta_refaccion_items vi
                 JOIN ventas_refacciones v ON v.id = vi.venta_id
                 JOIN refacciones r ON r.id = vi.refaccion_id
                 WHERE v.estado = 'activa' {$whereVentas}
                 GROUP BY vi.refaccion_id, vi.sku, vi.descripcion, r.categoria
             ) refacciones
             GROUP BY sku, nombre, categoria
             ORDER BY cantidad_usada DESC, venta_total DESC",
            array_merge($paramsOrdenes, $paramsVentas)
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

    private function rangoFechas(string $campo, ?string $inicio, ?string $fin, string $prefix = ''): array
    {
        $where = '';
        $params = [];

        if ($inicio && preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio)) {
            $where .= " AND DATE({$campo}) >= :{$prefix}inicio";
            $params[$prefix . 'inicio'] = $inicio;
        }
        if ($fin && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
            $where .= " AND DATE({$campo}) <= :{$prefix}fin";
            $params[$prefix . 'fin'] = $fin;
        }

        return [$where, $params];
    }
}
