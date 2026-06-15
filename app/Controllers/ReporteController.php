<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\ReporteService;

final class ReporteController extends BaseController
{
    public function __construct(
        private readonly ReporteService $reportes = new ReporteService()
    ) {
    }

    public function index(Request $request): never
    {
        $tipos = $this->reportes->getAvailableTypes();
        $tipo = (string) $request->input('tipo', 'vehiculos');
        $data = $this->reportes->getReportData($tipo);
        $this->render('reportes.index', [
            'tipos' => $tipos,
            'tipo' => $tipo,
            'data' => $data,
        ]);
    }

    public function export(Request $request, string $tipo): never
    {
        $format = (string) $request->input('format', 'csv');
        $this->reportes->export($tipo, $format);
    }
}
