<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Database;
use App\Repositories\UserRepository;
use RuntimeException;

final class UserService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService()
    ) {
    }

    public function listar(): array
    {
        $usuarios = $this->users->all();
        if ((new LicenciaService())->usuarioActualEsLicenciante()) {
            return $usuarios;
        }

        return array_values(array_filter($usuarios, static function (array $usuario): bool {
            return !str_contains(',' . (string) ($usuario['role_names'] ?? '') . ',', ',' . LicenciaService::ROLE . ',');
        }));
    }

    public function roles(): array
    {
        $roles = $this->users->roles();
        if (!(new LicenciaService())->usuarioActualEsLicenciante()) {
            $roles = array_values(array_filter($roles, static fn (array $role): bool => $role['name'] !== LicenciaService::ROLE));
        }

        return $roles;
    }

    public function crear(array $data): int
    {
        $payloadBase = $this->normalizarPerfil($data);
        $name = $payloadBase['name'];
        $email = $payloadBase['email'];
        $password = (string) ($data['password'] ?? '');
        $roleIds = $this->normalizarRoles($data);

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Nombre, email y contrasena son obligatorios.');
        }
        $this->validarPassword($password);
        if ($this->users->emailExists($email)) {
            throw new RuntimeException('Ya existe un usuario con ese email.');
        }
        $this->validarRolLicenciante($roleIds, []);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => $payloadBase['phone'],
                'status' => $payloadBase['status'],
            ];
            $id = $this->users->create($payload);
            $this->users->updateRoles($id, $roleIds);
            $this->auditoria->registrar('crear', 'usuarios', $id, null, ['email' => $email, 'roles' => $roleIds]);
            $db->commit();
            return $id;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function actualizar(int $id, array $data): void
    {
        $usuario = $this->users->find($id);
        if (!$usuario) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $perfil = $this->normalizarPerfil($data);
        $roleIds = $this->normalizarRoles($data);
        $password = (string) ($data['password'] ?? '');

        if ($id === Auth::id() && $perfil['status'] !== 'activo') {
            throw new RuntimeException('No puedes desactivar o bloquear tu propia cuenta.');
        }
        if ($this->users->emailExists($perfil['email'], $id)) {
            throw new RuntimeException('Ya existe un usuario con ese email.');
        }

        $rolesAntes = $this->users->rolesForUser($id);
        $this->validarUsuarioProtegido($id, $rolesAntes);
        $this->validarRolLicenciante($roleIds, $rolesAntes);
        $this->validarSuperadminActivo($id, $perfil['status'], $roleIds, $rolesAntes);

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $this->users->update($id, $perfil);
            $this->users->updateRoles($id, $roleIds);

            $cambios = [
                'perfil' => [
                    'name' => $perfil['name'],
                    'email' => $perfil['email'],
                    'phone' => $perfil['phone'],
                    'status' => $perfil['status'],
                ],
                'roles' => $roleIds,
            ];

            if ($password !== '') {
                $this->validarPassword($password);
                $this->users->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
                $cambios['password_reset'] = true;
            }

            $this->auditoria->registrar('editar', 'usuarios', $id, [
                'name' => $usuario['name'],
                'email' => $usuario['email'],
                'phone' => $usuario['phone'],
                'status' => $usuario['status'],
                'roles' => array_column($rolesAntes, 'id'),
            ], $cambios);
            $db->commit();
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function cambiarStatus(int $id, string $status): void
    {
        if (!in_array($status, ['activo', 'inactivo', 'bloqueado'], true)) {
            throw new RuntimeException('Estatus de usuario no valido.');
        }

        $usuario = $this->users->find($id);
        if (!$usuario) {
            throw new RuntimeException('Usuario no encontrado.');
        }
        if ($id === Auth::id() && $status !== 'activo') {
            throw new RuntimeException('No puedes desactivar o bloquear tu propia cuenta.');
        }

        $roles = $this->users->rolesForUser($id);
        $this->validarUsuarioProtegido($id, $roles);
        $this->validarSuperadminActivo($id, $status, array_column($roles, 'id'), $roles);

        $this->users->updateStatus($id, $status);
        $this->auditoria->registrar('cambiar_estado', 'usuarios', $id, ['status' => $usuario['status']], ['status' => $status]);
    }

    public function eliminar(int $id): void
    {
        if (!(new LicenciaService())->usuarioActualEsLicenciante()) {
            throw new RuntimeException('Sólo el administrador de licencias puede eliminar usuarios.');
        }
        if ($id === Auth::id()) {
            throw new RuntimeException('No puedes eliminar tu propia cuenta.');
        }

        $usuario = $this->users->find($id);
        if (!$usuario) {
            throw new RuntimeException('Usuario no encontrado.');
        }

        $roles = $this->users->rolesForUser($id);
        $this->validarSuperadminActivo($id, 'inactivo', [], $roles);
        if (in_array(LicenciaService::ROLE, array_column($roles, 'name'), true)) {
            throw new RuntimeException('No se puede eliminar una cuenta de licenciamiento.');
        }

        $this->users->softDelete($id);
        $this->auditoria->registrar('eliminar', 'usuarios', $id, $usuario, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    private function normalizarPerfil(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($name === '' || $email === '') {
            throw new RuntimeException('Nombre y email son obligatorios.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El email no es valido.');
        }

        $status = (string) ($data['status'] ?? 'activo');
        return [
            'name' => $name,
            'email' => $email,
            'phone' => normalizarTelefono((string) ($data['phone'] ?? '')) ?: null,
            'status' => in_array($status, ['activo', 'inactivo', 'bloqueado'], true) ? $status : 'activo',
        ];
    }

    private function normalizarRoles(array $data): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', (array) ($data['roles'] ?? [])))));
        if ($roleIds === []) {
            throw new RuntimeException('Selecciona al menos un rol.');
        }

        return $roleIds;
    }

    private function validarPassword(string $password): void
    {
        if (strlen($password) < 8) {
            throw new RuntimeException('La contrasena debe tener al menos 8 caracteres.');
        }
    }

    private function validarSuperadminActivo(int $id, string $nuevoStatus, array $roleIdsNuevos, array $rolesAntes): void
    {
        $eraSuperadmin = in_array('superadmin', array_column($rolesAntes, 'name'), true);
        if (!$eraSuperadmin) {
            return;
        }

        $rolesDisponibles = $this->users->roles();
        $superadminId = null;
        foreach ($rolesDisponibles as $role) {
            if ($role['name'] === 'superadmin') {
                $superadminId = (int) $role['id'];
                break;
            }
        }

        $seguiraSuperadminActivo = $nuevoStatus === 'activo' && $superadminId !== null && in_array($superadminId, $roleIdsNuevos, true);
        if ($seguiraSuperadminActivo) {
            return;
        }

        if ($id === Auth::id()) {
            throw new RuntimeException('No puedes quitarte tu propio acceso de superadmin activo.');
        }
        if ($this->users->countActiveSuperadmins() <= 1) {
            throw new RuntimeException('Debe existir al menos un superadmin activo.');
        }
    }

    private function validarRolLicenciante(array $roleIdsNuevos, array $rolesAntes): void
    {
        $licencianteId = $this->users->roleIdByName(LicenciaService::ROLE);
        if ($licencianteId === null) {
            return;
        }

        $teniaLicenciante = in_array(LicenciaService::ROLE, array_column($rolesAntes, 'name'), true);
        $tendraLicenciante = in_array($licencianteId, $roleIdsNuevos, true);

        if (($teniaLicenciante || $tendraLicenciante) && !(new LicenciaService())->usuarioActualEsLicenciante()) {
            throw new RuntimeException('Sólo el administrador de licencias puede asignar o modificar el rol licenciante.');
        }
    }

    private function validarUsuarioProtegido(int $id, array $rolesAntes): void
    {
        if (in_array(LicenciaService::ROLE, array_column($rolesAntes, 'name'), true) && !(new LicenciaService())->usuarioActualEsLicenciante()) {
            throw new RuntimeException('Esta cuenta de licenciamiento está protegida.');
        }
    }
}
