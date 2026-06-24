<?php

declare(strict_types=1);

namespace App\Repositories;

final class AlertaRepository extends BaseRepository
{
    public function findById(int $id): ?array
    {
        return $this->fetchOne(
            'SELECT a.*, v.numero_economico, v.placas
             FROM alertas a
             LEFT JOIN vehiculos v ON v.id = a.vehiculo_id
             WHERE a.id = ?',
            [$id]
        );
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO alertas (vehiculo_id, documento_id, tipo, titulo, mensaje, nivel)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['vehiculo_id'] ?? null,
                $data['documento_id'] ?? null,
                $data['tipo'],
                $data['titulo'],
                $data['mensaje'],
                $data['nivel'],
            ]
        );

        return (int) $this->lastInsertId();
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $where = 'WHERE 1=1';

        if (isset($filters['atendida'])) {
            $where .= ' AND a.atendida = ?';
            $params[] = (int) $filters['atendida'];
        }
        if (!empty($filters['nivel'])) {
            $where .= ' AND a.nivel = ?';
            $params[] = $filters['nivel'];
        }
        if (!empty($filters['tipo'])) {
            $where .= ' AND a.tipo = ?';
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['vehiculo_id'])) {
            $where .= ' AND a.vehiculo_id = ?';
            $params[] = (int) $filters['vehiculo_id'];
        }

        $total = (int) ($this->fetchOne(
            "SELECT COUNT(*) AS c FROM alertas a {$where}",
            $params
        )['c'] ?? 0);

        $queryParams = array_merge($params, [$perPage, $offset]);
        $rows = $this->fetchAll(
            "SELECT a.id, a.vehiculo_id, a.documento_id, a.tipo, a.titulo, a.mensaje, a.nivel,
                    a.atendida, a.created_at, v.numero_economico, d.fecha_vencimiento
             FROM alertas a
             LEFT JOIN vehiculos v ON v.id = a.vehiculo_id
             LEFT JOIN documentos d ON d.id = a.documento_id
             {$where}
             ORDER BY a.atendida ASC, FIELD(a.nivel, 'rojo', 'amarillo', 'verde'), a.created_at DESC
             LIMIT ? OFFSET ?",
            $queryParams
        );

        return ['data' => $rows, 'total' => $total, 'page' => $page, 'per_page' => $perPage];
    }

    public function atender(int $id, int $userId, ?string $comentario): bool
    {
        return $this->execute(
            'UPDATE alertas SET atendida = 1, atendida_por = ?, atendida_en = NOW(),
                    comentario_atencion = ?, updated_at = NOW()
             WHERE id = ? AND atendida = 0',
            [$userId, $comentario, $id]
        );
    }

    public function getDashboardCounts(): array
    {
        $rows = $this->fetchAll(
            'SELECT nivel, COUNT(*) AS total FROM alertas WHERE atendida = 0 GROUP BY nivel'
        );

        $counts = ['verde' => 0, 'amarillo' => 0, 'rojo' => 0, 'total' => 0];
        foreach ($rows as $row) {
            $counts[$row['nivel']] = (int) $row['total'];
            $counts['total'] += (int) $row['total'];
        }

        return $counts;
    }

    public function generarFromDocumentos(): int
    {
        $documentos = $this->fetchAll(
            'SELECT d.*, v.numero_economico
             FROM documentos d
             JOIN vehiculos v ON v.id = d.vehiculo_id
             WHERE d.activo = 1 AND d.fecha_vencimiento IS NOT NULL AND v.deleted_at IS NULL'
        );

        $generadas = 0;

        foreach ($documentos as $doc) {
            $tipoAlerta = $this->mapTipoDocumentoToAlerta($doc['tipo']);
            if ($tipoAlerta === null) {
                continue;
            }

            $config = $this->getEffectiveConfig((int) $doc['vehiculo_id'], $tipoAlerta);
            if ($config === null) {
                continue;
            }

            $diasRestantes = (int) ((strtotime($doc['fecha_vencimiento']) - strtotime(date('Y-m-d'))) / 86400);
            $nivel = $this->calcularNivelDias($diasRestantes, $config);

            if ($nivel === null) {
                continue;
            }

            $exists = $this->fetchOne(
                'SELECT id FROM alertas WHERE documento_id = ? AND atendida = 0 AND tipo = ?',
                [$doc['id'], $tipoAlerta]
            );

            if ($exists !== null) {
                $this->execute(
                    'UPDATE alertas SET nivel = ?, mensaje = ?, updated_at = NOW() WHERE id = ?',
                    [
                        $nivel,
                        $this->buildDocumentoMensaje($doc, $diasRestantes),
                        $exists['id'],
                    ]
                );
                continue;
            }

            $this->create([
                'vehiculo_id' => (int) $doc['vehiculo_id'],
                'documento_id' => (int) $doc['id'],
                'tipo' => $tipoAlerta,
                'titulo' => ucfirst(str_replace('_', ' ', $tipoAlerta)) . ' — ' . $doc['numero_economico'],
                'mensaje' => $this->buildDocumentoMensaje($doc, $diasRestantes),
                'nivel' => $nivel,
            ]);
            $generadas++;
        }

        return $generadas;
    }

    public function findActive(int $vehiculoId, string $tipo): ?array
    {
        return $this->fetchOne(
            'SELECT id FROM alertas WHERE vehiculo_id = ? AND tipo = ? AND atendida = 0 LIMIT 1',
            [$vehiculoId, $tipo]
        );
    }

    public function existsActive(int $vehiculoId, string $tipo): bool
    {
        return $this->findActive($vehiculoId, $tipo) !== null;
    }

    public function updateMensaje(int $id, string $mensaje, string $nivel): bool
    {
        return $this->execute(
            'UPDATE alertas SET mensaje = ?, nivel = ?, updated_at = NOW() WHERE id = ?',
            [$mensaje, $nivel, $id]
        );
    }

    public function getAlertaConfig(string $tipo): ?array
    {
        return $this->fetchOne('SELECT * FROM alerta_config WHERE tipo = ? AND activo = 1', [$tipo]);
    }

    public function getEffectiveConfig(int $vehiculoId, string $tipo): ?array
    {
        $global = $this->fetchOne('SELECT * FROM alerta_config WHERE tipo = ? AND activo = 1', [$tipo]);
        if ($global === null) {
            return null;
        }

        $custom = $this->fetchOne(
            'SELECT * FROM vehiculo_alerta_config WHERE vehiculo_id = ? AND tipo = ? AND activo = 1',
            [$vehiculoId, $tipo]
        );

        if ($custom === null) {
            return $global;
        }

        return array_merge($global, [
            'umbral_verde' => (int) $custom['umbral_verde'],
            'umbral_amarillo' => (int) $custom['umbral_amarillo'],
            'umbral_rojo' => (int) $custom['umbral_rojo'],
            'umbral_verde_dias' => $custom['umbral_verde_dias'],
            'umbral_amarillo_dias' => $custom['umbral_amarillo_dias'],
            'umbral_rojo_dias' => $custom['umbral_rojo_dias'],
        ]);
    }

    public function getAllConfig(): array
    {
        return $this->fetchAll('SELECT * FROM alerta_config ORDER BY nombre ASC');
    }

    /** @return list<array{tipo: string, nombre: string}> */
    public function getServiciosKm(): array
    {
        return $this->fetchAll(
            'SELECT tipo, nombre FROM alerta_config WHERE unidad = "km" AND activo = 1 ORDER BY nombre ASC'
        );
    }

    public function atenderActivasPorServicio(int $vehiculoId, string $tipoServicio, int $userId): bool
    {
        return $this->execute(
            'UPDATE alertas SET atendida = 1, atendida_por = ?, atendida_en = NOW(),
                    comentario_atencion = ?, updated_at = NOW()
             WHERE vehiculo_id = ? AND tipo = ? AND atendida = 0',
            [$userId, 'Atendida automáticamente al registrar mantenimiento.', $vehiculoId, $tipoServicio]
        );
    }

    public function updateConfig(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE alerta_config SET
                nombre = ?, umbral_verde = ?, umbral_amarillo = ?, umbral_rojo = ?,
                umbral_verde_dias = ?, umbral_amarillo_dias = ?, umbral_rojo_dias = ?,
                activo = ?
             WHERE id = ?',
            [
                $data['nombre'] ?? '',
                (int) ($data['umbral_verde'] ?? 0),
                (int) ($data['umbral_amarillo'] ?? 0),
                (int) ($data['umbral_rojo'] ?? 0),
                $this->nullableInt($data['umbral_verde_dias'] ?? null),
                $this->nullableInt($data['umbral_amarillo_dias'] ?? null),
                $this->nullableInt($data['umbral_rojo_dias'] ?? null),
                isset($data['activo']) ? (int) $data['activo'] : 1,
                $id,
            ]
        );
    }

    public function getVehiculoConfig(int $vehiculoId, string $tipo): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM vehiculo_alerta_config WHERE vehiculo_id = ? AND tipo = ? AND activo = 1',
            [$vehiculoId, $tipo]
        );
    }

    public function getVehiculoConfigAll(int $vehiculoId): array
    {
        $rows = $this->fetchAll(
            'SELECT * FROM vehiculo_alerta_config WHERE vehiculo_id = ? ORDER BY tipo ASC',
            [$vehiculoId]
        );
        $map = [];
        foreach ($rows as $row) {
            $map[$row['tipo']] = $row;
        }

        return $map;
    }

    public function upsertVehiculoConfig(int $vehiculoId, string $tipo, array $data): void
    {
        $existing = $this->fetchOne(
            'SELECT id FROM vehiculo_alerta_config WHERE vehiculo_id = ? AND tipo = ?',
            [$vehiculoId, $tipo]
        );

        $params = [
            (int) ($data['umbral_verde'] ?? 0),
            (int) ($data['umbral_amarillo'] ?? 0),
            (int) ($data['umbral_rojo'] ?? 0),
            $this->nullableInt($data['umbral_verde_dias'] ?? null),
            $this->nullableInt($data['umbral_amarillo_dias'] ?? null),
            $this->nullableInt($data['umbral_rojo_dias'] ?? null),
            isset($data['activo']) ? (int) $data['activo'] : 1,
        ];

        if ($existing !== null) {
            $this->execute(
                'UPDATE vehiculo_alerta_config SET
                    umbral_verde = ?, umbral_amarillo = ?, umbral_rojo = ?,
                    umbral_verde_dias = ?, umbral_amarillo_dias = ?, umbral_rojo_dias = ?,
                    activo = ?, updated_at = NOW()
                 WHERE id = ?',
                array_merge($params, [(int) $existing['id']])
            );

            return;
        }

        $this->execute(
            'INSERT INTO vehiculo_alerta_config (
                vehiculo_id, tipo, umbral_verde, umbral_amarillo, umbral_rojo,
                umbral_verde_dias, umbral_amarillo_dias, umbral_rojo_dias, activo
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            array_merge([$vehiculoId, $tipo], $params)
        );
    }

    public function deleteVehiculoConfig(int $vehiculoId, string $tipo): void
    {
        $this->execute(
            'DELETE FROM vehiculo_alerta_config WHERE vehiculo_id = ? AND tipo = ?',
            [$vehiculoId, $tipo]
        );
    }

    private function mapTipoDocumentoToAlerta(string $tipo): ?string
    {
        return match ($tipo) {
            'poliza' => 'seguro',
            'tenencia' => 'tenencia',
            'verificacion' => 'verificacion',
            'licencia' => 'licencia',
            default => null,
        };
    }

    private function calcularNivelDias(int $diasRestantes, array $config): ?string
    {
        if ($diasRestantes < 0 || $diasRestantes <= (int) $config['umbral_rojo']) {
            return 'rojo';
        }
        if ($diasRestantes <= (int) $config['umbral_amarillo']) {
            return 'amarillo';
        }
        if ($diasRestantes <= (int) $config['umbral_verde']) {
            return 'verde';
        }
        return null;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function buildDocumentoMensaje(array $doc, int $diasRestantes): string
    {
        if ($diasRestantes < 0) {
            return sprintf(
                '%s · vencido hace %d día(s)',
                $doc['titulo'],
                abs($diasRestantes)
            );
        }

        return sprintf(
            '%s · vence en %d día(s)',
            $doc['titulo'],
            $diasRestantes
        );
    }
}
