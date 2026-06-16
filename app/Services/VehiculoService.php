<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\FileUploader;
use App\Repositories\CatalogoRepository;
use App\Repositories\HerramientaRepository;
use App\Repositories\VehiculoRepository;
use RuntimeException;

final class VehiculoService
{
    public function __construct(
        private readonly VehiculoRepository $repo = new VehiculoRepository(),
        private readonly HerramientaRepository $herramientas = new HerramientaRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
    ) {
    }

    public function find(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function paginate(int $page = 1, ?string $search = null, ?string $estado = null): array
    {
        $filters = array_filter([
            'search' => $search,
            'estado' => $estado,
        ]);
        return $this->repo->paginate($page, 15, $filters);
    }

    public function getFormData(): array
    {
        return [
            'areas' => $this->catalogos->getAreas(),
            'responsables' => $this->catalogos->getUsersForSelect(),
            'tipos_combustible' => ['gasolina', 'diesel', 'hibrido', 'electrico', 'gnc'],
            'estados' => ['activo', 'disponible', 'en_comision', 'en_mantenimiento', 'en_taller', 'fuera_servicio'],
        ];
    }

    public function create(array $data, int $userId): int
    {
        $this->validateUniqueFields($data);
        $data['created_by'] = $userId;
        if (!empty($data['foto']) && is_array($data['foto'])) {
            $data['foto_principal'] = FileUploader::uploadImage($data['foto'], 'vehiculos');
        }
        $id = $this->repo->create($data);
        if (!empty($data['foto_principal'])) {
            $this->repo->addFoto($id, $data['foto_principal'], 'Fotografía principal', true);
        }
        $this->herramientas->ensureDefaultsForVehiculo($id);
        AuditService::log('CREATE', 'vehiculos', $id, null, $data);
        return $id;
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $this->validateUniqueFields($data, $id);
        $data['updated_by'] = $userId;
        $data['foto_principal'] = $data['foto_principal'] ?? $before['foto_principal'] ?? null;
        $data['estado'] = $data['estado'] ?? $before['estado'];
        $data['kilometraje_actual'] = $data['kilometraje_actual'] ?? $before['kilometraje_actual'];
        $result = $this->repo->update($id, $data);
        if ($result) {
            AuditService::log('UPDATE', 'vehiculos', $id, $before, $data);
        }
        return $result;
    }

    public function softDelete(int $id, int $userId, ?string $motivo): bool
    {
        $motivo = $motivo ?: 'Baja definitiva del vehículo';
        $result = $this->repo->updateEstado($id, 'baja', $motivo, $userId);
        if ($result) {
            AuditService::log('UPDATE', 'vehiculos', $id, null, ['estado' => 'baja', 'motivo' => $motivo]);
        }
        return $result;
    }

    public function uploadFoto(int $id, array $file, ?string $descripcion, bool $principal): void
    {
        $vehiculo = $this->repo->findById($id);
        if ($vehiculo === null) {
            throw new RuntimeException('Vehículo no encontrado.');
        }
        if (!$principal && empty($vehiculo['foto_principal'])) {
            $principal = true;
        }
        $ruta = FileUploader::uploadImage($file, 'vehiculos/' . $id);
        if ($ruta === null) {
            throw new RuntimeException('No se pudo cargar la imagen.');
        }
        $this->repo->addFoto($id, $ruta, $descripcion, $principal);
    }

    public function getExpediente(int $id): ?array
    {
        return $this->repo->getExpedienteData($id);
    }

    private function validateUniqueFields(array $data, ?int $excludeId = null): void
    {
        if ($this->repo->existsNumeroEconomico($data['numero_economico'], $excludeId)) {
            throw new RuntimeException('El número económico ya está registrado.');
        }
    }
}
