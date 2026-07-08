<?php

declare(strict_types=1);

namespace App\Repositories;

final class InventarioRepository extends BaseRepository
{
    public function stockBajo(): array
    {
        return $this->fetchAll("SELECT * FROM refacciones WHERE deleted_at IS NULL AND stock_actual <= stock_minimo AND estatus = 'activo' ORDER BY stock_actual ASC");
    }

    public function all(array $filtros = []): array
    {
        $params = [];
        $sql = "SELECT r.*, p.nombre proveedor_nombre
                FROM refacciones r
                LEFT JOIN proveedores p ON p.id = r.proveedor_id
                WHERE r.deleted_at IS NULL";

        if (!empty($filtros['q'])) {
            $sql .= " AND (r.nombre LIKE :q_nombre OR r.sku LIKE :q_sku OR r.categoria LIKE :q_cat OR r.marca LIKE :q_marca)";
            $like = '%' . trim((string) $filtros['q']) . '%';
            $params['q_nombre'] = $like;
            $params['q_sku'] = $like;
            $params['q_cat'] = $like;
            $params['q_marca'] = $like;
        }
        if (!empty($filtros['solo_bajo'])) {
            $sql .= " AND r.stock_actual <= r.stock_minimo AND r.estatus = 'activo'";
        }

        $sql .= " ORDER BY r.nombre LIMIT 300";
        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT r.*, p.nombre proveedor_nombre
             FROM refacciones r
             LEFT JOIN proveedores p ON p.id = r.proveedor_id
             WHERE r.id = :id AND r.deleted_at IS NULL",
            ['id' => $id]
        );
    }

    public function findForUpdate(int $id): ?array
    {
        return $this->fetch(
            "SELECT r.*, p.nombre proveedor_nombre
             FROM refacciones r
             LEFT JOIN proveedores p ON p.id = r.proveedor_id
             WHERE r.id = :id AND r.deleted_at IS NULL
             FOR UPDATE",
            ['id' => $id]
        );
    }

    public function buscarParaVenta(string $query, int $limite = 12): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $limite = max(1, min(30, $limite));
        $like = '%' . $query . '%';
        return $this->fetchAll(
            "SELECT id, nombre, sku, categoria, marca, modelo_compatible, precio_venta, stock_actual, estatus,
                    CASE WHEN LOWER(sku) = LOWER(:sku_exact) THEN 1 ELSE 0 END exact_sku
             FROM refacciones
             WHERE deleted_at IS NULL
               AND estatus = 'activo'
               AND (sku = :sku OR nombre LIKE :q_nombre OR categoria LIKE :q_categoria OR marca LIKE :q_marca OR modelo_compatible LIKE :q_modelo)
             ORDER BY exact_sku DESC, nombre
             LIMIT {$limite}",
            [
                'sku_exact' => $query,
                'sku' => $query,
                'q_nombre' => $like,
                'q_categoria' => $like,
                'q_marca' => $like,
                'q_modelo' => $like,
            ]
        );
    }

    public function skuExists(string $sku, ?int $exceptId = null): bool
    {
        // Placeholders independientes: con prepares nativos no se puede reutilizar
        // el mismo nombre (:id) dos veces en la consulta.
        $row = $this->fetch(
            'SELECT id FROM refacciones WHERE sku = :sku AND (:id_a IS NULL OR id <> :id_b) LIMIT 1',
            ['sku' => $sku, 'id_a' => $exceptId, 'id_b' => $exceptId]
        );

        return $row !== null;
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO refacciones
             (proveedor_id, nombre, sku, categoria, marca, modelo_compatible, costo, precio_venta, stock_actual, stock_minimo, ubicacion, estatus)
             VALUES
             (:proveedor_id, :nombre, :sku, :categoria, :marca, :modelo_compatible, :costo, :precio_venta, :stock_actual, :stock_minimo, :ubicacion, :estatus)",
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            "UPDATE refacciones SET proveedor_id = :proveedor_id, nombre = :nombre, sku = :sku, categoria = :categoria,
                    marca = :marca, modelo_compatible = :modelo_compatible, costo = :costo, precio_venta = :precio_venta,
                    stock_minimo = :stock_minimo, ubicacion = :ubicacion, estatus = :estatus
             WHERE id = :id",
            $data
        );
    }

    public function setStock(int $id, int $stock): void
    {
        $this->execute('UPDATE refacciones SET stock_actual = :stock WHERE id = :id', ['stock' => $stock, 'id' => $id]);
    }

    public function registrarUsoOrden(array $data): int
    {
        return $this->insert(
            "INSERT INTO refacciones_ordenes (orden_id, refaccion_id, cotizacion_item_id, cantidad, precio_unitario)
             VALUES (:orden_id, :refaccion_id, :cotizacion_item_id, :cantidad, :precio_unitario)",
            $data
        );
    }

    public function usosPorOrden(int $ordenId): array
    {
        return $this->fetchAll(
            "SELECT ro.*, r.nombre, r.sku, r.costo, r.precio_venta, r.stock_actual, u.name cancelado_por_nombre,
                    ci.descripcion cotizacion_descripcion
             FROM refacciones_ordenes ro
             JOIN refacciones r ON r.id = ro.refaccion_id
             LEFT JOIN users u ON u.id = ro.cancelado_por
             LEFT JOIN cotizacion_items ci ON ci.id = ro.cotizacion_item_id
             WHERE ro.orden_id = :orden_id
             ORDER BY ro.id DESC",
            ['orden_id' => $ordenId]
        );
    }

    public function usoOrdenForUpdate(int $id): ?array
    {
        return $this->fetch(
            "SELECT ro.*, r.nombre, r.sku, r.costo, r.precio_venta
             FROM refacciones_ordenes ro
             JOIN refacciones r ON r.id = ro.refaccion_id
             WHERE ro.id = :id
             FOR UPDATE",
            ['id' => $id]
        );
    }

    public function cancelarUsoOrden(int $id, string $motivo, int $usuarioId): void
    {
        $this->execute(
            "UPDATE refacciones_ordenes
             SET estado = 'cancelada', motivo_cancelacion = :motivo, cancelado_por = :usuario_id, cancelado_at = NOW()
             WHERE id = :id AND estado = 'activa'",
            ['id' => $id, 'motivo' => $motivo, 'usuario_id' => $usuarioId]
        );
    }

    public function registrarMovimiento(array $data): int
    {
        $data['venta_refaccion_id'] = $data['venta_refaccion_id'] ?? null;
        return $this->insert(
            "INSERT INTO inventario_movimientos
             (refaccion_id, orden_id, venta_refaccion_id, usuario_id, tipo, cantidad, motivo, costo_unitario, stock_anterior, stock_nuevo)
             VALUES
             (:refaccion_id, :orden_id, :venta_refaccion_id, :usuario_id, :tipo, :cantidad, :motivo, :costo_unitario, :stock_anterior, :stock_nuevo)",
            $data
        );
    }

    public function movimientos(int $refaccionId, int $limite = 50): array
    {
        $limite = max(1, min(200, $limite));
        return $this->fetchAll(
            "SELECT m.*, u.name usuario_nombre
             FROM inventario_movimientos m
             LEFT JOIN users u ON u.id = m.usuario_id
             WHERE m.refaccion_id = :id
             ORDER BY m.id DESC
             LIMIT {$limite}",
            ['id' => $refaccionId]
        );
    }
}
