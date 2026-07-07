<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();
$id = (int) $db->query("SELECT id FROM ordenes_servicio WHERE folio = 'ST-DEMO-00001'")->fetchColumn();

try {
    (new App\Services\OrdenService())->cambiarEstado($id, 'entregada', true);
    echo 'BLOCKED=0' . PHP_EOL;
} catch (Throwable $exception) {
    echo 'BLOCKED=1' . PHP_EOL;
    echo 'MESSAGE=' . $exception->getMessage() . PHP_EOL;
}
