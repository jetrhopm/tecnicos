<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();
$folio = 'ST-TEST-ENTREGA-' . date('His');
$codigo = 'ENT-' . $folio;

$db->exec("
    DELETE p FROM pagos p
    JOIN ordenes_servicio o ON o.id = p.orden_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE c.email = 'entrega.test@local.test'
");
$db->exec("
    DELETE en FROM entregas en
    JOIN ordenes_servicio o ON o.id = en.orden_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE c.email = 'entrega.test@local.test'
");
$db->exec("
    DELETE g FROM garantias g
    JOIN ordenes_servicio o ON o.id = g.orden_id
    JOIN clientes c ON c.id = o.cliente_id
    WHERE c.email = 'entrega.test@local.test'
");
$db->exec("
    DELETE o FROM ordenes_servicio o
    JOIN clientes c ON c.id = o.cliente_id
    WHERE c.email = 'entrega.test@local.test'
");
$db->exec("DELETE e FROM equipos e JOIN clientes c ON c.id = e.cliente_id WHERE c.email = 'entrega.test@local.test'");
$db->exec("DELETE FROM clientes WHERE email = 'entrega.test@local.test'");

$db->beginTransaction();
try {
    $db->exec("INSERT INTO clientes (nombre_completo, telefono, whatsapp, email, estatus) VALUES ('Cliente Entrega Test', '5557770000', '5557770000', 'entrega.test@local.test', 'activo')");
    $clienteId = (int) $db->lastInsertId();
    $db->exec("INSERT INTO equipos (cliente_id, tipo, marca, modelo, numero_serie) VALUES ({$clienteId}, 'celular', 'Test', 'Entrega', 'TEST-ENTREGA-" . date('His') . "')");
    $equipoId = (int) $db->lastInsertId();
    $userId = (int) $db->query("SELECT id FROM users WHERE email = 'admin@local.test'")->fetchColumn();
    $stmt = $db->prepare(
        "INSERT INTO ordenes_servicio
         (folio, cliente_id, equipo_id, recibido_por, tipo_servicio, falla_reportada, prioridad, estado, costo_final, anticipo, saldo_pendiente, codigo_entrega, ubicacion_actual, token_publico)
         VALUES
         (:folio, :cliente_id, :equipo_id, :recibido_por, 'Prueba entrega', 'Prueba temporal de entrega', 'normal', 'lista_para_entrega', 400, 0, 400, :codigo, 'Caja', :token)"
    );
    $stmt->execute([
        'folio' => $folio,
        'cliente_id' => $clienteId,
        'equipo_id' => $equipoId,
        'recibido_por' => $userId,
        'codigo' => $codigo,
        'token' => bin2hex(random_bytes(16)),
    ]);
    $ordenId = (int) $db->lastInsertId();
    $db->commit();

    $entregaId = (new App\Services\EntregaService())->entregar([
        'codigo_entrega' => $codigo,
        'recibido_por_nombre' => 'Cliente Entrega Test',
        'pago_final' => 400,
        'metodo_pago' => 'efectivo',
        'referencia_pago' => 'TEST-ENTREGA',
        'observaciones' => 'Prueba automatica',
    ]);

    $row = $db->query("SELECT estado, saldo_pendiente FROM ordenes_servicio WHERE id = {$ordenId}")->fetch();
    echo 'ENTREGA_ID=' . $entregaId . PHP_EOL;
    echo 'ESTADO=' . $row['estado'] . PHP_EOL;
    echo 'SALDO=' . $row['saldo_pendiente'] . PHP_EOL;
    echo 'OK=' . (($row['estado'] === 'entregada' && (float) $row['saldo_pendiente'] === 0.0) ? '1' : '0') . PHP_EOL;

    $db->exec("DELETE FROM entregas WHERE orden_id = {$ordenId}");
    $db->exec("DELETE FROM pagos WHERE orden_id = {$ordenId}");
    $db->exec("DELETE FROM garantias WHERE orden_id = {$ordenId}");
    $db->exec("DELETE FROM ordenes_servicio WHERE id = {$ordenId}");
    $db->exec("DELETE FROM equipos WHERE id = {$equipoId}");
    $db->exec("DELETE FROM clientes WHERE id = {$clienteId}");
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $exception;
}
