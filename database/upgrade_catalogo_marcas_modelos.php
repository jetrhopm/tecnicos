<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

function table_exists(PDO $db, string $table): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function column_exists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column');
    $stmt->execute(['table' => $table, 'column' => $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function index_exists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND INDEX_NAME = :index');
    $stmt->execute(['table' => $table, 'index' => $index]);
    return (int) $stmt->fetchColumn() > 0;
}

function constraint_exists(PDO $db, string $constraint): bool
{
    $stmt = $db->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = :constraint');
    $stmt->execute(['constraint' => $constraint]);
    return (int) $stmt->fetchColumn() > 0;
}

function catalog_slug(string $value): string
{
    $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : false;
    $ascii = $ascii === false ? $value : $ascii;
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $ascii) ?? '', '-'));
    return $slug !== '' ? $slug : hash('sha256', $value);
}

try {
    if (!table_exists($db, 'equipo_marcas')) {
        $db->exec("
            CREATE TABLE equipo_marcas (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL UNIQUE,
                estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_equipo_marcas_nombre (nombre),
                INDEX idx_equipo_marcas_estatus (estatus)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!table_exists($db, 'equipo_modelos')) {
        $db->exec("
            CREATE TABLE equipo_modelos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                marca_id INT UNSIGNED NOT NULL,
                nombre VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                tipo_equipo ENUM('celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro') NULL,
                estatus ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT fk_equipo_modelos_marca FOREIGN KEY (marca_id) REFERENCES equipo_marcas(id) ON DELETE CASCADE,
                UNIQUE KEY uq_equipo_modelo_marca_slug (marca_id, slug),
                INDEX idx_equipo_modelos_nombre (nombre),
                INDEX idx_equipo_modelos_tipo (tipo_equipo),
                INDEX idx_equipo_modelos_estatus (estatus)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    if (!column_exists($db, 'equipos', 'marca_id')) {
        $db->exec('ALTER TABLE equipos ADD marca_id INT UNSIGNED NULL AFTER tipo');
    }

    if (!column_exists($db, 'equipos', 'modelo_id')) {
        $db->exec('ALTER TABLE equipos ADD modelo_id INT UNSIGNED NULL AFTER marca_id');
    }

    foreach ([
        'idx_equipos_marca' => 'ALTER TABLE equipos ADD INDEX idx_equipos_marca (marca_id)',
        'idx_equipos_modelo' => 'ALTER TABLE equipos ADD INDEX idx_equipos_modelo (modelo_id)',
        'idx_equipos_tipo' => 'ALTER TABLE equipos ADD INDEX idx_equipos_tipo (tipo)',
    ] as $index => $sql) {
        if (!index_exists($db, 'equipos', $index)) {
            $db->exec($sql);
        }
    }

    $marcas = [
        'Acer','Apple','Asus','Black+Decker','Bosch','Brother','Canon','Dell','DeWalt','Epson','Generica',
        'Hisense','Honda','Honor','HP','Huawei','Italika','Lenovo','LG','Mabe','Makita','Microsoft',
        'Motorola','MSI','Nintendo','Nokia','OnePlus','Oppo','PlayStation','Realme','Ryobi','Samsung',
        'Sanyo','Sony','TCL','Toshiba','Vivo','Whirlpool','Xiaomi','Yamaha',
    ];

    $insertMarca = $db->prepare("
        INSERT INTO equipo_marcas (nombre, slug, estatus)
        VALUES (:nombre, :slug, 'activo')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estatus = 'activo'
    ");
    foreach ($marcas as $marca) {
        $insertMarca->execute(['nombre' => $marca, 'slug' => catalog_slug($marca)]);
    }

    $modelos = [
        ['Apple', 'iPhone 11', 'celular'], ['Apple', 'iPhone 12', 'celular'], ['Apple', 'iPhone 13', 'celular'],
        ['Apple', 'iPhone 14', 'celular'], ['Apple', 'iPhone 15', 'celular'], ['Apple', 'iPhone 16', 'celular'],
        ['Apple', 'iPad', 'otro'], ['Apple', 'MacBook Air', 'laptop'], ['Apple', 'MacBook Pro', 'laptop'],
        ['Samsung', 'Galaxy A03', 'celular'], ['Samsung', 'Galaxy A14', 'celular'], ['Samsung', 'Galaxy A15', 'celular'],
        ['Samsung', 'Galaxy A24', 'celular'], ['Samsung', 'Galaxy A34', 'celular'], ['Samsung', 'Galaxy A54', 'celular'],
        ['Samsung', 'Galaxy S21', 'celular'], ['Samsung', 'Galaxy S22', 'celular'], ['Samsung', 'Galaxy S23', 'celular'],
        ['Samsung', 'Galaxy S24', 'celular'], ['Samsung', 'Galaxy Tab', 'otro'], ['Samsung', 'Samsung TV', 'electrodomestico'],
        ['Motorola', 'Moto G Power', 'celular'], ['Motorola', 'Moto G Play', 'celular'], ['Motorola', 'Moto G Stylus', 'celular'],
        ['Motorola', 'Moto Edge', 'celular'], ['Xiaomi', 'Redmi Note 10', 'celular'], ['Xiaomi', 'Redmi Note 11', 'celular'],
        ['Xiaomi', 'Redmi Note 12', 'celular'], ['Xiaomi', 'Redmi Note 13', 'celular'], ['Xiaomi', 'Poco X3', 'celular'],
        ['Xiaomi', 'Poco X5', 'celular'], ['Huawei', 'P30 Lite', 'celular'], ['Huawei', 'P40 Lite', 'celular'],
        ['Huawei', 'Y9', 'celular'], ['Honor', 'Honor X8', 'celular'], ['Oppo', 'Reno 7', 'celular'],
        ['Oppo', 'A57', 'celular'], ['Vivo', 'Y20', 'celular'], ['Realme', 'C55', 'celular'], ['OnePlus', 'Nord', 'celular'],
        ['Lenovo', 'ThinkPad', 'laptop'], ['Lenovo', 'IdeaPad', 'laptop'], ['HP', 'Pavilion', 'laptop'],
        ['HP', 'LaserJet', 'impresora'], ['HP', 'DeskJet', 'impresora'], ['Dell', 'Inspiron', 'laptop'],
        ['Dell', 'Latitude', 'laptop'], ['Asus', 'VivoBook', 'laptop'], ['Asus', 'ROG', 'laptop'],
        ['Acer', 'Aspire', 'laptop'], ['MSI', 'Modern', 'laptop'], ['Brother', 'DCP', 'impresora'],
        ['Epson', 'EcoTank', 'impresora'], ['Canon', 'PIXMA', 'impresora'], ['Sony', 'PlayStation 4', 'consola'],
        ['Sony', 'PlayStation 5', 'consola'], ['Nintendo', 'Switch', 'consola'], ['Microsoft', 'Xbox One', 'consola'],
        ['Microsoft', 'Xbox Series S', 'consola'], ['Microsoft', 'Xbox Series X', 'consola'], ['LG', 'Microondas', 'electrodomestico'],
        ['LG', 'Lavadora', 'electrodomestico'], ['Whirlpool', 'Lavadora', 'electrodomestico'], ['Mabe', 'Refrigerador', 'electrodomestico'],
        ['Hisense', 'Pantalla Smart TV', 'electrodomestico'], ['TCL', 'Pantalla Roku TV', 'electrodomestico'], ['Bosch', 'Taladro', 'herramienta'],
        ['Makita', 'Esmeril', 'herramienta'], ['DeWalt', 'Rotomartillo', 'herramienta'], ['Black+Decker', 'Taladro', 'herramienta'],
        ['Ryobi', 'Sierra', 'herramienta'], ['Italika', 'FT', 'moto'], ['Italika', 'DM', 'moto'], ['Honda', 'CG', 'moto'],
        ['Yamaha', 'FZ', 'moto'], ['Generica', 'Equipo Otro Demo', 'otro'],
    ];

    $selectMarca = $db->prepare('SELECT id FROM equipo_marcas WHERE slug = :slug LIMIT 1');
    $insertModelo = $db->prepare("
        INSERT INTO equipo_modelos (marca_id, nombre, slug, tipo_equipo, estatus)
        VALUES (:marca_id, :nombre, :slug, :tipo_equipo, 'activo')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo_equipo = VALUES(tipo_equipo), estatus = 'activo'
    ");
    foreach ($modelos as [$marca, $modelo, $tipo]) {
        $selectMarca->execute(['slug' => catalog_slug($marca)]);
        $marcaId = (int) $selectMarca->fetchColumn();
        if ($marcaId > 0) {
            $insertModelo->execute([
                'marca_id' => $marcaId,
                'nombre' => $modelo,
                'slug' => catalog_slug($modelo),
                'tipo_equipo' => $tipo,
            ]);
        }
    }

    $selectMarcaId = $db->prepare('SELECT id, nombre FROM equipo_marcas WHERE slug = :slug LIMIT 1');
    $selectModeloId = $db->prepare('SELECT id, nombre FROM equipo_modelos WHERE marca_id = :marca_id AND slug = :slug LIMIT 1');
    $createMarca = $db->prepare("
        INSERT INTO equipo_marcas (nombre, slug, estatus)
        VALUES (:nombre, :slug, 'activo')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), estatus = 'activo'
    ");
    $createModelo = $db->prepare("
        INSERT INTO equipo_modelos (marca_id, nombre, slug, tipo_equipo, estatus)
        VALUES (:marca_id, :nombre, :slug, :tipo_equipo, 'activo')
        ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), tipo_equipo = VALUES(tipo_equipo), estatus = 'activo'
    ");
    $updateEquipo = $db->prepare('UPDATE equipos SET marca_id = :marca_id, modelo_id = :modelo_id WHERE id = :id');

    $equipos = $db->query("SELECT id, tipo, marca, modelo FROM equipos WHERE deleted_at IS NULL AND (marca_id IS NULL OR modelo_id IS NULL)")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($equipos as $equipo) {
        $marca = trim((string) ($equipo['marca'] ?? ''));
        $modelo = trim((string) ($equipo['modelo'] ?? ''));
        if ($marca === '') {
            continue;
        }

        $marcaSlug = catalog_slug($marca);
        $selectMarcaId->execute(['slug' => $marcaSlug]);
        $marcaRow = $selectMarcaId->fetch(PDO::FETCH_ASSOC);
        if (!$marcaRow) {
            $createMarca->execute(['nombre' => $marca, 'slug' => $marcaSlug]);
            $selectMarcaId->execute(['slug' => $marcaSlug]);
            $marcaRow = $selectMarcaId->fetch(PDO::FETCH_ASSOC);
        }

        $marcaId = (int) ($marcaRow['id'] ?? 0);
        $modeloId = null;
        if ($marcaId > 0 && $modelo !== '') {
            $modeloSlug = catalog_slug($modelo);
            $selectModeloId->execute(['marca_id' => $marcaId, 'slug' => $modeloSlug]);
            $modeloRow = $selectModeloId->fetch(PDO::FETCH_ASSOC);
            if (!$modeloRow) {
                $createModelo->execute([
                    'marca_id' => $marcaId,
                    'nombre' => $modelo,
                    'slug' => $modeloSlug,
                    'tipo_equipo' => in_array($equipo['tipo'], ['celular','laptop','pc','consola','impresora','electrodomestico','herramienta','moto','otro'], true) ? $equipo['tipo'] : null,
                ]);
                $selectModeloId->execute(['marca_id' => $marcaId, 'slug' => $modeloSlug]);
                $modeloRow = $selectModeloId->fetch(PDO::FETCH_ASSOC);
            }
            $modeloId = $modeloRow ? (int) $modeloRow['id'] : null;
        }

        $updateEquipo->execute([
            'marca_id' => $marcaId ?: null,
            'modelo_id' => $modeloId,
            'id' => (int) $equipo['id'],
        ]);
    }

    if (!constraint_exists($db, 'fk_equipos_marca')) {
        $db->exec('ALTER TABLE equipos ADD CONSTRAINT fk_equipos_marca FOREIGN KEY (marca_id) REFERENCES equipo_marcas(id) ON DELETE SET NULL');
    }

    if (!constraint_exists($db, 'fk_equipos_modelo')) {
        $db->exec('ALTER TABLE equipos ADD CONSTRAINT fk_equipos_modelo FOREIGN KEY (modelo_id) REFERENCES equipo_modelos(id) ON DELETE SET NULL');
    }

    echo "Catalogo de marcas/modelos actualizado.\n";
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $exception;
}
