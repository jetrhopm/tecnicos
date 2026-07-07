<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ConfiguracionService
{
    public function get(string $clave, mixed $default = null): mixed
    {
        $stmt = Database::connection()->prepare('SELECT valor FROM configuraciones WHERE clave = :clave LIMIT 1');
        $stmt->execute(['clave' => $clave]);
        $row = $stmt->fetch();
        return $row['valor'] ?? $default;
    }

    public function allGrouped(): array
    {
        $rows = Database::connection()->query('SELECT * FROM configuraciones ORDER BY grupo, clave')->fetchAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['grupo']][] = $row;
        }

        return $grouped;
    }

    public function actualizar(array $valores): void
    {
        $db = Database::connection();
        $rows = $db->query('SELECT clave, valor, tipo FROM configuraciones ORDER BY clave')->fetchAll();
        $existentes = [];
        foreach ($rows as $row) {
            $existentes[$row['clave']] = $row;
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare('UPDATE configuraciones SET valor = :valor WHERE clave = :clave');
            $cambios = [];

            foreach ($existentes as $clave => $row) {
                $valor = $valores[$clave] ?? null;

                if ($row['tipo'] === 'bool') {
                    $valor = $valor ? '1' : '0';
                } elseif ($row['tipo'] === 'number') {
                    $valor = is_numeric($valor) ? (string) $valor : '0';
                } else {
                    $valor = trim((string) $valor);
                }

                if ((string) $row['valor'] !== (string) $valor) {
                    $stmt->execute(['clave' => $clave, 'valor' => $valor]);
                    $cambios[$clave] = ['antes' => $row['valor'], 'despues' => $valor];
                }
            }

            if ($cambios !== []) {
                (new AuditoriaService())->registrar('editar', 'configuracion', null, null, $cambios);
            }

            $db->commit();
        } catch (\Throwable) {
            $db->rollBack();
            throw new RuntimeException('No se pudo guardar la configuracion.');
        }
    }
}
