<?php

declare(strict_types=1);

namespace App\Repositories;

final class ComisionRepository extends BaseRepository
{
    /** @var list<array{codigo: string, nombre: string}> */
    public const LIQUIDOS = [
        ['codigo' => 'aceite_motor', 'nombre' => 'Aceite de motor'],
        ['codigo' => 'liquido_frenos', 'nombre' => 'Líquido de frenos'],
        ['codigo' => 'refrigerante', 'nombre' => 'Refrigerante / anticongelante'],
        ['codigo' => 'direccion_hidraulica', 'nombre' => 'Dirección hidráulica'],
        ['codigo' => 'limpiaparabrisas', 'nombre' => 'Líquido limpiaparabrisas'],
        ['codigo' => 'transmision', 'nombre' => 'Líquido de transmisión'],
    ];

    /** @var array<string, string> */
    public const NIVEL_OPCIONES = [
        'lleno' => 'Lleno',
        'medio' => 'Medio',
        'bajo' => 'Bajo',
    ];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT c.*, v.numero_economico, v.placas, v.capacidad_tanque, v.marca, v.modelo,
                    a.nombre AS area_solicitante_nombre,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS responsable_nombre
             FROM comisiones c
             JOIN vehiculos v ON v.id = c.vehiculo_id
             JOIN areas a ON a.id = c.area_solicitante_id
             JOIN users u ON u.id = c.responsable_id
             WHERE c.id = ?',
            [$id]
        );
    }

    public function getUserFullName(int $userId): ?string
    {
        $row = $this->fetchOne(
            'SELECT CONCAT(nombre, " ", apellido_paterno) AS nombre_completo FROM users WHERE id = ?',
            [$userId]
        );
        return $row['nombre_completo'] ?? null;
    }

    public function updateDocumento(int $id, string $tipo, string $ruta): bool
    {
        $columna = $tipo === 'regreso' ? 'doc_regreso_ruta' : 'doc_salida_ruta';
        return $this->execute(
            "UPDATE comisiones SET {$columna} = ?, updated_at = NOW() WHERE id = ?",
            [$ruta, $id]
        );
    }

    /** @return array{salida: list<string>, regreso: list<string>} */
    public function getLuces(int $comisionId): array
    {
        $rows = $this->fetchAll(
            'SELECT momento, luz_codigo FROM comision_luces_tablero WHERE comision_id = ? ORDER BY luz_codigo ASC',
            [$comisionId]
        );
        $luces = ['salida' => [], 'regreso' => []];
        foreach ($rows as $row) {
            $momento = $row['momento'] === 'regreso' ? 'regreso' : 'salida';
            $luces[$momento][] = $row['luz_codigo'];
        }
        return $luces;
    }

    public function saveLuces(int $comisionId, string $momento, array $codigos): void
    {
        $momento = $momento === 'regreso' ? 'regreso' : 'salida';
        $this->execute(
            'DELETE FROM comision_luces_tablero WHERE comision_id = ? AND momento = ?',
            [$comisionId, $momento]
        );
        foreach ($codigos as $codigo) {
            $this->execute(
                'INSERT INTO comision_luces_tablero (comision_id, momento, luz_codigo) VALUES (?, ?, ?)',
                [$comisionId, $momento, (string) $codigo]
            );
        }
    }

    /** @return array{salida: array<string, string>, regreso: array<string, string>} */
    public function getNiveles(int $comisionId): array
    {
        $rows = $this->fetchAll(
            'SELECT momento, liquido_codigo, nivel FROM comision_niveles_liquidos WHERE comision_id = ?',
            [$comisionId]
        );
        $niveles = ['salida' => [], 'regreso' => []];
        foreach ($rows as $row) {
            $momento = $row['momento'] === 'regreso' ? 'regreso' : 'salida';
            $niveles[$momento][$row['liquido_codigo']] = $row['nivel'];
        }
        return $niveles;
    }

    /** @param array<string, string> $niveles */
    public function saveNiveles(int $comisionId, string $momento, array $niveles): void
    {
        $momento = $momento === 'regreso' ? 'regreso' : 'salida';
        $this->execute(
            'DELETE FROM comision_niveles_liquidos WHERE comision_id = ? AND momento = ?',
            [$comisionId, $momento]
        );
        foreach ($niveles as $codigo => $nivel) {
            $this->execute(
                'INSERT INTO comision_niveles_liquidos (comision_id, momento, liquido_codigo, nivel) VALUES (?, ?, ?, ?)',
                [$comisionId, $momento, (string) $codigo, (string) $nivel]
            );
        }
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO comisiones (
                folio, vehiculo_id, area_solicitante_id, responsable_id, conductor_nombre, conductor_id,
                responsable_regreso_nombre, responsable_regreso_id,
                destino, motivo, fecha, hora_salida, km_salida, combustible_salida, observaciones,
                estado, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['folio'],
                (int) $data['vehiculo_id'],
                (int) $data['area_solicitante_id'],
                (int) $data['responsable_id'],
                $data['conductor_nombre'],
                $data['conductor_id'] ?? null,
                $data['responsable_regreso_nombre'] ?? null,
                $data['responsable_regreso_id'] ?? null,
                $data['destino'],
                $data['motivo'],
                $data['fecha'],
                $data['hora_salida'],
                (int) $data['km_salida'],
                (float) $data['combustible_salida'],
                $data['observaciones'] ?? null,
                $data['estado'] ?? 'borrador',
                $data['created_by'] ?? null,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE comisiones SET
                area_solicitante_id = ?, responsable_id = ?, conductor_nombre = ?, conductor_id = ?,
                responsable_regreso_nombre = ?, responsable_regreso_id = ?,
                destino = ?, motivo = ?, fecha = ?, hora_salida = ?, hora_regreso = ?,
                km_salida = ?, km_regreso = ?, combustible_salida = ?, combustible_regreso = ?,
                km_recorridos = ?, litros_consumidos = ?, rendimiento = ?,
                observaciones = ?, firma_digital = ?, estado = ?, updated_at = NOW()
             WHERE id = ?',
            [
                (int) $data['area_solicitante_id'],
                (int) $data['responsable_id'],
                $data['conductor_nombre'],
                $data['conductor_id'] ?? null,
                $data['responsable_regreso_nombre'] ?? null,
                $data['responsable_regreso_id'] ?? null,
                $data['destino'],
                $data['motivo'],
                $data['fecha'],
                $data['hora_salida'],
                $data['hora_regreso'] ?? null,
                (int) $data['km_salida'],
                $data['km_regreso'] ?? null,
                (float) $data['combustible_salida'],
                $data['combustible_regreso'] ?? null,
                $data['km_recorridos'] ?? null,
                $data['litros_consumidos'] ?? null,
                $data['rendimiento'] ?? null,
                $data['observaciones'] ?? null,
                $data['firma_digital'] ?? null,
                $data['estado'],
                $id,
            ]
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM comisiones WHERE id = ? AND estado = "borrador"', [$id]);
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND c.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['estado'])) {
            $where .= ' AND c.estado = ?';
            $params[] = $filters['estado'];
        }
        if (!empty($filters['area_id'])) {
            $where .= ' AND c.area_solicitante_id = ?';
            $params[] = (int) $filters['area_id'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where .= ' AND c.fecha >= ?';
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where .= ' AND c.fecha <= ?';
            $params[] = $filters['fecha_hasta'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (c.folio LIKE ? OR c.destino LIKE ? OR v.numero_economico LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term);
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM comisiones c JOIN vehiculos v ON v.id = c.vehiculo_id {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT c.id, c.folio, c.fecha, c.destino, c.estado, c.km_recorridos, c.rendimiento,
                    v.numero_economico, a.nombre AS area_nombre
             FROM comisiones c
             JOIN vehiculos v ON v.id = c.vehiculo_id
             JOIN areas a ON a.id = c.area_solicitante_id
             {$where}
             ORDER BY c.fecha DESC, c.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function generateFolio(): string
    {
        $year = date('Y');
        $prefix = "COM-{$year}-";
        $last = $this->fetchOne(
            'SELECT folio FROM comisiones WHERE folio LIKE ? ORDER BY id DESC LIMIT 1',
            ["{$prefix}%"]
        );
        $seq = 1;
        if ($last !== null && preg_match('/(\d+)$/', $last['folio'], $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function calcularMetricas(array $comision, float $capacidadTanque): array
    {
        $kmSalida = (int) $comision['km_salida'];
        $kmRegreso = isset($comision['km_regreso']) ? (int) $comision['km_regreso'] : null;
        $combSalida = (float) $comision['combustible_salida'];
        $combRegreso = isset($comision['combustible_regreso']) ? (float) $comision['combustible_regreso'] : null;

        $kmRecorridos = null;
        $litrosConsumidos = null;
        $rendimiento = null;

        if ($kmRegreso !== null && $kmRegreso >= $kmSalida) {
            $kmRecorridos = $kmRegreso - $kmSalida;
        }

        if ($combRegreso !== null && $capacidadTanque > 0) {
            $litrosSalida = ($combSalida / 100) * $capacidadTanque;
            $litrosRegreso = ($combRegreso / 100) * $capacidadTanque;
            $litrosConsumidos = max(0, round($litrosSalida - $litrosRegreso, 2));
            if ($kmRecorridos !== null && $litrosConsumidos > 0) {
                $rendimiento = round($kmRecorridos / $litrosConsumidos, 2);
            }
        }

        return [
            'km_recorridos' => $kmRecorridos,
            'litros_consumidos' => $litrosConsumidos,
            'rendimiento' => $rendimiento,
        ];
    }

    public function findByVehiculo(int $vehiculoId, int $limit = 20): array
    {
        return $this->fetchAll(
            'SELECT id, folio, fecha, destino, estado, km_recorridos, rendimiento
             FROM comisiones WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT ?',
            [$vehiculoId, $limit]
        );
    }

    public function hasActiveComision(int $vehiculoId, ?int $excludeId = null): bool
    {
        $sql = 'SELECT id FROM comisiones WHERE vehiculo_id = ? AND estado = "en_curso"';
        $params = [$vehiculoId];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return $this->fetchOne($sql, $params) !== null;
    }

    public function getPromedioRendimiento(int $vehiculoId): ?float
    {
        $row = $this->fetchOne(
            'SELECT AVG(rendimiento) AS promedio FROM comisiones
             WHERE vehiculo_id = ? AND rendimiento IS NOT NULL AND estado = "finalizada"',
            [$vehiculoId]
        );
        return $row !== null && $row['promedio'] !== null ? (float) $row['promedio'] : null;
    }
}
