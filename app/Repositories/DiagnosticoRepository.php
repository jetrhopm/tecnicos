<?php

declare(strict_types=1);

namespace App\Repositories;

final class DiagnosticoRepository extends BaseRepository
{
    public function latestForOrder(int $ordenId): ?array
    {
        return $this->fetch('SELECT * FROM diagnosticos WHERE orden_id = :orden_id ORDER BY id DESC LIMIT 1', ['orden_id' => $ordenId]);
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO diagnosticos (orden_id, tecnico_id, diagnostico_tecnico, diagnostico_cliente, causa_probable, pruebas_realizadas, piezas_necesarias, tiempo_estimado, costo_mano_obra, costo_refacciones, costo_total_sugerido)
             VALUES (:orden_id, :tecnico_id, :diagnostico_tecnico, :diagnostico_cliente, :causa_probable, :pruebas_realizadas, :piezas_necesarias, :tiempo_estimado, :costo_mano_obra, :costo_refacciones, :costo_total_sugerido)",
            $data
        );
    }

    public function blockForOrder(int $ordenId): void
    {
        $this->execute('UPDATE diagnosticos SET bloqueado = 1 WHERE orden_id = :orden_id', ['orden_id' => $ordenId]);
    }
}
