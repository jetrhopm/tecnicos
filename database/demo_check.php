<?php

declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$db = App\Core\Database::connection();

$queries = [
    'usuarios_demo' => "SELECT COUNT(*) FROM users WHERE email IN ('superadmin@local.test','administrador@local.test','recepcion@local.test','tecnico@local.test','tecnico_senior@local.test','almacen@local.test','caja@local.test','cliente_consulta@local.test')",
    'roles_asignados_demo' => "SELECT COUNT(*) FROM user_roles ur JOIN users u ON u.id = ur.user_id WHERE u.email IN ('superadmin@local.test','administrador@local.test','recepcion@local.test','tecnico@local.test','tecnico_senior@local.test','almacen@local.test','caja@local.test','cliente_consulta@local.test')",
    'cliente_demo' => "SELECT COUNT(*) FROM clientes WHERE email = 'cliente.demo@local.test'",
    'equipos_demo' => "SELECT COUNT(*) FROM equipos WHERE numero_serie LIKE 'DEMO-%'",
    'orden_demo' => "SELECT COUNT(*) FROM ordenes_servicio WHERE folio = 'ST-DEMO-00001'",
    'cotizacion_demo' => "SELECT COUNT(*) FROM cotizaciones q JOIN ordenes_servicio o ON o.id = q.orden_id WHERE o.folio = 'ST-DEMO-00001'",
    'pago_demo' => "SELECT COUNT(DISTINCT p.referencia) FROM pagos p JOIN ordenes_servicio o ON o.id = p.orden_id WHERE o.folio = 'ST-DEMO-00001' AND p.referencia = 'ANT-DEMO-001'",
];

foreach ($queries as $label => $sql) {
    echo strtoupper($label) . '=' . $db->query($sql)->fetchColumn() . PHP_EOL;
}
