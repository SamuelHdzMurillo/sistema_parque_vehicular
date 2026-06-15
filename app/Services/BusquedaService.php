<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\BaseRepository;

final class BusquedaService extends BaseRepository
{
    public function search(string $query, int $limit = 20): array
    {
        if (strlen(trim($query)) < 2) {
            return [];
        }
        $like = '%' . trim($query) . '%';
        $vehiculos = $this->fetchAll(
            'SELECT id, numero_economico AS titulo, CONCAT(marca, " ", modelo, " — ", placas) AS subtitulo, "vehiculo" AS tipo
             FROM vehiculos WHERE deleted_at IS NULL AND (numero_economico LIKE ? OR placas LIKE ? OR marca LIKE ?)
             LIMIT ?',
            [$like, $like, $like, $limit]
        );
        $comisiones = $this->fetchAll(
            'SELECT c.id, c.folio AS titulo, CONCAT(c.destino, " — ", v.numero_economico) AS subtitulo, "comision" AS tipo
             FROM comisiones c JOIN vehiculos v ON v.id = c.vehiculo_id
             WHERE c.folio LIKE ? OR c.destino LIKE ? LIMIT ?',
            [$like, $like, $limit]
        );
        $usuarios = $this->fetchAll(
            'SELECT id, CONCAT(nombre, " ", apellido_paterno) AS titulo, email AS subtitulo, "usuario" AS tipo
             FROM users WHERE deleted_at IS NULL AND (nombre LIKE ? OR email LIKE ?) LIMIT ?',
            [$like, $like, $limit]
        );
        return array_merge($vehiculos, $comisiones, $usuarios);
    }
}
