<?php

declare(strict_types=1);

namespace App\Repositories;

final class VentaRefaccionRepository extends BaseRepository
{
    public function existsFolio(string $folio): bool
    {
        return $this->fetch('SELECT id FROM ventas_refacciones WHERE folio = :folio LIMIT 1', ['folio' => $folio]) !== null;
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO ventas_refacciones
             (folio, cliente_nombre, cliente_telefono, subtotal, descuento, total, metodo_pago, referencia, notas, usuario_id)
             VALUES
             (:folio, :cliente_nombre, :cliente_telefono, :subtotal, :descuento, :total, :metodo_pago, :referencia, :notas, :usuario_id)",
            $data
        );
    }

    public function addItem(array $data): int
    {
        return $this->insert(
            "INSERT INTO venta_refaccion_items
             (venta_id, refaccion_id, descripcion, sku, cantidad, costo_unitario, precio_unitario, subtotal)
             VALUES
             (:venta_id, :refaccion_id, :descripcion, :sku, :cantidad, :costo_unitario, :precio_unitario, :subtotal)",
            $data
        );
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT v.*, u.name usuario_nombre
             FROM ventas_refacciones v
             LEFT JOIN users u ON u.id = v.usuario_id
             WHERE v.id = :id",
            ['id' => $id]
        );
    }

    public function items(int $ventaId): array
    {
        return $this->fetchAll(
            "SELECT vi.*, r.stock_actual
             FROM venta_refaccion_items vi
             LEFT JOIN refacciones r ON r.id = vi.refaccion_id
             WHERE vi.venta_id = :id
             ORDER BY vi.id",
            ['id' => $ventaId]
        );
    }

    public function recientes(int $limite = 30): array
    {
        $limite = max(1, min(100, $limite));
        return $this->fetchAll(
            "SELECT v.*, u.name usuario_nombre,
                    (SELECT COUNT(*) FROM venta_refaccion_items vi WHERE vi.venta_id = v.id) total_items
             FROM ventas_refacciones v
             LEFT JOIN users u ON u.id = v.usuario_id
             ORDER BY v.id DESC
             LIMIT {$limite}"
        );
    }
}
