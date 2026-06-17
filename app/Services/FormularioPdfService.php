<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\View;
use App\Repositories\CombustibleRepository;
use App\Repositories\ComisionRepository;
use App\Repositories\DanioRepository;
use App\Repositories\InspeccionRepository;
use App\Repositories\MantenimientoRepository;
use Dompdf\Dompdf;
use Dompdf\Options;

final class FormularioPdfService
{
    public function __construct(
        private readonly ComisionRepository $comisiones = new ComisionRepository(),
        private readonly InspeccionRepository $inspecciones = new InspeccionRepository(),
        private readonly MantenimientoRepository $mantenimientos = new MantenimientoRepository(),
        private readonly DanioRepository $danios = new DanioRepository(),
        private readonly CombustibleRepository $combustible = new CombustibleRepository(),
    ) {
    }

    public function comision(?int $id = null, string $parte = 'completo'): never
    {
        $parte = in_array($parte, ['salida', 'regreso', 'completo'], true) ? $parte : 'completo';
        $data = $id !== null ? $this->comisiones->findById($id) : null;
        if ($id !== null && $data === null) {
            http_response_code(404);
            exit('Comisión no encontrada.');
        }
        $ultimoMantenimiento = null;
        if ($data !== null) {
            $ultimoMantenimiento = $this->mantenimientos->getUltimoFinalizado((int) $data['vehiculo_id']);
            $luces = $this->comisiones->getLuces((int) $data['id']);
            $data['luces_salida'] = $luces['salida'];
            $data['luces_regreso'] = $luces['regreso'];
            $niveles = $this->comisiones->getNiveles((int) $data['id']);
            $data['niveles_salida'] = $niveles['salida'];
            $data['niveles_regreso'] = $niveles['regreso'];
        }
        $sufijo = $parte !== 'completo' ? '_' . $parte : '';
        $this->stream(
            'pdf.comision',
            [
                'comision' => $data,
                'parte' => $parte,
                'ultimo_mantenimiento' => $ultimoMantenimiento,
                'luces_catalogo' => InspeccionRepository::LUCES_TABLERO,
                'liquidos_catalogo' => ComisionRepository::LIQUIDOS,
                'nivel_opciones' => ComisionRepository::NIVEL_OPCIONES,
            ],
            'comision_' . ($data['folio'] ?? 'formato') . $sufijo,
            'portrait'
        );
    }

    public function inspeccion(?int $id = null): never
    {
        $data = null;
        if ($id !== null) {
            $data = $this->inspecciones->findWithItems($id);
            if ($data === null) {
                http_response_code(404);
                exit('Inspección no encontrada.');
            }
        }
        $this->stream('pdf.inspeccion', [
            'inspeccion' => $data,
            'items' => InspeccionRepository::INSPECCION_ITEMS,
            'luces_tablero' => InspeccionRepository::LUCES_TABLERO,
        ], 'inspeccion_' . ($data['numero_economico'] ?? 'formato'), 'portrait');
    }

    public function mantenimiento(?int $id = null): never
    {
        $data = $id !== null ? $this->mantenimientos->findById($id) : null;
        if ($id !== null && $data === null) {
            http_response_code(404);
            exit('Mantenimiento no encontrado.');
        }
        $this->stream('pdf.mantenimiento', ['mantenimiento' => $data], 'mantenimiento_' . ($data['folio'] ?? 'formato'), 'portrait');
    }

    public function danio(?int $id = null): never
    {
        $data = $id !== null ? $this->danios->findById($id) : null;
        if ($id !== null && $data === null) {
            http_response_code(404);
            exit('Daño no encontrado.');
        }
        $fotos = $data !== null ? $this->danios->getFotos((int) $data['id']) : [];
        $seguimiento = $data !== null ? $this->danios->getSeguimiento((int) $data['id']) : [];
        $this->stream(
            'pdf.danio',
            ['danio' => $data, 'fotos' => $fotos, 'seguimiento' => $seguimiento],
            'danio_' . ($data['id'] ?? 'formato'),
            'portrait'
        );
    }

    public function combustible(?int $id = null): never
    {
        $data = $id !== null ? $this->combustible->findById($id) : null;
        if ($id !== null && $data === null) {
            http_response_code(404);
            exit('Registro de combustible no encontrado.');
        }
        $this->stream('pdf.combustible', ['carga' => $data], 'combustible_' . ($data['id'] ?? 'formato'), 'portrait');
    }

    private function stream(string $view, array $data, string $filename, string $orientation = 'portrait'): never
    {
        $html = View::render($view, $data, null);
        $path = storage_path('exports/' . $filename . '_' . date('Ymd_His') . '.pdf');
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        file_put_contents($path, $dompdf->output());

        AuditService::log('EXPORT', 'formatos_pdf', null, null, [
            'vista' => $view,
            'archivo' => basename($path),
        ]);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($path) . '"');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        readfile($path);
        exit;
    }
}
