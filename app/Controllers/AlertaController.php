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
        $todas = (bool) $request->input('todas');
        $result = $this->alertas->paginate($page, !$todas);
        $result['counts'] = $this->alertas->getDashboardCounts();
        $result['solo_pendientes'] = !$todas;
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

    public function config(Request $request): never
    {
        if ($request->isPost()) {
            $this->validateCsrf($request);

            $vehiculoId = (int) $request->input('vehiculo_id', 0);
            $vehiculoConfig = $request->input('vehiculo_config', []);

            if ($vehiculoId > 0 && is_array($vehiculoConfig)) {
                $this->alertas->updateVehiculoConfig($vehiculoId, $vehiculoConfig);
                flash('success', 'Configuración del vehículo guardada.');
                $this->redirect('alertas/config?vehiculo_id=' . $vehiculoId);
            }

            $config = $request->input('config', []);
            if (is_array($config)) {
                $this->alertas->updateConfig($config);
            }
            flash('success', 'Configuración global de alertas guardada.');
            $this->redirect('alertas/config');
        }

        $vehiculoId = (int) $request->input('vehiculo_id', 0);
        $this->render('alertas.config', $this->alertas->getConfigPageData($vehiculoId > 0 ? $vehiculoId : null));
    }
}
