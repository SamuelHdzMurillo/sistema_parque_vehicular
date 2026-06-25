<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\ComisionRepository;
use App\Services\AuditService;

final class HerramientaService extends BaseRepository
{
    /** @var array<string, string> */
    public const ESTADOS = [
        'presente' => 'Presente',
        'ausente' => 'Ausente',
        'danado' => 'Dañado',
        'vencido' => 'Vencido',
    ];

    public function listByVehiculo(int $vehiculoId): ?array
    {
        $vehiculo = $this->fetchOne(
            'SELECT id, numero_economico, placas FROM vehiculos WHERE id = ? AND deleted_at IS NULL',
            [$vehiculoId]
        );
        if ($vehiculo === null) {
            return null;
        }

        $rows = $this->fetchAll(
            'SELECT * FROM herramientas_vehiculo WHERE vehiculo_id = ? ORDER BY tipo',
            [$vehiculoId]
        );
        $byTipo = [];
        foreach ($rows as $row) {
            $byTipo[(string) $row['tipo']] = $row;
        }

        $catalogo = ComisionRepository::HERRAMIENTAS;
        $catalogCodes = array_column($catalogo, 'codigo');
        $herramientas = [];
        foreach ($catalogo as $item) {
            $codigo = $item['codigo'];
            if (isset($byTipo[$codigo])) {
                $row = $byTipo[$codigo];
                $row['registrada'] = true;
                $herramientas[] = $row;
                continue;
            }
            $herramientas[] = [
                'tipo' => $codigo,
                'estado' => 'presente',
                'fecha_vencimiento' => null,
                'observaciones' => null,
                'registrada' => false,
            ];
        }

        foreach ($rows as $row) {
            $tipo = (string) $row['tipo'];
            if (!in_array($tipo, $catalogCodes, true)) {
                $row['registrada'] = true;
                $herramientas[] = $row;
            }
        }

        return [
            'vehiculo' => $vehiculo,
            'herramientas' => $herramientas,
            'catalogo' => $catalogo,
            'estados' => self::ESTADOS,
        ];
    }

    public function updateByVehiculo(int $vehiculoId, array $items, int $userId): void
    {
        foreach ($items as $tipo => $estado) {
            if (!is_string($tipo) || !is_string($estado)) {
                continue;
            }
            if (!isset(self::ESTADOS[$estado])) {
                continue;
            }
            if (!herramienta_es_codigo_valido($tipo)) {
                continue;
            }
            $actual = $this->fetchOne(
                'SELECT id, estado FROM herramientas_vehiculo WHERE vehiculo_id = ? AND tipo = ?',
                [$vehiculoId, $tipo]
            );
            if ($actual === null) {
                $this->execute(
                    'INSERT INTO herramientas_vehiculo (vehiculo_id, tipo, estado) VALUES (?, ?, ?)',
                    [$vehiculoId, $tipo, $estado]
                );
                continue;
            }
            if ($actual['estado'] === $estado) {
                continue;
            }
            $this->execute(
                'UPDATE herramientas_vehiculo SET estado = ? WHERE id = ?',
                [$estado, $actual['id']]
            );
            $this->execute(
                'INSERT INTO herramienta_reposiciones (herramienta_id, fecha, estado_anterior, estado_nuevo, user_id)
                 VALUES (?, CURDATE(), ?, ?, ?)',
                [$actual['id'], $actual['estado'], $estado, $userId]
            );
        }
        AuditService::log('UPDATE', 'herramientas_vehiculo', $vehiculoId, null, $items);
    }

    public function addCustom(int $vehiculoId, string $nombre, string $estado, int $userId): ?string
    {
        $nombre = trim($nombre);
        if ($nombre === '' || mb_strlen($nombre) > 40) {
            return 'Indique un nombre de herramienta válido (máx. 40 caracteres).';
        }
        if (!isset(self::ESTADOS[$estado])) {
            return 'Estado de herramienta inválido.';
        }

        $tipo = herramienta_slug($nombre);
        if (!herramienta_es_codigo_valido($tipo)) {
            return 'No se pudo generar un identificador para la herramienta.';
        }

        $exists = $this->fetchOne(
            'SELECT id FROM herramientas_vehiculo WHERE vehiculo_id = ? AND tipo = ?',
            [$vehiculoId, $tipo]
        );
        if ($exists !== null) {
            return 'Esa herramienta ya está registrada en el vehículo.';
        }

        $this->execute(
            'INSERT INTO herramientas_vehiculo (vehiculo_id, tipo, estado) VALUES (?, ?, ?)',
            [$vehiculoId, $tipo, $estado]
        );
        AuditService::log('CREATE', 'herramientas_vehiculo', $vehiculoId, null, [
            'tipo' => $tipo,
            'nombre' => $nombre,
            'estado' => $estado,
        ]);

        return null;
    }

    /** @return list<string> */
    public function getPresentesByVehiculo(int $vehiculoId): array
    {
        $rows = $this->fetchAll(
            'SELECT tipo FROM herramientas_vehiculo WHERE vehiculo_id = ? AND estado = ? ORDER BY tipo',
            [$vehiculoId, 'presente']
        );
        $presentes = [];
        foreach ($rows as $row) {
            $presentes[] = (string) $row['tipo'];
        }
        return $presentes;
    }
}
