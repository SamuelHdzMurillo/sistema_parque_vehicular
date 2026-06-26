<?php

declare(strict_types=1);

namespace App\Repositories;

final class InspeccionRepository extends BaseRepository
{
    /** @var list<array{codigo: string, nombre: string, icon: string}> */
    public const LUCES_TABLERO = [
        ['codigo' => 'check_engine', 'nombre' => 'Motor (Check Engine)', 'icon' => 'check_engine.svg'],
        ['codigo' => 'aceite', 'nombre' => 'Presión de aceite', 'icon' => 'aceite.svg'],
        ['codigo' => 'bateria', 'nombre' => 'Sistema de carga / batería', 'icon' => 'bateria.svg'],
        ['codigo' => 'frenos', 'nombre' => 'Sistema de frenos', 'icon' => 'frenos.svg'],
        ['codigo' => 'freno_mano', 'nombre' => 'Freno de estacionamiento', 'icon' => 'freno_mano.svg'],
        ['codigo' => 'abs', 'nombre' => 'ABS', 'icon' => 'abs.svg'],
        ['codigo' => 'pastillas_freno', 'nombre' => 'Desgaste de pastillas', 'icon' => 'pastillas_freno.svg'],
        ['codigo' => 'pedal_freno', 'nombre' => 'Presionar pedal de freno', 'icon' => 'pedal_freno.svg'],
        ['codigo' => 'airbag', 'nombre' => 'Airbag', 'icon' => 'airbag.svg'],
        ['codigo' => 'cinturon', 'nombre' => 'Cinturón de seguridad', 'icon' => 'cinturon.svg'],
        ['codigo' => 'combustible', 'nombre' => 'Combustible bajo', 'icon' => 'combustible.svg'],
        ['codigo' => 'tpms', 'nombre' => 'Presión de llantas (TPMS)', 'icon' => 'tpms.svg'],
        ['codigo' => 'direccion', 'nombre' => 'Dirección asistida', 'icon' => 'direccion.svg'],
        ['codigo' => 'esp', 'nombre' => 'Control de estabilidad (ESP)', 'icon' => 'esp.svg'],
        ['codigo' => 'traccion', 'nombre' => 'Control de tracción', 'icon' => 'traccion.svg'],
        ['codigo' => 'fallo_luces', 'nombre' => 'Falla de luz exterior', 'icon' => 'fallo_luces.svg'],
        ['codigo' => 'luces_cortas', 'nombre' => 'Luces cortas', 'icon' => 'luces_cortas.svg'],
        ['codigo' => 'luces_altas', 'nombre' => 'Luces altas', 'icon' => 'luces_altas.svg'],
        ['codigo' => 'direccionales', 'nombre' => 'Direccionales', 'icon' => 'direccionales.svg'],
        ['codigo' => 'niebla_del', 'nombre' => 'Faros antiniebla', 'icon' => 'niebla_del.svg'],
        ['codigo' => 'niebla_tras', 'nombre' => 'Antiniebla trasero', 'icon' => 'niebla_tras.svg'],
        ['codigo' => 'reg_faros', 'nombre' => 'Regulación de faros', 'icon' => 'reg_faros.svg'],
        ['codigo' => 'precalentamiento', 'nombre' => 'Precalentamiento (diésel)', 'icon' => 'precalentamiento.svg'],
        ['codigo' => 'dpf', 'nombre' => 'Filtro de partículas (DPF)', 'icon' => 'dpf.svg'],
        ['codigo' => 'limpiaparabrisas', 'nombre' => 'Nivel líquido limpiaparabrisas', 'icon' => 'limpiaparabrisas.svg'],
        ['codigo' => 'control_crucero', 'nombre' => 'Control de crucero', 'icon' => 'control_crucero.svg'],
        ['codigo' => 'llave', 'nombre' => 'Inmovilizador / llave', 'icon' => 'llave.svg'],
        ['codigo' => 'puerta', 'nombre' => 'Puerta abierta', 'icon' => 'puerta.svg'],
    ];

    public const INSPECCION_ITEMS = [
        ['codigo' => 'aceite', 'nombre' => 'Aceite'],
        ['codigo' => 'anticongelante', 'nombre' => 'Anticongelante'],
        ['codigo' => 'frenos', 'nombre' => 'Frenos'],
        ['codigo' => 'direccion_hidraulica', 'nombre' => 'Dirección hidráulica'],
        ['codigo' => 'bateria', 'nombre' => 'Batería'],
        ['codigo' => 'luces', 'nombre' => 'Luces'],
        ['codigo' => 'direccionales', 'nombre' => 'Direccionales'],
        ['codigo' => 'llantas', 'nombre' => 'Llantas'],
        ['codigo' => 'suspension', 'nombre' => 'Suspensión'],
        ['codigo' => 'herramientas', 'nombre' => 'Herramientas'],
        ['codigo' => 'equipo_emergencia', 'nombre' => 'Equipo de emergencia'],
    ];

    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT i.*, v.numero_economico, CONCAT(u.nombre, " ", u.apellido_paterno) AS responsable_nombre
             FROM inspecciones i
             JOIN vehiculos v ON v.id = i.vehiculo_id
             JOIN users u ON u.id = i.responsable_id
             WHERE i.id = ?',
            [$id]
        );
    }

    public function findWithItems(int $id): ?array
    {
        $inspeccion = $this->findById($id);
        if ($inspeccion === null) {
            return null;
        }

        $inspeccion['items'] = $this->fetchAll(
            'SELECT * FROM inspeccion_items WHERE inspeccion_id = ? ORDER BY id ASC',
            [$id]
        );
        $inspeccion['fotos'] = $this->fetchAll(
            'SELECT * FROM inspeccion_fotos WHERE inspeccion_id = ? ORDER BY id ASC',
            [$id]
        );
        $inspeccion['luces_tablero'] = $this->getLucesTablero($id);

        return $inspeccion;
    }

    public function getLucesTablero(int $inspeccionId): array
    {
        return $this->fetchAll(
            'SELECT luz_codigo FROM inspeccion_luces_tablero WHERE inspeccion_id = ? ORDER BY luz_codigo ASC',
            [$inspeccionId]
        );
    }

    public function getLucesTableroCatalog(): array
    {
        return self::LUCES_TABLERO;
    }

    public static function luzTableroByCodigo(string $codigo): ?array
    {
        foreach (self::LUCES_TABLERO as $luz) {
            if ($luz['codigo'] === $codigo) {
                return $luz;
            }
        }
        return null;
    }

    public function generateFolio(): string
    {
        $year = date('Y');
        $prefix = "INS-{$year}-";
        $rows = $this->fetchAll(
            'SELECT folio FROM inspecciones WHERE folio LIKE ?',
            ["{$prefix}%"]
        );
        $maxSeq = 0;
        foreach ($rows as $row) {
            if (preg_match('/(\d+)$/', (string) $row['folio'], $m)) {
                $maxSeq = max($maxSeq, (int) $m[1]);
            }
        }

        return $prefix . str_pad((string) ($maxSeq + 1), 4, '0', STR_PAD_LEFT);
    }

    public function folioExists(string $folio, ?int $excludeId = null): bool
    {
        $params = [$folio];
        $sql = 'SELECT COUNT(*) AS c FROM inspecciones WHERE folio = ?';
        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return ((int) ($this->fetchOne($sql, $params)['c'] ?? 0)) > 0;
    }

    public function createWithItems(array $data, array $items, array $lucesTablero = []): int
    {
        $this->db->beginTransaction();
        try {
            $this->execute(
                'INSERT INTO inspecciones (
                    folio, vehiculo_id, responsable_id, kilometraje, es_historico, nivel_combustible, fecha,
                    observaciones_generales, firma_digital, resultado_general
                 ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $data['folio'],
                    (int) $data['vehiculo_id'],
                    (int) $data['responsable_id'],
                    (int) $data['kilometraje'],
                    !empty($data['es_historico']) ? 1 : 0,
                    $data['nivel_combustible'] ?? null,
                    $data['fecha'],
                    $data['observaciones_generales'] ?? null,
                    $data['firma_digital'] ?? null,
                    $data['resultado_general'] ?? 'aprobada',
                ]
            );

            $inspeccionId = (int) $this->lastInsertId();

            foreach ($items as $item) {
                $this->execute(
                    'INSERT INTO inspeccion_items (inspeccion_id, item_codigo, item_nombre, calificacion, observaciones)
                     VALUES (?, ?, ?, ?, ?)',
                    [
                        $inspeccionId,
                        $item['item_codigo'],
                        $item['item_nombre'],
                        $item['calificacion'],
                        $item['observaciones'] ?? null,
                    ]
                );
            }

            foreach ($lucesTablero as $luzCodigo) {
                $this->execute(
                    'INSERT INTO inspeccion_luces_tablero (inspeccion_id, luz_codigo) VALUES (?, ?)',
                    [$inspeccionId, $luzCodigo]
                );
            }

            $this->db->commit();
            return $inspeccionId;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND i.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }
        if (!empty($filters['resultado'])) {
            $where .= ' AND i.resultado_general = ?';
            $params[] = $filters['resultado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where .= ' AND i.fecha >= ?';
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where .= ' AND i.fecha <= ?';
            $params[] = $filters['fecha_hasta'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM inspecciones i {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT i.id, i.folio, i.fecha, i.kilometraje, i.nivel_combustible, i.resultado_general,
                    v.numero_economico,
                    CONCAT(u.nombre, ' ', u.apellido_paterno) AS responsable_nombre,
                    (SELECT COUNT(*) FROM inspeccion_items ii WHERE ii.inspeccion_id = i.id AND ii.calificacion = 'malo') AS items_malo
             FROM inspecciones i
             JOIN vehiculos v ON v.id = i.vehiculo_id
             JOIN users u ON u.id = i.responsable_id
             {$where}
             ORDER BY i.fecha DESC, i.id DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function countConsecutiveRegular(int $vehiculoId, string $itemCodigo, int $limit = 2): int
    {
        $rows = $this->fetchAll(
            'SELECT ii.calificacion
             FROM inspecciones i
             JOIN inspeccion_items ii ON ii.inspeccion_id = i.id
             WHERE i.vehiculo_id = ? AND ii.item_codigo = ?
             ORDER BY i.fecha DESC, i.id DESC
             LIMIT ?',
            [$vehiculoId, $itemCodigo, $limit]
        );

        $count = 0;
        foreach ($rows as $row) {
            if ($row['calificacion'] === 'regular') {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    public function getItemsMalos(int $inspeccionId): array
    {
        return $this->fetchAll(
            'SELECT * FROM inspeccion_items WHERE inspeccion_id = ? AND calificacion = "malo"',
            [$inspeccionId]
        );
    }

    public function delete(int $id): bool
    {
        return $this->execute('DELETE FROM inspecciones WHERE id = ?', [$id]);
    }
}
