<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();
$cliente = $db->query("SELECT * FROM clientes WHERE telefono = '5551002000' OR email = 'cliente.demo@local.test' ORDER BY id LIMIT 1")->fetch();

if (!$cliente) {
    echo "CLIENTE_DEMO=0" . PHP_EOL;
    exit(1);
}

$service = new App\Services\ClienteService();
$original = $cliente;
$nuevoTelefono = '5559998888';

$payload = [
    'nombre_completo' => $cliente['nombre_completo'],
    'telefono' => $nuevoTelefono,
    'whatsapp' => $cliente['whatsapp'],
    'email' => $cliente['email'],
    'domicilio' => $cliente['domicilio'],
    'ciudad' => $cliente['ciudad'],
    'estado' => $cliente['estado'],
    'codigo_postal' => $cliente['codigo_postal'],
    'rfc' => $cliente['rfc'],
    'notas_internas' => $cliente['notas_internas'],
    'estatus' => $cliente['estatus'],
];

$service->guardar($payload, (int) $cliente['id']);

$orden = $db->query("SELECT o.folio, c.telefono, c.whatsapp FROM ordenes_servicio o JOIN clientes c ON c.id = o.cliente_id WHERE o.folio = 'ST-DEMO-00001'")->fetch();

$restore = [
    'nombre_completo' => $original['nombre_completo'],
    'telefono' => $original['telefono'],
    'whatsapp' => $original['whatsapp'],
    'email' => $original['email'],
    'domicilio' => $original['domicilio'],
    'ciudad' => $original['ciudad'],
    'estado' => $original['estado'],
    'codigo_postal' => $original['codigo_postal'],
    'rfc' => $original['rfc'],
    'notas_internas' => $original['notas_internas'],
    'estatus' => $original['estatus'],
];
$service->guardar($restore, (int) $cliente['id']);

echo 'ORDEN_TELEFONO=' . ($orden['telefono'] ?? '') . PHP_EOL;
echo 'ORDEN_WHATSAPP=' . ($orden['whatsapp'] ?? '') . PHP_EOL;
echo 'OK=' . (((string) ($orden['telefono'] ?? '') === $nuevoTelefono && (string) ($orden['whatsapp'] ?? '') === $nuevoTelefono) ? '1' : '0') . PHP_EOL;
