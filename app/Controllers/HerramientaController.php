<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\HerramientaService;

final class HerramientaController extends BaseController
{
    public function __construct(
        private readonly HerramientaService $herramientas = new HerramientaService()
    ) {
    }

    public function index(Request $request, string $vehiculoId): never
    {
        $data = $this->herramientas->listByVehiculo((int) $vehiculoId);
        if ($data === null) {
            flash('error', 'Vehículo no encontrado.');
            $this->redirect('vehiculos');
        }
        $this->render('herramientas.index', $data);
    }

    public function update(Request $request, string $vehiculoId): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $items = $request->input('herramientas', []);
        if (!is_array($items)) {
            flash('error', 'Datos de herramientas inválidos.');
            $this->redirect('herramientas/vehiculo/' . $vehiculoId);
        }

        $this->herramientas->updateByVehiculo((int) $vehiculoId, $items, $userId);
        flash('success', 'Inventario de herramientas actualizado.');
        $this->redirect('herramientas/vehiculo/' . $vehiculoId);
    }
}
