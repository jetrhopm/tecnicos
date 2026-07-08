<?php

declare(strict_types=1);

namespace App\Repositories;

final class UserRepository extends BaseRepository
{
    public function find(int $id): ?array
    {
        return $this->fetch('SELECT id, name, email, phone, status, last_login_at FROM users WHERE id = :id AND deleted_at IS NULL', ['id' => $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1', ['email' => $email]);
    }

    public function activeTechnicians(): array
    {
        return $this->fetchAll(
            "SELECT DISTINCT u.id, u.name
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.status = 'activo' AND r.name IN ('tecnico','tecnico_senior','admin','superadmin')
             ORDER BY u.name"
        );
    }

    public function idsPorRoles(array $slugs): array
    {
        // IDs de usuarios activos que tienen alguno de los roles dados (por name).
        $slugs = array_values(array_filter($slugs));
        if ($slugs === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($slugs), '?'));
        $stmt = $this->db->prepare(
            "SELECT DISTINCT u.id
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.status = 'activo' AND u.deleted_at IS NULL AND r.name IN ({$placeholders})"
        );
        $stmt->execute($slugs);

        return array_map('intval', array_column($stmt->fetchAll(), 'id'));
    }

    public function rolesForUser(int $userId): array
    {
        return $this->fetchAll(
            'SELECT r.id, r.name, r.label FROM roles r JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    public function emailExists(string $email, ?int $exceptId = null): bool
    {
        $row = $this->fetch(
            'SELECT id FROM users WHERE email = :email AND deleted_at IS NULL AND (:id_a IS NULL OR id <> :id_b) LIMIT 1',
            ['email' => $email, 'id_a' => $exceptId, 'id_b' => $exceptId]
        );

        return $row !== null;
    }

    public function hasPermission(int $userId, string $module, string $action): bool
    {
        $row = $this->fetch(
            "SELECT 1
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             JOIN role_permissions rp ON rp.role_id = r.id
             JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :user_id
               AND u.status = 'activo'
               AND (r.name = 'superadmin' OR (p.module = :module AND p.action = :action))
             LIMIT 1",
            ['user_id' => $userId, 'module' => $module, 'action' => $action]
        );

        return $row !== null;
    }

    public function markLogin(int $userId): void
    {
        $this->execute('UPDATE users SET last_login_at = NOW() WHERE id = :id', ['id' => $userId]);
    }

    public function roles(): array
    {
        return $this->fetchAll('SELECT id, name, label FROM roles ORDER BY label');
    }

    public function create(array $data): int
    {
        return $this->insert(
            "INSERT INTO users (name, email, password, phone, status)
             VALUES (:name, :email, :password, :phone, :status)",
            $data
        );
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $this->execute(
            'UPDATE users SET name = :name, email = :email, phone = :phone, status = :status WHERE id = :id',
            $data
        );
    }

    public function updatePassword(int $id, string $hash): void
    {
        $this->execute('UPDATE users SET password = :password WHERE id = :id', ['id' => $id, 'password' => $hash]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->execute('UPDATE users SET status = :status WHERE id = :id', ['id' => $id, 'status' => $status]);
    }

    public function updateRoles(int $userId, array $roleIds): void
    {
        $this->execute('DELETE FROM user_roles WHERE user_id = :user_id', ['user_id' => $userId]);
        $stmt = $this->db->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
        foreach ($roleIds as $roleId) {
            $stmt->execute(['user_id' => $userId, 'role_id' => (int) $roleId]);
        }
    }

    public function all(): array
    {
        return $this->fetchAll(
            "SELECT u.id, u.name, u.email, u.phone, u.status, u.last_login_at, GROUP_CONCAT(r.label ORDER BY r.label SEPARATOR ', ') roles
             FROM users u
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             LEFT JOIN roles r ON r.id = ur.role_id
             WHERE u.deleted_at IS NULL
             GROUP BY u.id
             ORDER BY u.name"
        );
    }

    public function countActiveSuperadmins(): int
    {
        $row = $this->fetch(
            "SELECT COUNT(DISTINCT u.id) total
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id
             JOIN roles r ON r.id = ur.role_id
             WHERE u.deleted_at IS NULL AND u.status = 'activo' AND r.name = 'superadmin'"
        );

        return (int) ($row['total'] ?? 0);
    }
}
