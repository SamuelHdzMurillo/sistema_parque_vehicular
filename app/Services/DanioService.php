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
        $files = $this->normalizeFiles($data);
        $this->storeFotos($id, $files);
        AuditService::log('INSERT', 'danios', $id, null, $data);
        return $id;
    }

    /** @param list<array> $files */
    private function storeFotos(int $id, array $files): int
    {
        $subidas = 0;
        foreach ($files as $file) {
            if (!is_array($file)) {
                continue;
            }
            $ruta = FileUploader::uploadImage($file, 'danios/' . $id);
            if ($ruta === null) {
                continue;
            }
            $this->execute('INSERT INTO danio_fotos (danio_id, ruta) VALUES (?, ?)', [$id, $ruta]);
            $subidas++;
        }
        return $subidas;
    }

    /** @return list<array> */
    private function normalizeFiles(array $data): array
    {
        $files = $data['fotos'] ?? null;
        if (is_array($files) && isset($files[0])) {
            return $files;
        }
        $single = $data['foto'] ?? null;
        if (is_array($single) && isset($single['tmp_name'])) {
            return [$single];
        }
        return [];
    }

    /** @param list<array> $files */
    public function addFotos(int $id, array $files): int
    {
        if ($this->fetchOne('SELECT id FROM danios WHERE id = ?', [$id]) === null) {
            throw new \RuntimeException('Daño no encontrado.');
        }
        if ($files === []) {
            throw new \RuntimeException('Seleccione al menos una imagen.');
        }
        $count = $this->storeFotos($id, $files);
        if ($count === 0) {
            throw new \RuntimeException('No se pudo cargar ninguna imagen.');
        }
        AuditService::log('INSERT', 'danio_fotos', $id, null, ['fotos' => $count]);
        return $count;
    }

    public function deleteFoto(int $danioId, int $fotoId): void
    {
        $foto = $this->fetchOne('SELECT * FROM danio_fotos WHERE id = ? AND danio_id = ?', [$fotoId, $danioId]);
        if ($foto === null) {
            throw new \RuntimeException('Fotografía no encontrada.');
        }
        $this->execute('DELETE FROM danio_fotos WHERE id = ?', [$fotoId]);
        $path = storage_path('uploads/' . ltrim((string) $foto['ruta'], '/'));
        if (is_file($path)) {
            unlink($path);
        }
        AuditService::log('DELETE', 'danio_fotos', $fotoId, $foto, null);
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
