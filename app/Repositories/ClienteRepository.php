<?php

declare(strict_types=1);

namespace App\Repositories;

final class ClienteRepository extends BaseRepository
{
    public function search(string $term = ''): array
    {
        /*
         * Busca clientes por campos frecuentes. Fuente: texto de filtros/listados.
         * Destino: listado de clientes o seleccion en orden rapida.
         * Cada LIKE usa placeholder propio para evitar HY093 y mantener prepared statements.
         */
        $sql = "SELECT * FROM clientes WHERE deleted_at IS NULL";
        $params = [];
        if ($term !== '') {
            $search = '%' . trim($term) . '%';
            $sql .= " AND (
                nombre_completo LIKE :term_nombre
                OR telefono LIKE :term_telefono
                OR whatsapp LIKE :term_whatsapp
                OR email LIKE :term_email
                OR ciudad LIKE :term_ciudad
                OR estado LIKE :term_estado
                OR rfc LIKE :term_rfc
                OR id = :id
            )";
            $params['term_nombre'] = $search;
            $params['term_telefono'] = $search;
            $params['term_whatsapp'] = $search;
            $params['term_email'] = $search;
            $params['term_ciudad'] = $search;
            $params['term_estado'] = $search;
            $params['term_rfc'] = $search;
            $params['id'] = ctype_digit($term) ? (int) $term : 0;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 100";

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM clientes WHERE id = :id AND deleted_at IS NULL', ['id' => $id]);
    }

    public function findDuplicate(string $telefono, ?string $email, ?int $ignoreId = null): ?array
    {
        // Controla duplicados antes de crear/editar; evita clientes repetidos por telefono/email.
        $sql = 'SELECT * FROM clientes WHERE deleted_at IS NULL AND (telefono = :telefono';
        $params = ['telefono' => $telefono];
        if ($email) {
            $sql .= ' OR email = :email';
            $params['email'] = $email;
        }
        $sql .= ')';
        if ($ignoreId) {
            $sql .= ' AND id <> :id';
            $params['id'] = $ignoreId;
        }
        $sql .= ' LIMIT 1';

        return $this->fetch($sql, $params);
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO clientes (nombre_completo, telefono, whatsapp, email, domicilio, ciudad, estado, codigo_postal, rfc, notas_internas, estatus)
             VALUES (:nombre_completo, :telefono, :whatsapp, :email, :domicilio, :ciudad, :estado, :codigo_postal, :rfc, :notas_internas, :estatus)",
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            "UPDATE clientes SET nombre_completo = :nombre_completo, telefono = :telefono, whatsapp = :whatsapp, email = :email,
             domicilio = :domicilio, ciudad = :ciudad, estado = :estado, codigo_postal = :codigo_postal, rfc = :rfc,
             notas_internas = :notas_internas, estatus = :estatus WHERE id = :id",
            $data
        );
    }

    public function historial(int $clienteId): array
    {
        return $this->fetchAll(
            "SELECT o.*, e.tipo, e.marca, e.modelo
             FROM ordenes_servicio o
             JOIN equipos e ON e.id = o.equipo_id
             WHERE o.cliente_id = :cliente_id AND o.deleted_at IS NULL
             ORDER BY o.fecha_recepcion DESC",
            ['cliente_id' => $clienteId]
        );
    }
}
