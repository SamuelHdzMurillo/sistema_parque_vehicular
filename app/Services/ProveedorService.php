<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProveedorRepository;

final class ProveedorService
{
    public const TIPOS = ['mantenimiento', 'combustible', 'ambos', 'otro'];

    public function __construct(
        private readonly ProveedorRepository $repo = new ProveedorRepository()
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

    public function getTipos(): array
    {
        return self::TIPOS;
    }

    public function create(array $data): int
    {
        $clean = $this->sanitize($data);
        $id = $this->repo->create($clean);
        AuditService::log('CREATE', 'proveedores', $id, null, $clean);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $clean = $this->sanitize($data);
        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'proveedores', $id, $before, $clean);
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
            AuditService::log('UPDATE', 'proveedores', $id, $before, ['activo' => $activo ? 1 : 0]);
        }
        return $result;
    }

    private function sanitize(array $data): array
    {
        $tipo = $data['tipo'] ?? 'ambos';
        if (!in_array($tipo, self::TIPOS, true)) {
            $tipo = 'ambos';
        }
        return [
            'razon_social' => trim((string) ($data['razon_social'] ?? '')),
            'rfc' => $data['rfc'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'email' => $data['email'] ?? null,
            'direccion' => $data['direccion'] ?? null,
            'tipo' => $tipo,
            'activo' => isset($data['activo']) ? (int) (bool) $data['activo'] : 1,
        ];
    }
}
