<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        /*
         * Conexion central a MySQL por PDO.
         * Fuente: config/database.php, normalmente alimentado por .env.
         * Destino: repositorios y servicios que consultan o escriben datos.
         * Seguridad: ERRMODE_EXCEPTION y ATTR_EMULATE_PREPARES=false ayudan a usar
         * prepared statements reales y fallar de forma controlable.
         */
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $config = require BASE_PATH . '/config/database.php';
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            $config['host'],
            $config['port'],
            $config['database'],
            $config['charset']
        );

        try {
            self::$connection = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $exception) {
            Logger::error('Database connection failed', ['error' => $exception->getMessage()]);
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }

        return self::$connection;
    }
}
