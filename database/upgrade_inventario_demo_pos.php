<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;

$db = Database::connection();

$db->exec("
    INSERT INTO proveedores (nombre, contacto, telefono, email, domicilio, sitio_web, notas, estatus)
    SELECT 'Proveedor Demo', 'Contacto Demo', '5552003000', 'proveedor@local.test', 'Calle Refacciones 456', 'https://example.test', 'Proveedor de ejemplo.', 'activo'
    WHERE NOT EXISTS (SELECT 1 FROM proveedores WHERE email = 'proveedor@local.test' AND deleted_at IS NULL)
");

$proveedorId = (int) $db->query("SELECT id FROM proveedores WHERE email = 'proveedor@local.test' AND deleted_at IS NULL LIMIT 1")->fetchColumn();

if ($proveedorId <= 0) {
    throw new RuntimeException('No se pudo resolver el proveedor demo.');
}

$productos = [
    ['Pantalla OLED compatible Samsung A15', 'POS-SAM-A15-OLED', 'Pantallas', 'Samsung', 'Galaxy A15', 620.00, 1150.00, 8, 2, 'A1'],
    ['Pantalla incell iPhone 11', 'POS-IPH11-INCELL', 'Pantallas', 'Apple', 'iPhone 11', 480.00, 950.00, 6, 2, 'A2'],
    ['Pantalla OLED iPhone 13', 'POS-IPH13-OLED', 'Pantallas', 'Apple', 'iPhone 13', 1450.00, 2350.00, 4, 1, 'A3'],
    ['Bateria iPhone 11 alta capacidad', 'POS-IPH11-BAT', 'Baterias', 'Apple', 'iPhone 11', 210.00, 480.00, 10, 3, 'B1'],
    ['Bateria Samsung A14/A15', 'POS-SAM-A14-BAT', 'Baterias', 'Samsung', 'Galaxy A14 / A15', 180.00, 420.00, 12, 3, 'B2'],
    ['Centro de carga Redmi Note 12', 'POS-REDMI12-CG', 'Centros de carga', 'Xiaomi', 'Redmi Note 12', 95.00, 280.00, 15, 4, 'C1'],
    ['Centro de carga Motorola G Power', 'POS-MOTOGP-CG', 'Centros de carga', 'Motorola', 'Moto G Power', 110.00, 300.00, 9, 3, 'C2'],
    ['Flex encendido Samsung A15', 'POS-SAM-A15-FLEX', 'Flex y botones', 'Samsung', 'Galaxy A15', 65.00, 180.00, 18, 5, 'C3'],
    ['Camara trasera iPhone 12', 'POS-IPH12-CAMT', 'Camaras', 'Apple', 'iPhone 12', 520.00, 980.00, 3, 1, 'D1'],
    ['Bocina auricular universal celular', 'POS-UNI-AURI', 'Audio', 'Generica', 'Celular universal', 35.00, 120.00, 25, 6, 'D2'],
    ['Microfono universal soldable', 'POS-UNI-MIC', 'Audio', 'Generica', 'Celular universal', 18.00, 80.00, 30, 8, 'D3'],
    ['Vidrio templado 6.5 universal', 'POS-MICA-65', 'Accesorios', 'Generica', 'Universal 6.5 pulgadas', 18.00, 80.00, 50, 10, 'E1'],
    ['Mica ceramica iPhone 11/XR', 'POS-MICA-IPH11', 'Accesorios', 'Apple', 'iPhone 11 / XR', 28.00, 120.00, 35, 8, 'E2'],
    ['Cable USB-C carga rapida 1m', 'POS-CABLE-USBC', 'Accesorios', 'Generica', 'USB-C', 38.00, 130.00, 40, 10, 'F1'],
    ['Cable Lightning 1m', 'POS-CABLE-LIG', 'Accesorios', 'Apple', 'Lightning', 42.00, 150.00, 30, 8, 'F2'],
    ['Cargador tipo C 25W', 'POS-CARG-TC25', 'Accesorios', 'Generica', 'USB-C 25W', 95.00, 250.00, 20, 5, 'F3'],
    ['Memoria RAM DDR4 8GB laptop', 'POS-RAM-DDR4-8', 'Laptop', 'Generica', 'DDR4 SO-DIMM', 360.00, 650.00, 7, 2, 'L1'],
    ['SSD 240GB SATA', 'POS-SSD-240', 'Laptop', 'Generica', 'SATA 2.5', 310.00, 590.00, 8, 2, 'L2'],
    ['Pasta termica alto rendimiento', 'POS-PASTA-TERM', 'Servicio', 'Generica', 'CPU/GPU', 55.00, 160.00, 16, 4, 'L3'],
    ['Joystick analogo control PS4', 'POS-PS4-JOY', 'Consolas', 'Sony', 'DualShock 4', 55.00, 180.00, 12, 3, 'G1'],
    ['Puerto HDMI PlayStation 4', 'POS-PS4-HDMI', 'Consolas', 'Sony', 'PlayStation 4', 85.00, 260.00, 6, 2, 'G2'],
    ['Fusible microondas 20A', 'POS-MICRO-FUS20', 'Electrodomesticos', 'Generica', 'Microondas', 20.00, 90.00, 20, 5, 'H1'],
    ['Carbon para taladro universal', 'POS-CARBON-TAL', 'Herramientas', 'Generica', 'Taladro universal', 25.00, 110.00, 22, 6, 'H2'],
    ['Foco LED moto H4', 'POS-MOTO-H4LED', 'Motos', 'Generica', 'H4', 75.00, 190.00, 10, 3, 'M1'],
];

$stmt = $db->prepare("
    INSERT INTO refacciones
        (proveedor_id, nombre, sku, categoria, marca, modelo_compatible, costo, precio_venta, stock_actual, stock_minimo, ubicacion, estatus)
    VALUES
        (:proveedor_id, :nombre, :sku, :categoria, :marca, :modelo_compatible, :costo, :precio_venta, :stock_actual, :stock_minimo, :ubicacion, 'activo')
    ON DUPLICATE KEY UPDATE
        proveedor_id = VALUES(proveedor_id),
        nombre = VALUES(nombre),
        categoria = VALUES(categoria),
        marca = VALUES(marca),
        modelo_compatible = VALUES(modelo_compatible),
        costo = VALUES(costo),
        precio_venta = VALUES(precio_venta),
        stock_actual = GREATEST(stock_actual, VALUES(stock_actual)),
        stock_minimo = VALUES(stock_minimo),
        ubicacion = VALUES(ubicacion),
        estatus = VALUES(estatus)
");

foreach ($productos as [$nombre, $sku, $categoria, $marca, $modelo, $costo, $precio, $stock, $minimo, $ubicacion]) {
    $stmt->execute([
        'proveedor_id' => $proveedorId,
        'nombre' => $nombre,
        'sku' => $sku,
        'categoria' => $categoria,
        'marca' => $marca,
        'modelo_compatible' => $modelo,
        'costo' => $costo,
        'precio_venta' => $precio,
        'stock_actual' => $stock,
        'stock_minimo' => $minimo,
        'ubicacion' => $ubicacion,
    ]);
}

echo 'Productos demo para punto de venta cargados: ' . count($productos) . PHP_EOL;
