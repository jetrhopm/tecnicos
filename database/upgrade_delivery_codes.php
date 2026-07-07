<?php

declare(strict_types=1);

/*
 * Migracion: claves de entrega aleatorias.
 *
 * Antes la clave era derivable del folio (ENT-<folio>), asi que cualquiera
 * que conociera el folio podia presentarla. Este script regenera la clave de
 * las ordenes activas (no entregadas ni canceladas) cuya clave sea NULL o
 * derivada del folio. Las ordenes ya entregadas o canceladas conservan su
 * clave como registro historico.
 *
 * IMPORTANTE: las notas de recepcion ya impresas de ordenes activas quedan
 * invalidas; reimprime la nota desde la orden para entregarla al cliente.
 *
 * Uso: php database/upgrade_delivery_codes.php
 */

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Database;
use App\Services\FolioService;

$db = Database::connection();
$folios = new FolioService();

$demoFolio = 'ST-DEMO-00001';
$demoCodigo = 'ENT-DEMO2468';

$stmt = $db->query(
    "SELECT id, folio, codigo_entrega, estado
     FROM ordenes_servicio
     WHERE deleted_at IS NULL
       AND estado NOT IN ('entregada', 'cancelada')
       AND (codigo_entrega IS NULL OR codigo_entrega = CONCAT('ENT-', folio))"
);
$ordenes = $stmt->fetchAll();

if (!$ordenes) {
    echo "No hay claves derivadas del folio en ordenes activas. Nada que migrar.\n";
    exit(0);
}

$update = $db->prepare('UPDATE ordenes_servicio SET codigo_entrega = :codigo WHERE id = :id');

foreach ($ordenes as $orden) {
    // La orden demo del seed usa una clave fija documentada en el README.
    $nuevo = $orden['folio'] === $demoFolio ? $demoCodigo : $folios->codigoEntrega();
    $update->execute(['codigo' => $nuevo, 'id' => $orden['id']]);
    printf("Orden %s (%s): %s -> %s\n", $orden['folio'], $orden['estado'], $orden['codigo_entrega'] ?? 'NULL', $nuevo);
}

printf("Listo. %d clave(s) regenerada(s). Reimprime las notas de las ordenes activas.\n", count($ordenes));
