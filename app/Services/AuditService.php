<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\BaseRepository;

final class AuditService extends BaseRepository
{
    public static function log(
        string $action,
        string $table,
        ?int $recordId,
        ?array $before,
        ?array $after
    ): void {
        $db = Database::getInstance()->pdo();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $db->exec("SET @audit_ip = " . $db->quote($ip));
        $stmt = $db->prepare(
            'INSERT INTO auditoria (user_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, ip_address, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            auth_id(),
            $action,
            $table,
            $recordId,
            $before !== null ? json_encode($before, JSON_UNESCAPED_UNICODE) : null,
            $after !== null ? json_encode($after, JSON_UNESCAPED_UNICODE) : null,
            $ip,
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
        ]);
    }

    public function paginate(int $page = 1, int $perPage = 30): array
    {
        $offset = ($page - 1) * $perPage;
        $total = (int) ($this->fetchOne('SELECT COUNT(*) AS c FROM auditoria')['c'] ?? 0);
        $rows = $this->fetchAll(
            'SELECT a.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario
             FROM auditoria a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            [$perPage, $offset]
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page];
    }
}
