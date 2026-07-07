<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\OrdenRepository;
use RuntimeException;

final class EntregaService
{
    public function __construct(
        private readonly OrdenRepository $ordenes = new OrdenRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function buscar(string $codigo): ?array
    {
        // Busca por clave/codigo de entrega. No entrega nada; solo muestra la ficha para confirmar.
        return (new OrdenService())->buscarPorCodigoEntrega($codigo);
    }

    public function entregar(array $data): int
    {
        /*
         * Entrega de equipo.
         * Fuente: modulo Entregas, normalmente codigo de barras o clave tecleada.
         * Revisa: orden existente, clave correcta, saldo cero y nombre de quien recibe.
         * Destino: pagos opcionales, garantia, estado entregada, tabla entregas y auditoria.
         */
        $codigo = strtoupper(trim((string) ($data['codigo_entrega'] ?? '')));
        $orden = $codigo !== '' ? $this->ordenes->findByDeliveryCode($codigo) : null;
        if (!$orden) {
            throw new RuntimeException('No se encontro una orden con esa clave de entrega.');
        }
        // Solo la clave impresa en la nota del cliente libera el equipo;
        // el folio (visible y secuencial) no es valido como clave.
        if ((string) $orden['codigo_entrega'] !== $codigo) {
            throw new RuntimeException('La clave de entrega no corresponde a esta orden.');
        }
        if ($orden['estado'] === 'entregada') {
            throw new RuntimeException('Esta orden ya fue entregada.');
        }

        $recibidoPor = trim((string) ($data['recibido_por_nombre'] ?? ''));
        if ($recibidoPor === '') {
            throw new RuntimeException('Indica quien recibe el equipo.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $saldoAntes = (float) $orden['saldo_pendiente'];
            $pagoFinal = max(0, (float) ($data['pago_final'] ?? 0));
            $metodo = $pagoFinal > 0 ? (string) ($data['metodo_pago'] ?? 'efectivo') : null;
            $referencia = trim((string) ($data['referencia_pago'] ?? '')) ?: null;

            if ($pagoFinal > 0) {
                // Permite liquidar al momento de entrega dentro de la misma transaccion.
                (new PagoService())->registrar([
                    'orden_id' => (int) $orden['id'],
                    'monto' => $pagoFinal,
                    'metodo' => in_array($metodo, ['efectivo', 'transferencia', 'tarjeta', 'otro'], true) ? $metodo : 'efectivo',
                    'referencia' => $referencia ?: 'ENT-' . $orden['folio'],
                    'notas' => 'Pago registrado en entrega con clave ' . $codigo,
                ], false);
            }

            $ordenActualizada = $this->ordenes->find((int) $orden['id']);
            $saldoDespues = (float) ($ordenActualizada['saldo_pendiente'] ?? $saldoAntes);
            if ($saldoDespues > 0) {
                // Bloqueo critico: no libera equipo con saldo pendiente.
                throw new RuntimeException('No se puede entregar: aun queda saldo pendiente.');
            }

            $garantiaId = $this->crearGarantiaSiAplica((int) $orden['id'], (string) ($orden['garantia_ofrecida'] ?? ''));
            $this->ordenes->updateState((int) $orden['id'], 'entregada', date('Y-m-d H:i:s'));

            $stmt = $db->prepare(
                "INSERT INTO entregas
                 (orden_id, codigo_entrega, usuario_id, recibido_por_nombre, recibido_por_identificacion, saldo_antes, pago_final,
                  metodo_pago, referencia_pago, saldo_despues, garantia_id, observaciones)
                 VALUES
                 (:orden_id, :codigo_entrega, :usuario_id, :recibido_por_nombre, :recibido_por_identificacion, :saldo_antes, :pago_final,
                  :metodo_pago, :referencia_pago, :saldo_despues, :garantia_id, :observaciones)"
            );
            $stmt->execute([
                'orden_id' => (int) $orden['id'],
                'codigo_entrega' => $codigo,
                'usuario_id' => Auth::id() ?? 1,
                'recibido_por_nombre' => $recibidoPor,
                'recibido_por_identificacion' => trim((string) ($data['recibido_por_identificacion'] ?? '')) ?: null,
                'saldo_antes' => $saldoAntes,
                'pago_final' => $pagoFinal,
                'metodo_pago' => $metodo,
                'referencia_pago' => $referencia,
                'saldo_despues' => $saldoDespues,
                'garantia_id' => $garantiaId,
                'observaciones' => trim((string) ($data['observaciones'] ?? '')) ?: null,
            ]);

            $entregaId = (int) $db->lastInsertId();
            $this->auditoria->registrar('entregar', 'ordenes', (int) $orden['id'], ['estado' => $orden['estado'], 'saldo' => $saldoAntes], [
                'estado' => 'entregada',
                'codigo_entrega' => $codigo,
                'entrega_id' => $entregaId,
                'usuario_id' => Auth::id(),
                'recibido_por' => $recibidoPor,
            ]);

            $db->commit();
            return $entregaId;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function comprobante(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            "SELECT en.*, u.name usuario_nombre, o.folio, o.estado, o.fecha_real_entrega, o.costo_final, o.anticipo, o.saldo_pendiente,
                    c.nombre_completo cliente_nombre, c.telefono cliente_telefono,
                    e.tipo equipo_tipo, e.marca equipo_marca, e.modelo equipo_modelo
             FROM entregas en
             JOIN users u ON u.id = en.usuario_id
             JOIN ordenes_servicio o ON o.id = en.orden_id
             JOIN clientes c ON c.id = o.cliente_id
             JOIN equipos e ON e.id = o.equipo_id
             WHERE en.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function crearGarantiaSiAplica(int $ordenId, string $condiciones): ?int
    {
        // Crea una garantia basica una sola vez al entregar, si no existia previamente.
        $db = Database::connection();
        $stmt = $db->prepare('SELECT id FROM garantias WHERE orden_id = :orden_id ORDER BY id DESC LIMIT 1');
        $stmt->execute(['orden_id' => $ordenId]);
        $existente = $stmt->fetchColumn();
        if ($existente) {
            return (int) $existente;
        }

        $condiciones = trim($condiciones) ?: 'Garantia sobre la reparacion realizada.';
        $insert = $db->prepare(
            "INSERT INTO garantias (orden_id, fecha_inicio, fecha_fin, condiciones, estado)
             VALUES (:orden_id, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), :condiciones, 'activa')"
        );
        $insert->execute(['orden_id' => $ordenId, 'condiciones' => $condiciones]);
        return (int) $db->lastInsertId();
    }
}
