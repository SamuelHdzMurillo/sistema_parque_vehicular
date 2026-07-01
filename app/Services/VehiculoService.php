<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\ValidationException;
use App\Helpers\FileUploader;
use App\Repositories\AreaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\HerramientaRepository;
use App\Repositories\UserRepository;
use App\Repositories\VehiculoRepository;
use PDOException;
use RuntimeException;

final class VehiculoService
{
    private const TIPOS_COMBUSTIBLE = ['gasolina', 'diesel', 'hibrido', 'electrico', 'gnc'];
    private const ESTADOS_INICIALES = ['activo', 'disponible', 'en_comision', 'en_mantenimiento', 'en_taller', 'fuera_servicio'];

    public function __construct(
        private readonly VehiculoRepository $repo = new VehiculoRepository(),
        private readonly HerramientaRepository $herramientas = new HerramientaRepository(),
        private readonly CatalogoRepository $catalogos = new CatalogoRepository(),
        private readonly AreaRepository $areas = new AreaRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly AlertaService $alertas = new AlertaService(),
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
            'planteles' => $this->catalogos->getPlanteles(),
            'responsables' => $this->catalogos->getUsersForSelect(),
            'tipos_combustible' => self::TIPOS_COMBUSTIBLE,
            'estados' => self::ESTADOS_INICIALES,
        ];
    }

    /** @param array<string, mixed> $vehiculo */
    public function getFormDataForEdit(array $vehiculo): array
    {
        $data = $this->getFormData();

        $areaIds = array_map('intval', array_column($data['areas'], 'id'));
        $vehiculoAreaId = (int) ($vehiculo['area_id'] ?? 0);
        if ($vehiculoAreaId > 0 && !in_array($vehiculoAreaId, $areaIds, true)) {
            $data['areas'][] = [
                'id' => $vehiculoAreaId,
                'nombre' => $vehiculo['area_nombre'] ?? ('Área #' . $vehiculoAreaId),
                'plantel_clave' => $vehiculo['plantel_clave'] ?? '',
                'label' => $vehiculo['area_nombre'] ?? ('Área #' . $vehiculoAreaId),
            ];
        }

        $respIds = array_map('intval', array_column($data['responsables'], 'id'));
        $vehiculoRespId = (int) ($vehiculo['responsable_id'] ?? 0);
        if ($vehiculoRespId > 0 && !in_array($vehiculoRespId, $respIds, true)) {
            $nombreCompleto = trim((string) ($vehiculo['responsable_nombre'] ?? ''));
            $parts = preg_split('/\s+/', $nombreCompleto, 2) ?: [];
            $data['responsables'][] = [
                'id' => $vehiculoRespId,
                'nombre' => $parts[0] ?? $nombreCompleto,
                'apellido_paterno' => $parts[1] ?? '',
                'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : ('Usuario #' . $vehiculoRespId),
            ];
        }

        return $data;
    }

    public function create(array $data, int $userId): int
    {
        $fotos = $this->extractFotos($data);
        $clean = $this->validateForCreate($data, $fotos);
        $db = Database::getInstance();
        $db->beginTransaction();
        try {
            $clean['created_by'] = $userId;
            $clean['foto_principal'] = null;
            $id = $this->repo->create($clean);

            foreach ($fotos as $index => $foto) {
                $ruta = FileUploader::uploadImage($foto, 'vehiculos/' . $id);
                if ($ruta === null) {
                    throw new RuntimeException('No se pudo guardar la imagen ' . ($index + 1) . '.');
                }
                $this->repo->addFoto($id, $ruta, null, $index === 0);
            }

            $this->herramientas->ensureDefaultsForVehiculo($id);
            AuditService::log('CREATE', 'vehiculos', $id, null, $clean);
            $db->commit();
            return $id;
        } catch (ValidationException $e) {
            $db->rollBack();
            throw $e;
        } catch (RuntimeException $e) {
            $db->rollBack();
            throw $e;
        } catch (PDOException $e) {
            $db->rollBack();
            throw new ValidationException([$this->parseDuplicateKeyError($e)]);
        } catch (\Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function update(int $id, array $data, int $userId): bool
    {
        $before = $this->repo->findById($id);
        if ($before === null) {
            return false;
        }
        $clean = $this->validateForCreate($data, [], $id, $before);
        $clean['updated_by'] = $userId;
        $clean['foto_principal'] = $before['foto_principal'] ?? null;
        $result = $this->repo->update($id, $clean);
        if ($result) {
            AuditService::log('UPDATE', 'vehiculos', $id, $before, $clean);
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
        $this->validateFotos($files);
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
        $this->alertas->sincronizar();

        return $this->repo->getExpedienteData($id);
    }

    /** @return list<string> */
    public function getLucesTablero(int $id): array
    {
        return $this->repo->getLucesTablero($id);
    }

    /** @param list<array<string, mixed>> $fotos
     *  @param array<string, mixed>|null $before
     *  @return array<string, mixed>
     */
    private function validateForCreate(array $data, array $fotos = [], ?int $excludeId = null, ?array $before = null): array
    {
        $errors = [];
        $identificadorLabel = vehiculo_identificador_label();

        $numeroEconomico = trim((string) ($data['numero_economico'] ?? ''));
        if ($numeroEconomico === '') {
            $errors['numero_economico'] = 'El ' . strtolower($identificadorLabel) . ' es obligatorio.';
        } elseif (mb_strlen($numeroEconomico) > 30) {
            $errors['numero_economico'] = 'El ' . strtolower($identificadorLabel) . ' no puede exceder 30 caracteres.';
        } elseif ($this->repo->existsNumeroEconomico($numeroEconomico, $excludeId)) {
            $errors['numero_economico'] = 'Ese ' . strtolower($identificadorLabel) . ' ya está registrado en otro vehículo.';
        }

        $placas = strtoupper(trim((string) ($data['placas'] ?? '')));
        if ($placas === '') {
            $errors['placas'] = 'Las placas son obligatorias.';
        } elseif (mb_strlen($placas) > 20) {
            $errors['placas'] = 'Las placas no pueden exceder 20 caracteres.';
        } elseif ($this->repo->existsPlacas($placas, $excludeId)) {
            $errors['placas'] = 'Esas placas ya están registradas en otro vehículo.';
        }

        $serieVin = strtoupper(trim((string) ($data['serie_vin'] ?? '')));
        if ($serieVin === '') {
            $errors['serie_vin'] = 'La serie VIN es obligatoria.';
        } elseif (strlen($serieVin) !== 17) {
            $errors['serie_vin'] = 'La serie VIN debe tener exactamente 17 caracteres.';
        } elseif (!preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $serieVin)) {
            $errors['serie_vin'] = 'La serie VIN solo puede contener letras (sin I, O, Q) y números.';
        } elseif ($this->repo->existsSerieVin($serieVin, $excludeId)) {
            $errors['serie_vin'] = 'Esa serie VIN ya está registrada en otro vehículo.';
        }

        $marca = trim((string) ($data['marca'] ?? ''));
        if ($marca === '') {
            $errors['marca'] = 'La marca es obligatoria.';
        } elseif (mb_strlen($marca) > 80) {
            $errors['marca'] = 'La marca no puede exceder 80 caracteres.';
        }

        $modelo = trim((string) ($data['modelo'] ?? ''));
        if ($modelo === '') {
            $errors['modelo'] = 'El modelo es obligatorio.';
        } elseif (mb_strlen($modelo) > 80) {
            $errors['modelo'] = 'El modelo no puede exceder 80 caracteres.';
        }

        $version = trim((string) ($data['version'] ?? ''));
        if (mb_strlen($version) > 80) {
            $errors['version'] = 'La versión no puede exceder 80 caracteres.';
        }

        $anioRaw = trim((string) ($data['anio'] ?? ''));
        if ($anioRaw === '') {
            $errors['anio'] = 'El año es obligatorio.';
        } elseif (!ctype_digit($anioRaw)) {
            $errors['anio'] = 'El año debe ser un número entero.';
        } else {
            $anio = (int) $anioRaw;
            $maxAnio = (int) date('Y') + 1;
            if ($anio < 1990 || $anio > $maxAnio) {
                $errors['anio'] = 'El año debe estar entre 1990 y ' . $maxAnio . '.';
            }
        }

        $color = trim((string) ($data['color'] ?? ''));
        if ($color === '') {
            $errors['color'] = 'El color es obligatorio.';
        } elseif (mb_strlen($color) > 50) {
            $errors['color'] = 'El color no puede exceder 50 caracteres.';
        }

        $motor = trim((string) ($data['motor'] ?? ''));
        if (mb_strlen($motor) > 80) {
            $errors['motor'] = 'El motor no puede exceder 80 caracteres.';
        }

        $tipoCombustible = (string) ($data['tipo_combustible'] ?? '');
        if ($tipoCombustible === '') {
            $errors['tipo_combustible'] = 'Debe seleccionar el tipo de combustible.';
        } elseif (!in_array($tipoCombustible, self::TIPOS_COMBUSTIBLE, true)) {
            $errors['tipo_combustible'] = 'El tipo de combustible seleccionado no es válido.';
        }

        $capacidadRaw = trim((string) ($data['capacidad_tanque'] ?? ''));
        if ($capacidadRaw === '') {
            $errors['capacidad_tanque'] = 'La capacidad del tanque es obligatoria.';
        } elseif (!is_numeric($capacidadRaw)) {
            $errors['capacidad_tanque'] = 'La capacidad del tanque debe ser un número.';
        } else {
            $capacidad = (float) $capacidadRaw;
            if ($capacidad <= 0) {
                $errors['capacidad_tanque'] = 'La capacidad del tanque debe ser mayor a 0 litros.';
            } elseif ($capacidad > 9999.99) {
                $errors['capacidad_tanque'] = 'La capacidad del tanque es demasiado alta.';
            }
        }

        $kmRaw = trim((string) ($data['kilometraje_actual'] ?? '0'));
        if ($kmRaw === '') {
            $kmRaw = '0';
        }
        if (!ctype_digit($kmRaw)) {
            $errors['kilometraje_actual'] = 'El kilometraje debe ser un número entero sin decimales.';
        } elseif ((int) $kmRaw > 9999999) {
            $errors['kilometraje_actual'] = 'El kilometraje ingresado es demasiado alto.';
        }

        $fechaAdquisicion = trim((string) ($data['fecha_adquisicion'] ?? ''));
        if ($fechaAdquisicion === '') {
            $errors['fecha_adquisicion'] = 'La fecha de adquisición es obligatoria.';
        } else {
            $fecha = \DateTimeImmutable::createFromFormat('Y-m-d', $fechaAdquisicion);
            $fechaErrors = \DateTimeImmutable::getLastErrors();
            if ($fecha === false || ($fechaErrors['warning_count'] ?? 0) > 0 || ($fechaErrors['error_count'] ?? 0) > 0) {
                $errors['fecha_adquisicion'] = 'La fecha de adquisición no es válida.';
            } elseif ($fecha > new \DateTimeImmutable('today')) {
                $errors['fecha_adquisicion'] = 'La fecha de adquisición no puede ser futura.';
            }
        }

        $areaId = (int) ($data['area_id'] ?? 0);
        if ($areaId <= 0) {
            $errors['area_id'] = 'Debe seleccionar un área.';
        } else {
            $area = $this->areas->findById($areaId);
            if ($area === null) {
                $errors['area_id'] = 'El área seleccionada no existe.';
            } elseif (empty($area['activo']) && !($before !== null && (int) ($before['area_id'] ?? 0) === $areaId)) {
                $errors['area_id'] = 'El área seleccionada está inactiva.';
            }
        }

        $responsableId = (int) ($data['responsable_id'] ?? 0);
        if ($responsableId <= 0) {
            $errors['responsable_id'] = 'Debe seleccionar un responsable.';
        } else {
            $responsable = $this->users->findById($responsableId);
            $keepingCurrentResponsable = $before !== null && (int) ($before['responsable_id'] ?? 0) === $responsableId;
            if ($responsable === null && !$keepingCurrentResponsable) {
                $errors['responsable_id'] = 'El responsable seleccionado no existe o fue dado de baja.';
            } elseif ($responsable !== null && empty($responsable['activo']) && !$keepingCurrentResponsable) {
                $errors['responsable_id'] = 'El responsable seleccionado está inactivo.';
            }
        }

        $estado = (string) ($data['estado'] ?? ($before['estado'] ?? 'disponible'));
        $estadosPermitidos = self::ESTADOS_INICIALES;
        if ($before !== null && !empty($before['estado']) && !in_array($before['estado'], $estadosPermitidos, true)) {
            $estadosPermitidos[] = $before['estado'];
        }
        if (!in_array($estado, $estadosPermitidos, true)) {
            $errors['estado'] = $before === null
                ? 'El estado inicial seleccionado no es válido.'
                : 'El estado seleccionado no es válido.';
        }

        $fotoErrors = $this->collectFotoErrors($fotos);
        if ($fotoErrors !== []) {
            $errors['fotos'] = implode(' ', $fotoErrors);
        }

        if ($errors !== []) {
            $allErrors = [];
            foreach ($errors as $key => $message) {
                if ($key !== 'fotos') {
                    $allErrors[] = $message;
                }
            }
            foreach ($fotoErrors as $fotoError) {
                $allErrors[] = $fotoError;
            }
            throw new ValidationException($allErrors, $errors);
        }

        return [
            'numero_economico' => $numeroEconomico,
            'marca' => $marca,
            'modelo' => $modelo,
            'version' => $version !== '' ? $version : null,
            'anio' => (int) $anioRaw,
            'color' => $color,
            'placas' => $placas,
            'serie_vin' => $serieVin,
            'motor' => $motor !== '' ? $motor : null,
            'tipo_combustible' => $tipoCombustible,
            'capacidad_tanque' => (float) $capacidadRaw,
            'kilometraje_actual' => (int) $kmRaw,
            'area_id' => $areaId,
            'responsable_id' => $responsableId,
            'fecha_adquisicion' => $fechaAdquisicion,
            'estado' => $estado,
            'observaciones' => trim((string) ($data['observaciones'] ?? '')) ?: null,
        ];
    }

    /** @param list<array<string, mixed>> $fotos
     *  @return list<string>
     */
    private function collectFotoErrors(array $fotos): array
    {
        if ($fotos === []) {
            return [];
        }

        $errors = [];
        foreach ($fotos as $index => $foto) {
            if (!is_array($foto)) {
                continue;
            }
            $message = FileUploader::validateImageUpload($foto, $index);
            if ($message !== null) {
                $errors[] = $message;
            }
        }

        return $errors;
    }

    /** @param list<array<string, mixed>> $fotos */
    private function validateFotos(array $fotos): void
    {
        $errors = $this->collectFotoErrors($fotos);
        if ($errors !== []) {
            throw new ValidationException($errors, ['fotos' => implode(' ', $errors)]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function extractFotos(array &$data): array
    {
        $fotos = $data['fotos'] ?? [];
        if (!is_array($fotos)) {
            $fotos = [];
        }
        if (!empty($data['foto']) && is_array($data['foto'])) {
            array_unshift($fotos, $data['foto']);
        }
        unset($data['foto'], $data['fotos']);

        return array_values(array_filter($fotos, static fn ($foto) => is_array($foto) && (($foto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)));
    }

    private function parseDuplicateKeyError(PDOException $e): string
    {
        $message = $e->getMessage();
        if (str_contains($message, 'numero_economico')) {
            return 'Ese ' . strtolower(vehiculo_identificador_label()) . ' ya está registrado en otro vehículo.';
        }
        if (str_contains($message, 'placas')) {
            return 'Esas placas ya están registradas en otro vehículo.';
        }
        if (str_contains($message, 'serie_vin')) {
            return 'Esa serie VIN ya está registrada en otro vehículo.';
        }
        if (str_contains($message, 'area_id')) {
            return 'El área seleccionada no es válida.';
        }
        if (str_contains($message, 'responsable_id')) {
            return 'El responsable seleccionado no es válido.';
        }
        return 'No se pudo registrar el vehículo por un conflicto de datos duplicados.';
    }
}
