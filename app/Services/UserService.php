<?php

declare(strict_types=1);

namespace App\Services;

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

    public function roles(): array
    {
        return $this->users->roles();
    }

    public function crear(array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $password = (string) ($data['password'] ?? '');
        $roleIds = array_filter(array_map('intval', (array) ($data['roles'] ?? [])));

        if ($name === '' || $email === '' || $password === '') {
            throw new RuntimeException('Nombre, email y contrasena son obligatorios.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('La contrasena debe tener al menos 8 caracteres.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('El email no es valido.');
        }
        if ($roleIds === []) {
            throw new RuntimeException('Selecciona al menos un rol.');
        }
        if ($this->users->findByEmail($email)) {
            throw new RuntimeException('Ya existe un usuario con ese email.');
        }

        $db = Database::connection();
        $db->beginTransaction();
        try {
            $payload = [
                'name' => $name,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'phone' => normalizarTelefono((string) ($data['phone'] ?? '')) ?: null,
                'status' => in_array(($data['status'] ?? 'activo'), ['activo', 'inactivo', 'bloqueado'], true) ? $data['status'] : 'activo',
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
}
