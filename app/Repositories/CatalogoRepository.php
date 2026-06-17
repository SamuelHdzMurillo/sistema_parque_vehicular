<?php

declare(strict_types=1);

namespace App\Repositories;

final class CatalogoRepository extends BaseRepository
{
    public function getAreas(bool $soloActivos = true): array
    {
        $sql = 'SELECT id, clave, nombre FROM areas';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY nombre ASC';
        return $this->fetchAll($sql);
    }

    public function getProveedores(?string $tipo = null, bool $soloActivos = true): array
    {
        $params = [];
        $where = [];
        if ($soloActivos) {
            $where[] = 'activo = 1';
        }
        if ($tipo !== null) {
            $where[] = '(tipo = ? OR tipo = "ambos")';
            $params[] = $tipo;
        }
        $sql = 'SELECT id, razon_social, rfc, telefono, email, direccion, tipo FROM proveedores';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY razon_social ASC';
        return $this->fetchAll($sql, $params);
    }

    public function getRoles(bool $soloActivos = true): array
    {
        $sql = 'SELECT id, slug, nombre FROM roles';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY nombre ASC';
        return $this->fetchAll($sql);
    }

    public function getUsersForSelect(?int $areaId = null, ?string $roleSlug = null): array
    {
        $params = [];
        $where = ['u.deleted_at IS NULL', 'u.activo = 1'];

        if ($areaId !== null) {
            $where[] = 'u.area_id = ?';
            $params[] = $areaId;
        }
        if ($roleSlug !== null) {
            $where[] = 'r.slug = ?';
            $params[] = $roleSlug;
        }

        return $this->fetchAll(
            'SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.email,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS nombre_completo,
                    r.slug AS role_slug, a.nombre AS area_nombre
             FROM users u
             JOIN roles r ON r.id = u.role_id
             LEFT JOIN areas a ON a.id = u.area_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY u.nombre ASC',
            $params
        );
    }

    public function getResponsablesVehiculo(): array
    {
        return $this->getUsersForSelect(null, 'responsable_vehiculo');
    }

    public function getAllForSelects(): array
    {
        return [
            'areas' => $this->getAreas(),
            'proveedores' => $this->getProveedores(),
            'roles' => $this->getRoles(),
            'users' => $this->getUsersForSelect(),
        ];
    }

    public function getVehiculosDisponibles(): array
    {
        return $this->fetchAll(
            "SELECT v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.kilometraje_actual, v.estado
             FROM vehiculos v
             WHERE v.deleted_at IS NULL AND v.estado IN ('activo','disponible')
             ORDER BY v.numero_economico ASC"
        );
    }

    /** Vehículos en operación (excluye baja y fuera de servicio). */
    public function getVehiculosOperativos(): array
    {
        return $this->fetchAll(
            "SELECT v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.kilometraje_actual, v.estado
             FROM vehiculos v
             WHERE v.deleted_at IS NULL AND v.estado NOT IN ('baja', 'fuera_servicio')
             ORDER BY v.numero_economico ASC"
        );
    }
}
