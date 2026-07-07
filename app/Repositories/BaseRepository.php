<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        // Lectura de un registro. Toda variable externa debe llegar como parametro PDO, no concatenada al SQL.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        // Lectura de multiples registros para listados/reportes. Devuelve arrays asociativos desde PDO.
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        // Escritura/actualizacion. Prepared statements reducen riesgo de inyeccion SQL.
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function insert(string $sql, array $params = []): int
    {
        // Inserta y regresa el id autoincremental para que el service audite o encadene procesos.
        $this->execute($sql, $params);
        return (int) $this->db->lastInsertId();
    }
}
