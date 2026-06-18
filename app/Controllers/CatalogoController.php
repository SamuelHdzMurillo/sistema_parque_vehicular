<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Repositories\AreaRepository;
use App\Repositories\CatalogoRepository;
use App\Repositories\ConductorRepository;
use App\Repositories\PlantelRepository;

final class CatalogoController extends BaseController
{
    public function index(Request $request): never
    {
        $planteles = new PlantelRepository();
        $areas = new AreaRepository();
        $conductores = new ConductorRepository();

        $this->render('catalogos.index', [
            'stats' => [
                'planteles' => (int) ($planteles->paginate(1, 1)['total'] ?? 0),
                'areas' => (int) ($areas->paginate(1, 1)['total'] ?? 0),
                'conductores' => (int) ($conductores->paginate(1, 1)['total'] ?? 0),
            ],
        ]);
    }

    public function apiPlanteles(Request $request): never
    {
        $items = (new CatalogoRepository())->getPlanteles();
        $mapped = array_map(static function (array $p): array {
            return [
                'id' => (int) $p['id'],
                'clave' => (string) $p['clave'],
                'nombre' => (string) $p['nombre'],
                'label' => $p['clave'] . ' — ' . $p['nombre'],
            ];
        }, $items);

        Response::json(['ok' => true, 'items' => $mapped]);
    }

    public function apiAreas(Request $request): never
    {
        $items = (new CatalogoRepository())->getAreas();
        $mapped = array_map(static function (array $a): array {
            return [
                'id' => (int) $a['id'],
                'label' => catalogo_area_label($a),
            ];
        }, $items);

        Response::json(['ok' => true, 'items' => $mapped]);
    }

    public function apiConductores(Request $request): never
    {
        $items = (new CatalogoRepository())->getConductores();
        $mapped = array_map(static function (array $c): array {
            $areaLabel = (string) ($c['area_label'] ?? catalogo_area_label($c));
            $nombre = (string) $c['nombre'];
            $telefono = (string) $c['telefono'];

            return [
                'id' => (int) $c['id'],
                'nombre' => $nombre,
                'telefono' => $telefono,
                'area_label' => $areaLabel,
                'label' => $nombre . ' — ' . $areaLabel . ' — ' . $telefono,
            ];
        }, $items);

        Response::json(['ok' => true, 'items' => $mapped]);
    }
}
