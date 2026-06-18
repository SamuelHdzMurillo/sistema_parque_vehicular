<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AreaRepository;
use App\Repositories\ConductorRepository;
use App\Repositories\PlantelRepository;

final class ConductorService
{
    public function __construct(
        private readonly ConductorRepository $repo = new ConductorRepository(),
        private readonly AreaRepository $areas = new AreaRepository(),
        private readonly PlantelRepository $planteles = new PlantelRepository(),
    ) {
    }

    public function paginate(int $page = 1, array $filters = []): array
    {
        $result = $this->repo->paginate($page, 15, $filters);
        $result['areas'] = $this->areas->paginate(1, 500, ['activo' => '1'])['data'];
        return $result;
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function getFormData(): array
    {
        return [
            'areas' => $this->areas->paginate(1, 500, ['activo' => '1'])['data'],
            'planteles' => $this->planteles->listForSelect(),
        ];
    }

    public function create(array $data): int|string
    {
        $clean = $this->sanitize($data);
        if (is_string($clean)) {
            return $clean;
        }
        if ($this->areas->findById($clean['area_id']) === null) {
            return 'El área seleccionada no es válida.';
        }
        $id = $this->repo->create($clean);
        AuditService::log('CREATE', 'conductores', $id, null, $clean);
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
        if ($this->areas->findById($clean['area_id']) === null) {
            return 'El área seleccionada no es válida.';
        }
        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'conductores', $id, $before, $clean);
        }
        return $result;
    }

    public function setActivo(int $id, bool $activo): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $result = $this->repo->setActivo($id, $activo);
        if ($result) {
            AuditService::log('UPDATE', 'conductores', $id, $before, ['activo' => $activo ? 1 : 0]);
        }
        return $result;
    }

    private function sanitize(array $data): array|string
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $telefono = trim((string) ($data['telefono'] ?? ''));
        $areaId = (int) ($data['area_id'] ?? 0);
        if ($nombre === '') {
            return 'El nombre del conductor es obligatorio.';
        }
        if ($telefono === '') {
            return 'El teléfono es obligatorio.';
        }
        if ($areaId <= 0) {
            return 'Debe seleccionar un área.';
        }
        return [
            'nombre' => $nombre,
            'telefono' => $telefono,
            'area_id' => $areaId,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }
}
