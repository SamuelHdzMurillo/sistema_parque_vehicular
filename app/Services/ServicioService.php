<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ServicioRepository;

final class ServicioService
{
    public function __construct(
        private readonly ServicioRepository $repo = new ServicioRepository(),
    ) {
    }

    public function paginate(int $page = 1, array $filters = []): array
    {
        return $this->repo->paginate($page, 15, $filters);
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data): int|string
    {
        $clean = $this->sanitizeForCreate($data);
        if (is_string($clean)) {
            return $clean;
        }
        if ($this->repo->tipoExists($clean['tipo'])) {
            return 'Ya existe un servicio con ese código interno.';
        }

        $id = $this->repo->create($clean);
        AuditService::log('CREATE', 'alerta_config', $id, null, [
            'tipo' => $clean['tipo'],
            'nombre' => $clean['nombre'],
            'unidad' => 'km',
        ]);

        return $id;
    }

    /** @return array{error: ?string, tipo: ?string} */
    public function createWithTipo(array $data): array
    {
        $result = $this->create($data);
        if (is_string($result)) {
            return ['error' => $result, 'tipo' => null];
        }

        $servicio = $this->repo->findById($result);

        return ['error' => null, 'tipo' => $servicio !== null ? (string) $servicio['tipo'] : null];
    }

    public function update(int $id, array $data): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }

        $clean = $this->sanitizeForUpdate($data);
        if (is_string($clean)) {
            return $clean;
        }

        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'alerta_config', $id, $before, $clean);
        }

        return $result;
    }

    public function setActivo(int $id, bool $activo): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }

        $result = $this->repo->setActivo($id, $activo);
        if ($result) {
            AuditService::log('UPDATE', 'alerta_config', $id, $before, ['activo' => $activo ? 1 : 0]);
        }

        return $result;
    }

    public function delete(int $id): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }

        $tipo = (string) $before['tipo'];
        if ($this->repo->countMantenimientos($tipo) > 0) {
            return 'No se puede eliminar: hay mantenimientos registrados con este servicio. Desactívelo en su lugar.';
        }
        if ($this->repo->countAlertas($tipo) > 0) {
            return 'No se puede eliminar: hay alertas asociadas a este servicio. Desactívelo en su lugar.';
        }

        $result = $this->repo->delete($id);
        if ($result) {
            AuditService::log('DELETE', 'alerta_config', $id, $before, null);
        }

        return $result;
    }

    private function sanitizeForCreate(array $data): array|string
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return 'El nombre del servicio es obligatorio.';
        }

        $tipo = trim((string) ($data['tipo'] ?? ''));
        $tipo = $tipo !== '' ? alerta_servicio_slug($tipo) : alerta_servicio_slug($nombre);
        if ($tipo === '' || !preg_match('/^[a-z][a-z0-9_]{1,48}$/', $tipo)) {
            return 'El código interno debe usar letras minúsculas, números y guión bajo (ej. revision_frenos).';
        }

        return [
            'tipo' => $tipo,
            'nombre' => $nombre,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }

    private function sanitizeForUpdate(array $data): array|string
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            return 'El nombre del servicio es obligatorio.';
        }

        return [
            'nombre' => $nombre,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }
}
