<?php

declare(strict_types=1);

namespace App\Repositories;

final class OrdenRepository extends BaseRepository
{
    public function all(array $filters = []): array
    {
        /*
         * Listado principal de ordenes. Fuente: filtros GET o buscadores internos.
         * Destino: tabla de ordenes/dashboard. Une cliente, equipo y tecnico para no
         * exponer consultas SQL en la vista.
         */
        $params = [];
        $sql = "SELECT o.*, c.nombre_completo cliente_nombre, c.telefono cliente_telefono, c.whatsapp cliente_whatsapp,
                       e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo, u.name tecnico_nombre
                FROM ordenes_servicio o
                JOIN clientes c ON c.id = o.cliente_id
                JOIN equipos e ON e.id = o.equipo_id
                LEFT JOIN users u ON u.id = o.tecnico_id
                WHERE o.deleted_at IS NULL";

        foreach (['estado', 'prioridad', 'tecnico_id'] as $key) {
            if (!empty($filters[$key])) {
                $sql .= " AND o.$key = :$key";
                $params[$key] = $filters[$key];
            }
        }
        if (!empty($filters['q'])) {
            // Placeholders independientes: PDO/MySQL puede fallar si se reutiliza el mismo nombre en varios LIKE.
            $search = '%' . trim((string) $filters['q']) . '%';
            $sql .= " AND (
                o.folio LIKE :q_folio
                OR o.codigo_entrega LIKE :q_codigo_entrega
                OR e.numero_serie LIKE :q_numero_serie
                OR e.imei LIKE :q_imei
                OR c.nombre_completo LIKE :q_cliente
                OR c.telefono LIKE :q_telefono
            )";
            $params['q_folio'] = $search;
            $params['q_codigo_entrega'] = $search;
            $params['q_numero_serie'] = $search;
            $params['q_imei'] = $search;
            $params['q_cliente'] = $search;
            $params['q_telefono'] = $search;
        }

        $sql .= " ORDER BY o.fecha_recepcion DESC LIMIT 150";
        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            "SELECT o.*, c.nombre_completo cliente_nombre, c.telefono cliente_telefono, c.whatsapp cliente_whatsapp, c.email cliente_email,
                    c.domicilio cliente_domicilio,
                    e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo, e.numero_serie, e.imei,
                    e.password_equipo, e.color equipo_color, e.accesorios_recibidos, e.estado_fisico equipo_estado_fisico,
                    e.observaciones equipo_observaciones, u.name tecnico_nombre
             FROM ordenes_servicio o
             JOIN clientes c ON c.id = o.cliente_id
             JOIN equipos e ON e.id = o.equipo_id
             LEFT JOIN users u ON u.id = o.tecnico_id
             WHERE o.id = :id AND o.deleted_at IS NULL",
            ['id' => $id]
        );
    }

    public function findByPublicToken(string $folio, string $token): ?array
    {
        // Portal publico: requiere folio + token para no mostrar ordenes solo adivinando folios.
        return $this->fetch(
            "SELECT o.*, c.nombre_completo cliente_nombre, c.telefono cliente_telefono,
                    e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo
             FROM ordenes_servicio o
             JOIN clientes c ON c.id = o.cliente_id
             JOIN equipos e ON e.id = o.equipo_id
             WHERE o.folio = :folio AND o.token_publico = :token AND o.deleted_at IS NULL",
            ['folio' => $folio, 'token' => $token]
        );
    }

    public function findByDeliveryCode(string $code): ?array
    {
        // Entregas: solo acepta la clave de entrega impresa en la nota del cliente.
        // El folio queda fuera a proposito: es visible/secuencial y no demuestra
        // que el cliente presento su nota.
        return $this->fetch(
            "SELECT o.*, c.nombre_completo cliente_nombre, c.telefono cliente_telefono, c.whatsapp cliente_whatsapp, c.email cliente_email,
                    e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo, e.numero_serie, e.imei, u.name tecnico_nombre
             FROM ordenes_servicio o
             JOIN clientes c ON c.id = o.cliente_id
             JOIN equipos e ON e.id = o.equipo_id
             LEFT JOIN users u ON u.id = o.tecnico_id
             WHERE o.codigo_entrega = :code_delivery AND o.deleted_at IS NULL
             LIMIT 1",
            ['code_delivery' => $code]
        );
    }

    public function updateAcceptance(int $id, string $firmaRecepcion): void
    {
        // Deja constancia en la orden de que el cliente acepto presupuesto/terminos.
        $this->execute(
            'UPDATE ordenes_servicio SET firma_recepcion = :firma WHERE id = :id',
            ['firma' => $firmaRecepcion, 'id' => $id]
        );
    }

    public function deliveryCodeExists(string $code): bool
    {
        $row = $this->fetch(
            'SELECT id FROM ordenes_servicio WHERE codigo_entrega = :codigo LIMIT 1',
            ['codigo' => $code]
        );

        return $row !== null;
    }

    public function findByLookupCode(string $code): ?array
    {
        return $this->fetch(
            "SELECT o.id
             FROM ordenes_servicio o
             JOIN equipos e ON e.id = o.equipo_id
             WHERE (o.folio = :folio OR o.codigo_entrega = :codigo_entrega OR e.numero_serie = :serie OR e.imei = :imei)
               AND o.deleted_at IS NULL
             LIMIT 1",
            ['folio' => $code, 'codigo_entrega' => $code, 'serie' => $code, 'imei' => $code]
        );
    }

    public function nextConsecutiveForDate(string $date): int
    {
        $row = $this->fetch(
            "SELECT COUNT(*) total FROM ordenes_servicio WHERE DATE(fecha_recepcion) = :date",
            ['date' => $date]
        );

        return ((int) ($row['total'] ?? 0)) + 1;
    }

    public function create(array $data): int
    {
        // Insercion central de ordenes. El service prepara folio, token, saldos y estado antes de llegar aqui.
        return $this->insert(
            "INSERT INTO ordenes_servicio
             (folio, cliente_id, equipo_id, tecnico_id, recibido_por, tipo_servicio, falla_reportada, diagnostico_inicial, prioridad,
              estado, fecha_estimada_entrega, costo_estimado, costo_final, anticipo, saldo_pendiente, garantia_ofrecida,
              observaciones_internas, observaciones_cliente, codigo_entrega, ubicacion_actual, token_publico)
             VALUES
             (:folio, :cliente_id, :equipo_id, :tecnico_id, :recibido_por, :tipo_servicio, :falla_reportada, :diagnostico_inicial, :prioridad,
              :estado, :fecha_estimada_entrega, :costo_estimado, :costo_final, :anticipo, :saldo_pendiente, :garantia_ofrecida,
              :observaciones_internas, :observaciones_cliente, :codigo_entrega, :ubicacion_actual, :token_publico)",
            $data
        );
    }

    public function updateState(int $id, string $estado, ?string $fechaEntrega = null): void
    {
        $sql = 'UPDATE ordenes_servicio SET estado = :estado';
        $params = ['id' => $id, 'estado' => $estado];
        if ($fechaEntrega) {
            $sql .= ', fecha_real_entrega = :fecha_entrega';
            $params['fecha_entrega'] = $fechaEntrega;
        }
        $sql .= ' WHERE id = :id';
        $this->execute($sql, $params);
    }

    public function assignTechnician(int $id, ?int $tecnicoId): void
    {
        $this->execute('UPDATE ordenes_servicio SET tecnico_id = :tecnico_id WHERE id = :id', ['id' => $id, 'tecnico_id' => $tecnicoId]);
    }

    public function updateTotals(int $id, float $total, float $pagos): void
    {
        $this->execute(
            'UPDATE ordenes_servicio SET costo_final = :total, saldo_pendiente = :saldo, anticipo = :pagos WHERE id = :id',
            ['id' => $id, 'total' => $total, 'saldo' => calcularSaldo($total, $pagos), 'pagos' => $pagos]
        );
    }

    public function ensureDeliveryCodes(): void
    {
        $rows = $this->fetchAll("SELECT id, folio FROM ordenes_servicio WHERE codigo_entrega IS NULL OR codigo_entrega = ''");
        foreach ($rows as $row) {
            $this->execute(
                'UPDATE ordenes_servicio SET codigo_entrega = :code WHERE id = :id',
                ['id' => (int) $row['id'], 'code' => 'ENT-' . strtoupper((string) $row['folio'])]
            );
        }
    }

    public function pagosActivos(int $ordenId): float
    {
        $row = $this->fetch("SELECT COALESCE(SUM(monto), 0) total FROM pagos WHERE orden_id = :id AND estado = 'activo'", ['id' => $ordenId]);
        return (float) ($row['total'] ?? 0);
    }

    public function dashboardStats(): array
    {
        return [
            'abiertas' => (int) ($this->fetch("SELECT COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL AND estado NOT IN ('entregada','cancelada')")['total'] ?? 0),
            'urgentes' => (int) ($this->fetch("SELECT COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL AND prioridad = 'urgente' AND estado NOT IN ('entregada','cancelada')")['total'] ?? 0),
            'esperando_autorizacion' => (int) ($this->fetch("SELECT COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL AND estado = 'esperando_autorizacion'")['total'] ?? 0),
            'en_reparacion' => (int) ($this->fetch("SELECT COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL AND estado = 'en_reparacion'")['total'] ?? 0),
            'listas' => (int) ($this->fetch("SELECT COUNT(*) total FROM ordenes_servicio WHERE deleted_at IS NULL AND estado = 'lista_para_entrega'")['total'] ?? 0),
            'saldo_pendiente' => (float) ($this->fetch("SELECT COALESCE(SUM(saldo_pendiente),0) total FROM ordenes_servicio WHERE deleted_at IS NULL AND estado NOT IN ('entregada','cancelada')")['total'] ?? 0),
        ];
    }
}
