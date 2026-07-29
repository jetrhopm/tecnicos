<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use RuntimeException;

final class LicenciaService
{
    public const ROLE = 'licenciante';

    public function __construct(
        private readonly ConfiguracionService $configuracion = new ConfiguracionService(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function estado(): array
    {
        $activo = (string) $this->configuracion->get('demo.activo', '1') === '1';
        $inicio = $this->fecha((string) $this->configuracion->get('demo.inicio', date('Y-m-d')));
        $dias = min(3650, max(0, (int) $this->configuracion->get('demo.dias', 14)));
        $hoy = new DateTimeImmutable('today');
        $vence = $inicio->modify('+' . $dias . ' days');
        $diasRestantes = max(0, (int) $hoy->diff($vence)->format('%r%a'));
        $vencida = $activo && $hoy >= $vence;
        $mensaje = trim((string) $this->configuracion->get('demo.mensaje_expirado', ''));

        if ($mensaje === '') {
            $mensaje = 'La demostración terminó. Si te interesó nuestro sistema y lo ves útil en tu día a día, contáctanos para activar la versión extendida.';
        }

        return [
            'activo' => $activo,
            'inicio' => $inicio->format('Y-m-d'),
            'dias' => $dias,
            'vence' => $vence->format('Y-m-d'),
            'dias_restantes' => $diasRestantes,
            'vencida' => $vencida,
            'mensaje_expirado' => $mensaje,
        ];
    }

    public function verificarAcceso(int $userId): void
    {
        if ($this->esLicenciante($userId)) {
            return;
        }

        $estado = $this->estado();
        if ($estado['vencida']) {
            $this->auditoria->registrar('demo_expirada_login', 'licencias', null, null, ['user_id' => $userId]);
            throw new RuntimeException((string) $estado['mensaje_expirado']);
        }
    }

    public function esLicenciante(int $userId): bool
    {
        $roles = $this->users->rolesForUser($userId);
        return in_array(self::ROLE, array_column($roles, 'name'), true);
    }

    public function usuarioActualEsLicenciante(): bool
    {
        $id = Auth::id();
        return $id !== null && $this->esLicenciante((int) $id);
    }

    public function guardar(array $data): void
    {
        $this->exigirLicenciante();
        $inicio = trim((string) ($data['inicio'] ?? date('Y-m-d')));
        $dias = min(3650, max(0, (int) ($data['dias'] ?? 14)));
        $mensaje = trim((string) ($data['mensaje_expirado'] ?? ''));
        if (!$this->esFechaValida($inicio)) {
            throw new RuntimeException('La fecha de inicio de demo no es válida.');
        }
        if ($mensaje === '') {
            throw new RuntimeException('El mensaje de demo terminada es obligatorio.');
        }

        $this->configuracion->upsertMany([
            ['demo.activo', !empty($data['activo']) ? '1' : '0', 'bool', 'licencia'],
            ['demo.inicio', $inicio, 'date', 'licencia'],
            ['demo.dias', (string) $dias, 'number', 'licencia'],
            ['demo.mensaje_expirado', $mensaje, 'text', 'licencia'],
        ]);

        $this->auditoria->registrar('editar_demo', 'licencias', null, null, [
            'activo' => !empty($data['activo']),
            'inicio' => $inicio,
            'dias' => $dias,
        ]);
    }

    public function reiniciar(): void
    {
        $this->exigirLicenciante();
        $hoy = date('Y-m-d');
        $this->configuracion->upsertMany([
            ['demo.inicio', $hoy, 'date', 'licencia'],
        ]);
        $this->auditoria->registrar('reiniciar_demo', 'licencias', null, null, ['inicio' => $hoy]);
    }

    private function exigirLicenciante(): void
    {
        if (!$this->usuarioActualEsLicenciante()) {
            throw new RuntimeException('Sólo el administrador de licencias puede modificar la demo.');
        }
    }

    private function fecha(string $value): DateTimeImmutable
    {
        if (!$this->esFechaValida($value)) {
            return new DateTimeImmutable('today');
        }

        return new DateTimeImmutable($value);
    }

    private function esFechaValida(string $value): bool
    {
        $fecha = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $fecha instanceof DateTimeImmutable && $fecha->format('Y-m-d') === $value;
    }
}
