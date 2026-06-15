<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Services\FormularioPdfService;

final class FormularioController extends BaseController
{
    public function __construct(
        private readonly FormularioPdfService $formatos = new FormularioPdfService()
    ) {
    }

    public function comision(Request $request, ?string $id = null): never
    {
        $this->formatos->comision($id !== null && $id !== '' ? (int) $id : null);
    }

    public function inspeccion(Request $request, ?string $id = null): never
    {
        $this->formatos->inspeccion($id !== null && $id !== '' ? (int) $id : null);
    }

    public function mantenimiento(Request $request, ?string $id = null): never
    {
        $this->formatos->mantenimiento($id !== null && $id !== '' ? (int) $id : null);
    }

    public function danio(Request $request, ?string $id = null): never
    {
        $this->formatos->danio($id !== null && $id !== '' ? (int) $id : null);
    }

    public function combustible(Request $request, ?string $id = null): never
    {
        $this->formatos->combustible($id !== null && $id !== '' ? (int) $id : null);
    }
}
