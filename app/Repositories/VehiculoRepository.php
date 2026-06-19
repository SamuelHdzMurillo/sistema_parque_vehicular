<?php

declare(strict_types=1);

namespace App\Repositories;

final class VehiculoRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT v.*, a.clave AS area_clave,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(" - ", p.clave), "")) AS area_nombre,
                    p.clave AS plantel_clave,
                    CONCAT(u.nombre, " ", u.apellido_paterno) AS responsable_nombre
             FROM vehiculos v
             JOIN areas a ON a.id = v.area_id
             LEFT JOIN planteles p ON p.id = a.plantel_id
             JOIN users u ON u.id = v.responsable_id
             WHERE v.id = ? AND v.deleted_at IS NULL',
            [$id]
        );
    }

    public function findWithRelations(int $id): ?array
    {
        $vehiculo = $this->findById($id);
        if ($vehiculo === null) {
            return null;
        }

        $vehiculo['fotos'] = $this->fetchAll(
            'SELECT * FROM vehiculo_fotos WHERE vehiculo_id = ? ORDER BY es_principal DESC, id ASC',
            [$id]
        );
        $vehiculo['estado_historial'] = $this->fetchAll(
            'SELECT h.*, CONCAT(u.nombre, " ", u.apellido_paterno) AS usuario_nombre
             FROM vehiculo_estado_historial h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.vehiculo_id = ? ORDER BY h.created_at DESC LIMIT 20',
            [$id]
        );

        return $vehiculo;
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO vehiculos (
                numero_economico, marca, modelo, version, anio, color, placas, serie_vin, motor,
                tipo_combustible, capacidad_tanque, kilometraje_actual, area_id, responsable_id,
                fecha_adquisicion, estado, foto_principal, observaciones, created_by
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['numero_economico'],
                $data['marca'],
                $data['modelo'],
                $data['version'] ?? null,
                (int) $data['anio'],
                $data['color'],
                $data['placas'],
                $data['serie_vin'],
                $data['motor'] ?? null,
                $data['tipo_combustible'],
                (float) $data['capacidad_tanque'],
                (int) ($data['kilometraje_actual'] ?? 0),
                (int) $data['area_id'],
                (int) $data['responsable_id'],
                $data['fecha_adquisicion'],
                $data['estado'] ?? 'disponible',
                $data['foto_principal'] ?? null,
                $data['observaciones'] ?? null,
                $data['created_by'] ?? null,
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE vehiculos SET
                numero_economico = ?, marca = ?, modelo = ?, version = ?, anio = ?, color = ?,
                placas = ?, serie_vin = ?, motor = ?, tipo_combustible = ?, capacidad_tanque = ?,
                kilometraje_actual = ?, area_id = ?, responsable_id = ?, fecha_adquisicion = ?,
                estado = ?, foto_principal = ?, observaciones = ?, updated_by = ?,
                version_lock = version_lock + 1, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL',
            [
                $data['numero_economico'],
                $data['marca'],
                $data['modelo'],
                $data['version'] ?? null,
                (int) $data['anio'],
                $data['color'],
                $data['placas'],
                $data['serie_vin'],
                $data['motor'] ?? null,
                $data['tipo_combustible'],
                (float) $data['capacidad_tanque'],
                (int) ($data['kilometraje_actual'] ?? 0),
                (int) $data['area_id'],
                (int) $data['responsable_id'],
                $data['fecha_adquisicion'],
                $data['estado'] ?? 'disponible',
                $data['foto_principal'] ?? null,
                $data['observaciones'] ?? null,
                $data['updated_by'] ?? null,
                $id,
            ]
        );
    }

    public function softDelete(int $id, ?int $userId = null): bool
    {
        return $this->updateEstado($id, 'baja', 'Baja definitiva del vehículo', $userId);
    }

    public function updateEstado(int $id, string $estado, ?string $motivo, ?int $userId = null): bool
    {
        $current = $this->fetchOne('SELECT estado FROM vehiculos WHERE id = ? AND deleted_at IS NULL', [$id]);
        if ($current === null) {
            return false;
        }

        $ownsTransaction = !$this->db->inTransaction();
        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $this->execute(
                'UPDATE vehiculos SET estado = ?, updated_by = ?, updated_at = NOW() WHERE id = ?',
                [$estado, $userId, $id]
            );
            $this->execute(
                'INSERT INTO vehiculo_estado_historial (vehiculo_id, estado_anterior, estado_nuevo, motivo, user_id)
                 VALUES (?, ?, ?, ?, ?)',
                [$id, $current['estado'], $estado, $motivo, $userId]
            );
            if ($estado === 'baja') {
                $this->execute('UPDATE vehiculos SET deleted_at = NOW() WHERE id = ?', [$id]);
            }
            if ($ownsTransaction) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function updateKilometraje(int $id, int $kilometraje, ?int $userId = null): bool
    {
        $this->execute(
            'UPDATE vehiculos SET kilometraje_actual = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL AND kilometraje_actual <= ?',
            [$kilometraje, $userId, $id, $kilometraje]
        );

        return $this->fetchOne(
            'SELECT id FROM vehiculos WHERE id = ? AND deleted_at IS NULL AND kilometraje_actual = ?',
            [$id, $kilometraje]
        ) !== null;
    }

    /** Ajuste administrativo del odómetro (permite aumentar o disminuir). */
    public function setKilometraje(int $id, int $kilometraje, ?int $userId = null): bool
    {
        $this->execute(
            'UPDATE vehiculos SET kilometraje_actual = ?, updated_by = ?, updated_at = NOW()
             WHERE id = ? AND deleted_at IS NULL',
            [$kilometraje, $userId, $id]
        );

        return $this->fetchOne(
            'SELECT id FROM vehiculos WHERE id = ? AND deleted_at IS NULL AND kilometraje_actual = ?',
            [$id, $kilometraje]
        ) !== null;
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE v.deleted_at IS NULL';

        if (!empty($filters['estado'])) {
            $where .= ' AND v.estado = ?';
            $params[] = $filters['estado'];
        }
        if (!empty($filters['area_id'])) {
            $where .= ' AND v.area_id = ?';
            $params[] = (int) $filters['area_id'];
        }
        if (!empty($filters['responsable_id'])) {
            $where .= ' AND v.responsable_id = ?';
            $params[] = (int) $filters['responsable_id'];
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (v.numero_economico LIKE ? OR v.placas LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ? OR v.serie_vin LIKE ?)';
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term, $term, $term);
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM vehiculos v {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.anio, v.estado,
                    v.kilometraje_actual,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(' - ', p.clave), '')) AS area_nombre,
                    CONCAT(u.nombre, ' ', u.apellido_paterno) AS responsable_nombre
             FROM vehiculos v
             JOIN areas a ON a.id = v.area_id
             LEFT JOIN planteles p ON p.id = a.plantel_id
             JOIN users u ON u.id = v.responsable_id
             {$where}
             ORDER BY v.numero_economico ASC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function searchGlobal(string $term, int $limit = 10): array
    {
        $like = '%' . $term . '%';
        return $this->fetchAll(
            'SELECT v.id, v.numero_economico, v.marca, v.modelo, v.placas, v.estado, v.serie_vin,
                    CONCAT(a.nombre, IF(p.clave IS NOT NULL, CONCAT(" - ", p.clave), "")) AS area_nombre
             FROM vehiculos v
             JOIN areas a ON a.id = v.area_id
             LEFT JOIN planteles p ON p.id = a.plantel_id
             WHERE v.deleted_at IS NULL
               AND (v.numero_economico LIKE ? OR v.placas LIKE ? OR v.serie_vin LIKE ?
                    OR v.marca LIKE ? OR v.modelo LIKE ?)
             ORDER BY v.numero_economico ASC
             LIMIT ?',
            [$like, $like, $like, $like, $like, $limit]
        );
    }

    public function getExpedienteData(int $id): ?array
    {
        $vehiculo = $this->findWithRelations($id);
        if ($vehiculo === null) {
            return null;
        }

        $vehiculo['comisiones'] = $this->fetchAll(
            'SELECT id, folio, fecha, destino, estado, km_recorridos, rendimiento, km_salida, km_regreso
             FROM comisiones WHERE vehiculo_id = ? ORDER BY fecha DESC, id DESC LIMIT 50',
            [$id]
        );
        $vehiculo['mantenimientos'] = $this->fetchAll(
            'SELECT m.id, m.folio, m.tipo, m.fecha, m.kilometraje, m.costo, m.estado, p.razon_social AS proveedor
             FROM mantenimientos m
             LEFT JOIN proveedores p ON p.id = m.proveedor_id
             WHERE m.vehiculo_id = ? ORDER BY m.fecha DESC LIMIT 50',
            [$id]
        );
        $vehiculo['combustible'] = $this->fetchAll(
            'SELECT id, fecha, litros, importe, kilometraje, rendimiento, costo_por_km
             FROM combustible_cargas WHERE vehiculo_id = ? ORDER BY fecha DESC LIMIT 50',
            [$id]
        );
        $vehiculo['danios'] = $this->fetchAll(
            'SELECT id, tipo_dano, ubicacion, descripcion, estado, created_at
             FROM danios WHERE vehiculo_id = ? ORDER BY created_at DESC LIMIT 50',
            [$id]
        );
        $vehiculo['inspecciones'] = $this->fetchAll(
            'SELECT i.id, i.fecha, i.kilometraje, i.resultado_general,
                    (SELECT COUNT(*) FROM inspeccion_items ii WHERE ii.inspeccion_id = i.id AND ii.calificacion = "malo") AS items_malo
             FROM inspecciones i WHERE i.vehiculo_id = ? ORDER BY i.fecha DESC LIMIT 50',
            [$id]
        );
        $vehiculo['documentos'] = $this->fetchAll(
            'SELECT id, tipo, titulo, numero_documento, fecha_vencimiento, version, activo
             FROM documentos WHERE vehiculo_id = ? AND activo = 1 ORDER BY fecha_vencimiento ASC',
            [$id]
        );
        $vehiculo['alertas_activas'] = $this->fetchAll(
            'SELECT id, tipo, titulo, mensaje, nivel, created_at
             FROM alertas WHERE vehiculo_id = ? AND atendida = 0 ORDER BY FIELD(nivel, "rojo", "amarillo", "verde"), created_at DESC',
            [$id]
        );
        $vehiculo['herramientas'] = $this->fetchAll(
            'SELECT tipo, estado, fecha_vencimiento, observaciones FROM herramientas_vehiculo WHERE vehiculo_id = ?',
            [$id]
        );
        $vehiculo['luces_tablero'] = $this->getLucesTablero($id);
        $vehiculo['luces_tablero_meta'] = $this->getLucesTableroMeta($id);
        $vehiculo['costos'] = $this->fetchOne(
            'SELECT costo_mantenimiento, costo_combustible, costo_total FROM v_costos_vehiculo WHERE vehiculo_id = ?',
            [$id]
        ) ?? ['costo_mantenimiento' => 0, 'costo_combustible' => 0, 'costo_total' => 0];

        $km = max(1, (int) $vehiculo['kilometraje_actual']);
        $vehiculo['kpis'] = [
            'costo_total' => (float) ($vehiculo['costos']['costo_total'] ?? 0),
            'costo_por_km' => round((float) ($vehiculo['costos']['costo_total'] ?? 0) / $km, 4),
            'rendimiento_promedio' => (float) ($this->fetchOne(
                'SELECT AVG(rendimiento) AS promedio FROM combustible_cargas WHERE vehiculo_id = ? AND rendimiento IS NOT NULL',
                [$id]
            )['promedio'] ?? 0),
            'incidencias_activas' => (int) ($this->fetchOne(
                'SELECT COUNT(*) AS c FROM danios WHERE vehiculo_id = ? AND estado NOT IN ("reparado", "cerrado_sin_accion")',
                [$id]
            )['c'] ?? 0),
        ];

        return $vehiculo;
    }

    public function existsNumeroEconomico(string $numero, ?int $excludeId = null): bool
    {
        return $this->existsField('numero_economico', $numero, $excludeId);
    }

    public function existsPlacas(string $placas, ?int $excludeId = null): bool
    {
        return $this->existsField('placas', $placas, $excludeId);
    }

    public function existsSerieVin(string $serieVin, ?int $excludeId = null): bool
    {
        return $this->existsField('serie_vin', $serieVin, $excludeId);
    }

    private function existsField(string $column, string $value, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM vehiculos WHERE {$column} = ? AND deleted_at IS NULL";
        $params = [$value];
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }
        return $this->fetchOne($sql, $params) !== null;
    }

    public function isAvailableForComision(int $id): bool
    {
        $row = $this->fetchOne(
            'SELECT estado FROM vehiculos WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
        return $row !== null && in_array($row['estado'], ['activo', 'disponible'], true);
    }

    public function findFoto(int $fotoId, int $vehiculoId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM vehiculo_fotos WHERE id = ? AND vehiculo_id = ?',
            [$fotoId, $vehiculoId]
        );
    }

    public function addFoto(int $vehiculoId, string $ruta, ?string $descripcion, bool $principal): void
    {
        if ($principal) {
            $this->execute('UPDATE vehiculo_fotos SET es_principal = 0 WHERE vehiculo_id = ?', [$vehiculoId]);
            $this->execute('UPDATE vehiculos SET foto_principal = ? WHERE id = ?', [$ruta, $vehiculoId]);
        }
        $this->execute(
            'INSERT INTO vehiculo_fotos (vehiculo_id, ruta, descripcion, es_principal) VALUES (?, ?, ?, ?)',
            [$vehiculoId, $ruta, $descripcion, $principal ? 1 : 0]
        );
    }

    public function setFotoPrincipal(int $vehiculoId, int $fotoId): bool
    {
        $foto = $this->findFoto($fotoId, $vehiculoId);
        if ($foto === null) {
            return false;
        }
        $this->execute('UPDATE vehiculo_fotos SET es_principal = 0 WHERE vehiculo_id = ?', [$vehiculoId]);
        $this->execute('UPDATE vehiculo_fotos SET es_principal = 1 WHERE id = ?', [$fotoId]);
        $this->execute('UPDATE vehiculos SET foto_principal = ? WHERE id = ?', [$foto['ruta'], $vehiculoId]);
        return true;
    }

    public function deleteFoto(int $vehiculoId, int $fotoId): ?array
    {
        $foto = $this->findFoto($fotoId, $vehiculoId);
        if ($foto === null) {
            return null;
        }
        $wasPrincipal = !empty($foto['es_principal']);
        $this->execute('DELETE FROM vehiculo_fotos WHERE id = ? AND vehiculo_id = ?', [$fotoId, $vehiculoId]);

        if ($wasPrincipal) {
            $next = $this->fetchOne(
                'SELECT id, ruta FROM vehiculo_fotos WHERE vehiculo_id = ? ORDER BY id ASC LIMIT 1',
                [$vehiculoId]
            );
            if ($next !== null) {
                $this->setFotoPrincipal($vehiculoId, (int) $next['id']);
            } else {
                $this->execute('UPDATE vehiculos SET foto_principal = NULL WHERE id = ?', [$vehiculoId]);
            }
        }

        return $foto;
    }

    /** @return list<string> */
    public function getLucesTablero(int $vehiculoId): array
    {
        $rows = $this->fetchAll(
            'SELECT luz_codigo FROM vehiculo_luces_tablero WHERE vehiculo_id = ? ORDER BY luz_codigo ASC',
            [$vehiculoId]
        );

        return array_column($rows, 'luz_codigo');
    }

    public function getLucesTableroMeta(int $vehiculoId): ?array
    {
        $meta = $this->fetchOne(
            'SELECT origen_tipo, origen_id, updated_at FROM vehiculo_luces_meta WHERE vehiculo_id = ?',
            [$vehiculoId]
        );
        if ($meta === null) {
            return null;
        }

        if ($meta['origen_tipo'] === 'comision') {
            $comision = $this->fetchOne(
                'SELECT folio, fecha FROM comisiones WHERE id = ?',
                [(int) $meta['origen_id']]
            );
            $meta['origen_label'] = $comision !== null
                ? 'Comisión ' . $comision['folio']
                : null;
        } else {
            $inspeccion = $this->fetchOne(
                'SELECT fecha FROM inspecciones WHERE id = ?',
                [(int) $meta['origen_id']]
            );
            $meta['origen_label'] = $inspeccion !== null
                ? 'Inspección del ' . $inspeccion['fecha']
                : null;
        }

        return $meta;
    }

    /** @param list<string> $luces */
    public function syncLucesTablero(int $vehiculoId, array $luces, string $origenTipo, int $origenId): void
    {
        $validCodes = array_column(InspeccionRepository::LUCES_TABLERO, 'codigo');
        $filtered = [];
        foreach ($luces as $codigo) {
            $codigo = (string) $codigo;
            if (in_array($codigo, $validCodes, true)) {
                $filtered[] = $codigo;
            }
        }
        $luces = array_values(array_unique($filtered));

        $this->execute('DELETE FROM vehiculo_luces_tablero WHERE vehiculo_id = ?', [$vehiculoId]);
        foreach ($luces as $codigo) {
            $this->execute(
                'INSERT INTO vehiculo_luces_tablero (vehiculo_id, luz_codigo) VALUES (?, ?)',
                [$vehiculoId, $codigo]
            );
        }

        $this->execute(
            'INSERT INTO vehiculo_luces_meta (vehiculo_id, origen_tipo, origen_id, updated_at)
             VALUES (?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE
                origen_tipo = VALUES(origen_tipo),
                origen_id = VALUES(origen_id),
                updated_at = NOW()',
            [$vehiculoId, $origenTipo, $origenId]
        );
    }
}
