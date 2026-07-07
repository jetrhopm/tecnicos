<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();
$db->beginTransaction();

try {
    $db->exec("
        UPDATE clientes
        SET telefono = '5551002000', whatsapp = '5551002000'
        WHERE email = 'cliente.demo@local.test' AND deleted_at IS NULL
    ");

    $db->exec("
        DELETE p1 FROM pagos p1
        JOIN pagos p2
          ON p1.orden_id = p2.orden_id
         AND p1.referencia = p2.referencia
         AND p1.id > p2.id
        WHERE p1.referencia = 'ANT-DEMO-001'
    ");

    $db->commit();
    echo 'Demo corregido correctamente.' . PHP_EOL;
} catch (Throwable $exception) {
    $db->rollBack();
    throw $exception;
}
