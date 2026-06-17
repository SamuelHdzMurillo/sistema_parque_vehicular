<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AreaRepository;
use App\Repositories\PlantelRepository;

final class AreaService
{
    public function __construct(
        private readonly AreaRepository $repo = new AreaRepository(),
        private readonly PlantelRepository $planteles = new PlantelRepository(),
    ) {
    }

    public function paginate(int $page = 1, array $filters = []): array
    {
        $result = $this->repo->paginate($page, 15, $filters);
        $result['planteles'] = $this->planteles->listForSelect(false);
        return $result;
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function getFormData(): array
    {
        return ['planteles' => $this->planteles->listForSelect()];
    }

    public function create(array $data): int|string
    {
        $clean = $this->sanitize($data);
        if (is_string($clean)) {
            return $clean;
        }
        if ($this->repo->findByClave($clean['clave']) !== null) {
            return 'Ya existe un área con esa clave.';
        }
        if ($this->planteles->findById($clean['plantel_id']) === null) {
            return 'El plantel seleccionado no es válido.';
        }
        $id = $this->repo->create($clean);
        AuditService::log('CREATE', 'areas', $id, null, $clean);
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
            return 'Ya existe otra área con esa clave.';
        }
        if ($this->planteles->findById($clean['plantel_id']) === null) {
            return 'El plantel seleccionado no es válido.';
        }
        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'areas', $id, $before, $clean);
        }
        return $result;
    }

    public function setActivo(int $id, bool $activo): bool|string
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        if (!$activo && $this->repo->countConductores($id) > 0) {
            return 'No puede desactivar un área con conductores asignados.';
        }
        $result = $this->repo->setActivo($id, $activo);
        if ($result) {
            AuditService::log('UPDATE', 'areas', $id, $before, ['activo' => $activo ? 1 : 0]);
        }
        return $result;
    }

    private function sanitize(array $data): array|string
    {
        $clave = strtoupper(trim((string) ($data['clave'] ?? '')));
        $nombre = trim((string) ($data['nombre'] ?? ''));
        $plantelId = (int) ($data['plantel_id'] ?? 0);
        if ($clave === '') {
            return 'La clave del área es obligatoria.';
        }
        if ($nombre === '') {
            return 'El nombre del área es obligatorio.';
        }
        if ($plantelId <= 0) {
            return 'Debe seleccionar un plantel.';
        }
        return [
            'clave' => $clave,
            'nombre' => $nombre,
            'plantel_id' => $plantelId,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }
}
