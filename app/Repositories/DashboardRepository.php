<?php

declare(strict_types=1);

namespace App\Repositories;

final class DashboardRepository extends BaseRepository
{
    public function getResumenVehiculos(): array
    {
        return $this->fetchOne(
            "SELECT
                COUNT(*) AS total,
                SUM(estado NOT IN ('baja','fuera_servicio')) AS operativos,
                SUM(estado = 'en_comision') AS en_comision,
                SUM(estado = 'en_mantenimiento') AS en_mantenimiento,
                SUM(estado = 'en_taller') AS en_taller
             FROM vehiculos WHERE deleted_at IS NULL"
        ) ?: [];
    }

    public function getResumenAlertas(): array
    {
        return $this->fetchOne(
            "SELECT
                SUM(nivel = 'rojo' AND atendida = 0) AS rojas,
                SUM(nivel = 'amarillo' AND atendida = 0) AS amarillas,
                SUM(atendida = 0) AS pendientes
             FROM alertas"
        ) ?: [];
    }

    public function getComisionesActivasCount(): int
    {
        return (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM comisiones WHERE estado = 'en_curso'"
        )['c'] ?? 0);
    }

    public function getVehiculosActivos(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM vehiculos
             WHERE deleted_at IS NULL AND estado IN ("activo", "disponible")'
        )['c'] ?? 0);
    }

    public function getVehiculosEnTaller(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM vehiculos WHERE deleted_at IS NULL AND estado = "en_taller"'
        )['c'] ?? 0);
    }

    public function getVehiculosEnMantenimiento(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM vehiculos WHERE deleted_at IS NULL AND estado = "en_mantenimiento"'
        )['c'] ?? 0);
    }

    public function getGastosPeriodo(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? (int) date('Y');
        $params = [$year];
        $mantWhere = 'WHERE YEAR(fecha) = ? AND estado = "finalizado"';
        $combWhere = 'WHERE YEAR(fecha) = ?';

        if ($month !== null) {
            $mantWhere .= ' AND MONTH(fecha) = ?';
            $combWhere .= ' AND MONTH(fecha) = ?';
            $params[] = $month;
        }

        $mant = (float) ($this->fetchOne(
            "SELECT COALESCE(SUM(costo), 0) AS total FROM mantenimientos {$mantWhere}",
            $params
        )['total'] ?? 0);

        $comb = (float) ($this->fetchOne(
            "SELECT COALESCE(SUM(importe), 0) AS total FROM combustible_cargas {$combWhere}",
            $params
        )['total'] ?? 0);

        return [
            'mantenimiento' => $mant,
            'combustible' => $comb,
            'total' => $mant + $comb,
            'year' => $year,
            'month' => $month,
        ];
    }

    public function getCombustiblePeriodo(?int $year = null, ?int $month = null): array
    {
        $year = $year ?? (int) date('Y');
        $params = [$year];
        $where = 'WHERE YEAR(fecha) = ?';

        if ($month !== null) {
            $where .= ' AND MONTH(fecha) = ?';
            $params[] = $month;
        }

        return $this->fetchOne(
            "SELECT COALESCE(SUM(litros), 0) AS litros, COALESCE(SUM(importe), 0) AS importe
             FROM combustible_cargas {$where}",
            $params
        ) ?? ['litros' => 0, 'importe' => 0];
    }

    public function getServiciosPendientes(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM mantenimientos WHERE estado IN ("pendiente", "programado")'
        )['c'] ?? 0);
    }

    public function getDocsPorVencer(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM documentos
             WHERE activo = 1 AND fecha_vencimiento IS NOT NULL
               AND fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)'
        )['c'] ?? 0);
    }

    public function getAlertasPendientes(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT a.id, a.tipo, a.titulo, a.mensaje, a.nivel, a.created_at,
                    v.id AS vehiculo_id, v.numero_economico
             FROM alertas a
             LEFT JOIN vehiculos v ON v.id = a.vehiculo_id
             WHERE a.atendida = 0
             ORDER BY FIELD(a.nivel, "rojo", "amarillo", "verde"), a.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getDocumentosPorVencer(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT d.id, d.titulo, d.tipo, d.fecha_vencimiento,
                    v.id AS vehiculo_id, v.numero_economico,
                    DATEDIFF(d.fecha_vencimiento, CURDATE()) AS dias_restantes
             FROM documentos d
             JOIN vehiculos v ON v.id = d.vehiculo_id
             WHERE d.activo = 1 AND d.fecha_vencimiento IS NOT NULL
               AND d.fecha_vencimiento <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
               AND v.deleted_at IS NULL
             ORDER BY d.fecha_vencimiento ASC
             LIMIT ?',
            [$limit]
        );
    }

    public function getMantenimientosActivos(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT m.id, m.folio, m.tipo, m.fecha, m.estado, m.descripcion,
                    v.id AS vehiculo_id, v.numero_economico,
                    p.razon_social AS proveedor
             FROM mantenimientos m
             JOIN vehiculos v ON v.id = m.vehiculo_id
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             WHERE m.estado IN ("pendiente", "programado", "autorizado", "en_proceso")
             ORDER BY FIELD(m.estado, "en_proceso", "autorizado", "programado", "pendiente"), m.fecha ASC
             LIMIT ?',
            [$limit]
        );
    }

    public function getComisionesEnCurso(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT c.id, c.folio, c.destino, c.conductor_nombre, c.fecha, c.hora_salida,
                    v.id AS vehiculo_id, v.numero_economico,
                    a.nombre AS area_nombre
             FROM comisiones c
             JOIN vehiculos v ON v.id = c.vehiculo_id
             JOIN areas a ON a.id = c.area_solicitante_id
             WHERE c.estado = "en_curso"
             ORDER BY c.fecha DESC, c.hora_salida DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getDaniosAbiertos(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT d.id, d.tipo_dano, d.ubicacion, d.descripcion, d.estado, d.created_at,
                    v.id AS vehiculo_id, v.numero_economico
             FROM danios d
             JOIN vehiculos v ON v.id = d.vehiculo_id
             WHERE d.estado NOT IN ("reparado", "cerrado_sin_accion")
             ORDER BY FIELD(d.estado, "reportado", "en_evaluacion", "en_reparacion"), d.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function countDaniosAbiertos(): int
    {
        return (int) ($this->fetchOne(
            'SELECT COUNT(*) AS c FROM danios WHERE estado NOT IN ("reparado", "cerrado_sin_accion")'
        )['c'] ?? 0);
    }

    /** @return array<int, array<string, mixed>> */
    public function getAlertaConfigsKm(): array
    {
        return $this->fetchAll(
            'SELECT * FROM alerta_config WHERE unidad = "km" AND activo = 1'
        );
    }

    /** @return array<int, array<string, mixed>> */
    public function getVehiculosOperativos(): array
    {
        return $this->fetchAll(
            'SELECT id, numero_economico, kilometraje_actual, estado
             FROM vehiculos
             WHERE deleted_at IS NULL AND estado NOT IN ("baja", "fuera_servicio")'
        );
    }

    public function getUltimoServicioPreventivo(int $vehiculoId, string $servicio): ?array
    {
        $row = $this->fetchOne(
            'SELECT m.fecha, m.kilometraje FROM mantenimientos m
             LEFT JOIN mantenimiento_servicios ms ON ms.mantenimiento_id = m.id
             WHERE m.vehiculo_id = ? AND m.estado = "finalizado"
               AND (ms.servicio = ? OR m.servicio = ?)
             ORDER BY m.fecha DESC, m.id DESC LIMIT 1',
            [$vehiculoId, $servicio, $servicio]
        );

        if ($row !== null) {
            return $row;
        }

        return $this->fetchOne(
            'SELECT fecha, kilometraje FROM mantenimientos
             WHERE vehiculo_id = ? AND tipo = "preventivo" AND estado = "finalizado"
               AND servicio IS NULL AND descripcion LIKE ?
             ORDER BY fecha DESC, id DESC LIMIT 1',
            [$vehiculoId, '%' . $this->legacyBusquedaDashboard($servicio) . '%']
        );
    }

    private function legacyBusquedaDashboard(string $servicio): string
    {
        return match ($servicio) {
            'cambio_aceite' => 'aceite',
            'afinacion' => 'afinaci',
            'llantas' => 'llanta',
            default => $servicio,
        };
    }

    public function getTopVehiculosCostosos(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT vehiculo_id, numero_economico, costo_total
             FROM v_costos_vehiculo
             ORDER BY costo_total DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getTopProveedores(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT p.id, p.razon_social,
                    COALESCE(SUM(m.costo), 0) + COALESCE(
                        (SELECT SUM(c.importe) FROM combustible_cargas c WHERE c.proveedor_id = p.id), 0
                    ) AS total_gastado
             FROM proveedores p
             LEFT JOIN mantenimientos m ON m.proveedor_id = p.id AND m.estado = "finalizado"
             WHERE p.activo = 1
             GROUP BY p.id, p.razon_social
             ORDER BY total_gastado DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getTopIncidencias(int $limit = 5): array
    {
        return $this->fetchAll(
            'SELECT v.id, v.numero_economico,
                    (SELECT COUNT(*) FROM danios d WHERE d.vehiculo_id = v.id) AS total_danios,
                    (SELECT COUNT(*) FROM inspeccion_items ii
                     JOIN inspecciones i ON i.id = ii.inspeccion_id
                     WHERE i.vehiculo_id = v.id AND ii.calificacion = "malo") AS items_malo
             FROM vehiculos v
             WHERE v.deleted_at IS NULL
             HAVING (total_danios + items_malo) > 0
             ORDER BY (total_danios + items_malo) DESC
             LIMIT ?',
            [$limit]
        );
    }

    public function getEstadosFlota(): array
    {
        return $this->fetchAll(
            'SELECT estado, COUNT(*) AS total FROM vehiculos WHERE deleted_at IS NULL GROUP BY estado'
        );
    }

    public function getGastosMensuales(int $months = 12): array
    {
        return $this->fetchAll(
            'SELECT periodo, SUM(mantenimiento) AS mantenimiento, SUM(combustible) AS combustible
             FROM (
                SELECT DATE_FORMAT(fecha, "%Y-%m") AS periodo, SUM(costo) AS mantenimiento, 0 AS combustible
                FROM mantenimientos WHERE estado = "finalizado"
                  AND fecha >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(fecha, "%Y-%m")
                UNION ALL
                SELECT DATE_FORMAT(fecha, "%Y-%m") AS periodo, 0 AS mantenimiento, SUM(importe) AS combustible
                FROM combustible_cargas
                WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(fecha, "%Y-%m")
             ) combined
             GROUP BY periodo
             ORDER BY periodo ASC',
            [$months, $months]
        );
    }

    public function getAllKpis(?int $year = null, ?int $month = null): array
    {
        return [
            'vehiculos_activos' => $this->getVehiculosActivos(),
            'en_taller' => $this->getVehiculosEnTaller(),
            'en_mantenimiento' => $this->getVehiculosEnMantenimiento(),
            'gastos' => $this->getGastosPeriodo($year, $month),
            'combustible' => $this->getCombustiblePeriodo($year, $month),
            'servicios_pendientes' => $this->getServiciosPendientes(),
            'docs_por_vencer' => $this->getDocsPorVencer(),
            'top_vehiculos_costosos' => $this->getTopVehiculosCostosos(),
            'top_proveedores' => $this->getTopProveedores(),
            'top_incidencias' => $this->getTopIncidencias(),
            'estados_flota' => $this->getEstadosFlota(),
            'gastos_mensuales' => $this->getGastosMensuales(),
            'alertas' => (new AlertaRepository())->getDashboardCounts(),
        ];
    }
}
