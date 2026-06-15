<?php

declare(strict_types=1);

namespace App\Repositories;

final class DashboardRepository extends BaseRepository
{
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
