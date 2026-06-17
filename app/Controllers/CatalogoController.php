<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Repositories\AreaRepository;
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
}
