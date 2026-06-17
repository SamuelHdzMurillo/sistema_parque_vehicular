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

    /** @return array{data: list<array<string, mixed>>, total: int, page: int, per_page: int} */
    public function paginate(int $page = 1, int $perPage = 30, ?string $modulo = null, ?string $accion = null): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $where = [];
        $params = [];

        if ($modulo !== null && $modulo !== '') {
            $where[] = 'a.tabla_afectada = ?';
            $params[] = $modulo;
        }
        if ($accion !== null && $accion !== '') {
            if (strtoupper($accion) === 'NUEVO') {
                $where[] = 'UPPER(a.accion) IN ("CREATE", "INSERT")';
            } else {
                $where[] = 'UPPER(a.accion) = ?';
                $params[] = strtoupper($accion);
            }
        }

        $sqlWhere = $where === [] ? '' : ' WHERE ' . implode(' AND ', $where);
        $total = (int) ($this->fetchOne('SELECT COUNT(*) AS c FROM auditoria a' . $sqlWhere, $params)['c'] ?? 0);
        $rows = $this->fetchAll(
            'SELECT a.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario
             FROM auditoria a LEFT JOIN users u ON u.id = a.user_id'
            . $sqlWhere
            . ' ORDER BY a.created_at DESC LIMIT ? OFFSET ?',
            array_merge($params, [$perPage, $offset])
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }
}
