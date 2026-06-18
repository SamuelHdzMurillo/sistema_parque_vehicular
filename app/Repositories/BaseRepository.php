<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

abstract class BaseRepository
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->pdo();
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        if (!$stmt->execute($params)) {
            $info = $stmt->errorInfo();
            throw new \PDOException(
                ($info[2] ?? 'Error al ejecutar la consulta.') . ' [SQL: ' . preg_replace('/\s+/', ' ', trim($sql)) . ']',
                (int) ($info[0] ?? 0)
            );
        }
        return true;
    }

    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->db->commit();
    }

    public function rollBack(): bool
    {
        return $this->db->inTransaction() ? $this->db->rollBack() : false;
    }

    protected function lastInsertId(): string
    {
        return Database::getInstance()->lastInsertId();
    }
}
