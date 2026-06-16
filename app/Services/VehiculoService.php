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
        $fotos = $data['fotos'] ?? [];
        if (!empty($data['foto']) && is_array($data['foto'])) {
            array_unshift($fotos, $data['foto']);
        }
        unset($data['foto'], $data['fotos']);
        $data['foto_principal'] = null;
        $id = $this->repo->create($data);
        foreach ($fotos as $index => $foto) {
            if (!is_array($foto)) {
                continue;
            }
            $ruta = FileUploader::uploadImage($foto, 'vehiculos/' . $id);
            if ($ruta !== null) {
                $this->repo->addFoto($id, $ruta, null, $index === 0);
            }
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

    /** @param list<array> $files */
    public function uploadFotos(int $id, array $files, ?string $descripcion, bool $marcarPrimeraComoPrincipal): void
    {
        $vehiculo = $this->repo->findById($id);
        if ($vehiculo === null) {
            throw new RuntimeException('Vehículo no encontrado.');
        }
        if ($files === []) {
            throw new RuntimeException('Seleccione al menos una imagen.');
        }
        $sinPrincipal = empty($vehiculo['foto_principal']);
        $subidas = 0;
        foreach ($files as $index => $file) {
            if (!is_array($file)) {
                continue;
            }
            $esPrincipal = ($marcarPrimeraComoPrincipal && $index === 0) || ($sinPrincipal && $subidas === 0);
            $ruta = FileUploader::uploadImage($file, 'vehiculos/' . $id);
            if ($ruta === null) {
                continue;
            }
            $desc = ($index === 0 && $descripcion) ? $descripcion : null;
            $this->repo->addFoto($id, $ruta, $desc, $esPrincipal);
            $subidas++;
        }
        if ($subidas === 0) {
            throw new RuntimeException('No se pudo cargar ninguna imagen.');
        }
    }

    public function setFotoPrincipal(int $vehiculoId, int $fotoId): void
    {
        if ($this->repo->findById($vehiculoId) === null) {
            throw new RuntimeException('Vehículo no encontrado.');
        }
        if (!$this->repo->setFotoPrincipal($vehiculoId, $fotoId)) {
            throw new RuntimeException('Fotografía no encontrada.');
        }
    }

    public function deleteFoto(int $vehiculoId, int $fotoId): void
    {
        if ($this->repo->findById($vehiculoId) === null) {
            throw new RuntimeException('Vehículo no encontrado.');
        }
        $foto = $this->repo->deleteFoto($vehiculoId, $fotoId);
        if ($foto === null) {
            throw new RuntimeException('Fotografía no encontrada.');
        }
        $path = storage_path('uploads/' . ltrim((string) $foto['ruta'], '/'));
        if (is_file($path)) {
            unlink($path);
        }
    }

    public function getExpediente(int $id): ?array
    {
        return $this->repo->getExpedienteData($id);
    }

    private function validateUniqueFields(array $data, ?int $excludeId = null): void
    {
        if ($this->repo->existsNumeroEconomico($data['numero_economico'], $excludeId)) {
            throw new RuntimeException('Ese identificador ya está registrado.');
        }
    }
}
