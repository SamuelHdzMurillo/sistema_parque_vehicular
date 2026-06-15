<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\BaseRepository;
use App\Services\AuditService;

final class DanioService extends BaseRepository
{
    public function paginate(int $page = 1, ?string $estado = null): array
    {
        $perPage = 15;
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';
        if ($estado) {
            $where .= ' AND d.estado = ?';
            $params[] = $estado;
        }
        $total = (int) ($this->fetchOne("SELECT COUNT(*) AS c FROM danios d {$where}", $params)['c'] ?? 0);
        $params[] = $perPage;
        $params[] = $offset;
        $rows = $this->fetchAll(
            "SELECT d.*, v.numero_economico, v.placas
             FROM danios d JOIN vehiculos v ON v.id = d.vehiculo_id
             {$where} ORDER BY d.created_at DESC LIMIT ? OFFSET ?",
            $params
        );
        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function find(int $id): ?array
    {
        $danio = $this->fetchOne(
            'SELECT d.*, v.numero_economico, v.placas FROM danios d JOIN vehiculos v ON v.id = d.vehiculo_id WHERE d.id = ?',
            [$id]
        );
        if ($danio === null) {
            return null;
        }
        return [
            'danio' => $danio,
            'fotos' => $this->fetchAll('SELECT * FROM danio_fotos WHERE danio_id = ?', [$id]),
            'seguimiento' => $this->fetchAll(
                'SELECT ds.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario
                 FROM danio_seguimiento ds JOIN users u ON u.id = ds.user_id
                 WHERE ds.danio_id = ? ORDER BY ds.created_at DESC',
                [$id]
            ),
        ];
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->fetchAll(
                'SELECT id, numero_economico, placas FROM vehiculos WHERE deleted_at IS NULL ORDER BY numero_economico'
            ),
        ];
    }

    public function create(array $data, int $userId): int
    {
        $this->execute(
            'INSERT INTO danios (vehiculo_id, tipo_dano, ubicacion, descripcion, estado, reportado_por)
             VALUES (?, ?, ?, ?, "reportado", ?)',
            [(int) $data['vehiculo_id'], $data['tipo_dano'], $data['ubicacion'], $data['descripcion'], $userId]
        );
        $id = (int) $this->lastInsertId();
        $file = $data['foto'] ?? null;
        if (is_array($file)) {
            $ruta = FileUploader::uploadImage($file, 'danios/' . $id);
            if ($ruta !== null) {
                $this->execute('INSERT INTO danio_fotos (danio_id, ruta) VALUES (?, ?)', [$id, $ruta]);
            }
        }
        AuditService::log('INSERT', 'danios', $id, null, $data);
        return $id;
    }

    public function updateEstado(int $id, string $estado, int $userId, ?string $comentario = null): ?string
    {
        $danio = $this->fetchOne('SELECT * FROM danios WHERE id = ?', [$id]);
        if ($danio === null) {
            return 'Daño no encontrado.';
        }
        $estadosValidos = ['reportado', 'en_evaluacion', 'en_reparacion', 'reparado', 'cerrado_sin_accion'];
        if (!in_array($estado, $estadosValidos, true)) {
            return 'Estado no válido.';
        }
        $this->execute('UPDATE danios SET estado = ? WHERE id = ?', [$estado, $id]);
        $this->execute(
            'INSERT INTO danio_seguimiento (danio_id, estado_anterior, estado_nuevo, comentario, user_id)
             VALUES (?, ?, ?, ?, ?)',
            [$id, $danio['estado'], $estado, $comentario, $userId]
        );
        AuditService::log('UPDATE', 'danios', $id, $danio, ['estado' => $estado]);
        return null;
    }
}
