<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\BaseRepository;

final class UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT u.*, r.slug AS role_slug, r.nombre AS role_nombre
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.email = ? AND u.deleted_at IS NULL',
            [$email]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT u.*, r.slug AS role_slug, r.nombre AS role_nombre
             FROM users u JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? AND u.deleted_at IS NULL',
            [$id]
        );
    }

    public function getPermissionsByRole(int $roleId): array
    {
        return $this->fetchAll(
            'SELECT p.slug FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?',
            [$roleId]
        );
    }

    public function incrementFailedAttempts(int $userId): void
    {
        $this->execute('UPDATE users SET intentos_fallidos = intentos_fallidos + 1 WHERE id = ?', [$userId]);
    }

    public function resetFailedAttempts(int $userId): void
    {
        $this->execute(
            'UPDATE users SET intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = ?',
            [$userId]
        );
    }

    public function lockUntil(int $userId, int $minutes): void
    {
        $until = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        $this->execute('UPDATE users SET bloqueado_hasta = ? WHERE id = ?', [$until, $userId]);
    }

    public function updateLastAccess(int $userId): void
    {
        $this->execute('UPDATE users SET ultimo_acceso = NOW() WHERE id = ?', [$userId]);
    }

    public function createSession(int $userId, string $token, ?string $rememberHash, string $ip, string $ua, string $expires): void
    {
        $this->execute(
            'INSERT INTO user_sessions (user_id, session_token, remember_token_hash, ip_address, user_agent, ultimo_uso, expira_en)
             VALUES (?, ?, ?, ?, ?, NOW(), ?)',
            [$userId, $token, $rememberHash, $ip, substr($ua, 0, 500), $expires]
        );
    }

    public function invalidateSession(string $token): void
    {
        $this->execute('UPDATE user_sessions SET activa = 0 WHERE session_token = ?', [$token]);
    }

    public function findActiveSession(string $token): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM user_sessions WHERE session_token = ? AND activa = 1 AND expira_en > NOW()',
            [$token]
        );
    }

    public function logAccess(?int $userId, string $email, bool $success, ?string $reason, string $ip, string $ua): void
    {
        $this->execute(
            'INSERT INTO access_logs (user_id, email_intentado, exito, motivo, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)',
            [$userId, $email, $success ? 1 : 0, $reason, $ip, substr($ua, 0, 500)]
        );
    }

    public function updatePassword(int $userId, string $hash): void
    {
        $this->execute(
            'UPDATE users SET password_hash = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?',
            [$hash, $userId]
        );
    }

    public function createPasswordReset(int $userId, string $tokenHash, string $expires): void
    {
        $this->execute('UPDATE password_resets SET usado = 1 WHERE user_id = ? AND usado = 0', [$userId]);
        $this->execute(
            'INSERT INTO password_resets (user_id, token_hash, expira_en) VALUES (?, ?, ?)',
            [$userId, $tokenHash, $expires]
        );
    }

    public function findValidResetToken(string $tokenHash): ?array
    {
        return $this->fetchOne(
            'SELECT pr.*, u.email FROM password_resets pr
             JOIN users u ON u.id = pr.user_id
             WHERE pr.token_hash = ? AND pr.usado = 0 AND pr.expira_en > NOW()',
            [$tokenHash]
        );
    }

    public function markResetUsed(int $id): void
    {
        $this->execute('UPDATE password_resets SET usado = 1 WHERE id = ?', [$id]);
    }

    public function paginate(int $page = 1, int $perPage = 15, ?string $search = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE u.deleted_at IS NULL';
        if ($search) {
            $where .= ' AND (u.nombre LIKE ? OR u.email LIKE ?)';
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        $total = (int) ($this->fetchOne("SELECT COUNT(*) AS c FROM users u {$where}", $params)['c'] ?? 0);
        $params[] = $perPage;
        $params[] = $offset;
        $rows = $this->fetchAll(
            "SELECT u.id, u.nombre, u.apellido_paterno, u.email, u.activo, r.nombre AS rol, a.nombre AS area
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN areas a ON a.id = u.area_id
             {$where} ORDER BY u.nombre LIMIT ? OFFSET ?",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function getActiveSessions(int $userId): array
    {
        return $this->fetchAll(
            'SELECT * FROM user_sessions WHERE user_id = ? AND activa = 1 ORDER BY ultimo_uso DESC',
            [$userId]
        );
    }

    public function getAccessLogs(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT al.*, u.email AS user_email FROM access_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC LIMIT ?',
            [$limit]
        );
    }

    public function getRoles(): array
    {
        return $this->fetchAll('SELECT id, nombre FROM roles WHERE activo = 1 ORDER BY nombre');
    }

    public function getAreas(): array
    {
        return $this->fetchAll('SELECT id, nombre FROM areas WHERE activo = 1 ORDER BY nombre');
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO users (role_id, area_id, nombre, apellido_paterno, apellido_materno, email, telefono, password_hash, activo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)',
            [
                $data['role_id'], $data['area_id'], $data['nombre'], $data['apellido_paterno'],
                $data['apellido_materno'], $data['email'], $data['telefono'], $data['password_hash'],
            ]
        );
        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $this->execute(
            'UPDATE users SET role_id=?, area_id=?, nombre=?, apellido_paterno=?, apellido_materno=?,
             email=?, telefono=?, activo=? WHERE id=? AND deleted_at IS NULL',
            [
                $data['role_id'], $data['area_id'], $data['nombre'], $data['apellido_paterno'],
                $data['apellido_materno'], $data['email'], $data['telefono'], $data['activo'], $id,
            ]
        );
    }
}
