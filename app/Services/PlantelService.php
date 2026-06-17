<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PlantelRepository;

final class PlantelService
{
    public function __construct(
        private readonly PlantelRepository $repo = new PlantelRepository()
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

    public function listForSelect(): array
    {
        return $this->repo->listForSelect();
    }

    public function create(array $data): int|string
    {
        $clean = $this->sanitize($data);
        if (is_string($clean)) {
            return $clean;
        }
        if ($this->repo->findByClave($clean['clave']) !== null) {
            return 'Ya existe un plantel con esa clave.';
        }
        $id = $this->repo->create($clean);
        AuditService::log('CREATE', 'planteles', $id, null, $clean);
        return $id;
    }

    public function update(int $id, array $data): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $clean = $this->sanitize($data);
        if (is_string($clean)) {
            return $clean;
        }
        if ($this->repo->findByClave($clean['clave'], $id) !== null) {
            return 'Ya existe otro plantel con esa clave.';
        }
        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'planteles', $id, $before, $clean);
        }
        return $result;
    }

    public function setActivo(int $id, bool $activo): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        if (!$activo && $this->repo->countAreas($id) > 0) {
            return 'No puede desactivar un plantel con áreas asignadas.';
        }
        $result = $this->repo->setActivo($id, $activo);
        if ($result) {
            AuditService::log('UPDATE', 'planteles', $id, $before, ['activo' => $activo ? 1 : 0]);
        }
        return $result;
    }

    private function sanitize(array $data): array|string
    {
        $clave = strtoupper(trim((string) ($data['clave'] ?? '')));
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($clave === '') {
            return 'La clave del plantel es obligatoria.';
        }
        if ($nombre === '') {
            return 'El nombre del plantel es obligatorio.';
        }
        return [
            'clave' => $clave,
            'nombre' => $nombre,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }
}
