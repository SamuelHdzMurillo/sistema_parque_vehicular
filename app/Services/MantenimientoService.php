<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CatalogoRepository;
use App\Repositories\MantenimientoRepository;
use App\Repositories\VehiculoRepository;

final class MantenimientoService
{
    public function __construct(
        private readonly MantenimientoRepository $repo = new MantenimientoRepository(),
        private readonly VehiculoRepository $vehiculos = new VehiculoRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function paginate(int $page = 1, ?string $estado = null): array
    {
        return $this->repo->paginate($page, 15, array_filter(['estado' => $estado]));
    }

    public function getFormData(): array
    {
        return [
            'vehiculos' => $this->catalogos->getVehiculosDisponibles(),
            'proveedores' => $this->catalogos->getProveedores('mantenimiento'),
            'responsables' => $this->catalogos->getUsersForSelect(),
            'tipos' => ['preventivo', 'correctivo', 'predictivo'],
            'estados' => ['pendiente', 'programado', 'autorizado', 'en_proceso', 'finalizado', 'cancelado'],
        ];
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function create(array $data, int $userId): int
    {
        $data['folio'] = $this->repo->generateFolio();
        $data['created_by'] = $userId;
        $data['responsable_id'] = (int) ($data['responsable_id'] ?? $userId);
        $data['estado'] = $data['estado'] ?? 'pendiente';
        $id = $this->repo->create($data);
        AuditService::log('CREATE', 'mantenimientos', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $result = $this->repo->update($id, array_merge($before, $data));
        if ($result) {
            AuditService::log('UPDATE', 'mantenimientos', $id, $before, $data);
        }
        return $result;
    }

    public function autorizar(int $id, int $userId): ?string
    {
        try {
            $mant = $this->repo->findById($id);
            if ($mant === null) {
                return 'Mantenimiento no encontrado.';
            }
            if (!in_array($mant['estado'], ['pendiente', 'programado'], true)) {
                return 'Estado no válido para autorizar.';
            }
            $this->repo->authorize($id, $userId);
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }

    public function finalizar(int $id): ?string
    {
        try {
            $mant = $this->repo->findById($id);
            if ($mant === null) {
                return 'Mantenimiento no encontrado.';
            }
            if (!in_array($mant['estado'], ['autorizado', 'en_proceso'], true)) {
                return 'No se puede finalizar en este estado.';
            }
            $this->repo->update($id, array_merge($mant, ['estado' => 'finalizado']));
            $vehiculoId = (int) $mant['vehiculo_id'];
            $this->vehiculos->updateKilometraje($vehiculoId, (int) $mant['kilometraje'], auth_id());
            $this->vehiculos->updateEstado($vehiculoId, 'disponible', 'Fin mantenimiento ' . $mant['folio'], auth_id());
            return null;
        } catch (\Throwable $e) {
            return $e->getMessage();
        }
    }
}
