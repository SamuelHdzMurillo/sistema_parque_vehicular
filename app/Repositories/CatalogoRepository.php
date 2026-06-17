<?php

declare(strict_types=1);

namespace App\Repositories;

final class CatalogoRepository extends BaseRepository
{
    public function getAreas(bool $soloActivos = true): array
    {
        $sql = 'SELECT a.id, a.clave, a.nombre, a.plantel_id,
                       p.clave AS plantel_clave, p.nombre AS plantel_nombre,
                       CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(" - ", p.clave), "")) AS label
                FROM areas a
                LEFT JOIN planteles p ON p.id = a.plantel_id';
        if ($soloActivos) {
            $sql .= ' WHERE a.activo = 1';
        }
        $sql .= ' ORDER BY p.clave ASC, a.nombre ASC';
        return $this->fetchAll($sql);
    }

    public function getConductores(bool $soloActivos = true): array
    {
        $sql = 'SELECT c.id, c.nombre, c.telefono, c.area_id,
                       a.nombre AS area_nombre, p.clave AS plantel_clave,
                       CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(" - ", p.clave), "")) AS area_label
                FROM conductores c
                JOIN areas a ON a.id = c.area_id
                LEFT JOIN planteles p ON p.id = a.plantel_id';
        if ($soloActivos) {
            $sql .= ' WHERE c.activo = 1';
        }
        $sql .= ' ORDER BY c.nombre ASC';
        return $this->fetchAll($sql);
    }

    public function getConductorById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.id, c.nombre, c.telefono, c.area_id
             FROM conductores c WHERE c.id = ? AND c.activo = 1',
            [$id]
        );
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
        $sql = 'SELECT id, slug, nombre, descripcion FROM roles';
        if ($soloActivos) {
            $sql .= ' WHERE activo = 1';
        }
        $sql .= ' ORDER BY id ASC';
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

    /** Catálogo completo de vehículos (sin filtrar por estado). */
    public function getVehiculosCatalogo(): array
    {
        return $this->fetchAll(
            'SELECT v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.kilometraje_actual, v.estado
             FROM vehiculos v
             WHERE v.deleted_at IS NULL
             ORDER BY v.numero_economico ASC'
        );
    }

    public function getVehiculosDisponibles(): array
    {
        return $this->getVehiculosCatalogo();
    }

    public function getVehiculosOperativos(): array
    {
        return $this->getVehiculosCatalogo();
    }
}
