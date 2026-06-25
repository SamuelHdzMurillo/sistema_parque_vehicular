<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\AlertaService;

final class AlertaController extends BaseController
{
    public function __construct(
        private readonly AlertaService $alertas = new AlertaService()
    ) {
    }

    public function index(Request $request): never
    {
        $page = max(1, (int) $request->input('page', 1));
        $historial = (bool) $request->input('historial') || (bool) $request->input('todas');
        $soloConAvisos = (bool) $request->input('pendientes');
        $vehiculoId = (int) $request->input('vehiculo_id', 0);
        $vehiculoId = $vehiculoId > 0 ? $vehiculoId : null;

        if ($historial) {
            $result = $this->alertas->paginate($page, false, $vehiculoId);
            $result['modo'] = 'historial';
            $result['solo_pendientes'] = false;
            $result['vehiculo_id'] = $vehiculoId;
            $result['vehiculos'] = $this->alertas->getVehiculosCatalogo();
        } else {
            $result = $this->alertas->getMatrizMantenimiento($page, $soloConAvisos, $vehiculoId);
        }

        $result['counts'] = $this->alertas->getDashboardCounts();
        $this->render('alertas.index', $result);
    }

    public function atender(Request $request, string $id): never
    {
        $this->validateCsrf($request);
        $userId = auth_id();
        if ($userId === null) {
            $this->redirect('login');
        }

        $error = $this->alertas->atender(
            (int) $id,
            $userId,
            $request->input('comentario') ? (string) $request->input('comentario') : null
        );
        flash($error ? 'error' : 'success', $error ?? 'Alerta atendida correctamente.');
        $this->redirect('alertas');
    }
}
