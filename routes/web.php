<?php

declare(strict_types=1);

use App\Controllers\AlertaController;
use App\Controllers\AreaController;
use App\Controllers\AuditoriaController;
use App\Controllers\AuthController;
use App\Controllers\BusquedaController;
use App\Controllers\CatalogoController;
use App\Controllers\CombustibleController;
use App\Controllers\ComisionController;
use App\Controllers\ConductorController;
use App\Controllers\DanioController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentoController;
use App\Controllers\FormularioController;
use App\Controllers\HerramientaController;
use App\Controllers\InspeccionController;
use App\Controllers\MantenimientoController;
use App\Controllers\PlantelController;
use App\Controllers\ProveedorController;
use App\Controllers\ReporteController;
use App\Controllers\UsuarioController;
use App\Controllers\VehiculoController;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\GuestMiddleware;
use App\Middlewares\PermissionMiddleware;

/** @var \App\Core\Router $router */

$auth = [AuthMiddleware::class];
$guest = [GuestMiddleware::class];
$perm = static fn (string $permission): array => [
    AuthMiddleware::class,
    new PermissionMiddleware($permission),
];

// ——— Autenticación ———
$router->get('login', [AuthController::class, 'loginForm'], $guest);
$router->post('login', [AuthController::class, 'login'], $guest);
$router->post('logout', [AuthController::class, 'logout'], $auth);
$router->get('forgot-password', [AuthController::class, 'forgotForm'], $guest);
$router->post('forgot-password', [AuthController::class, 'forgot'], $guest);
$router->get('reset-password/{token}', [AuthController::class, 'resetForm'], $guest);
$router->post('reset-password', [AuthController::class, 'reset'], $guest);
$router->get('change-password', [AuthController::class, 'changePasswordForm'], $auth);
$router->post('change-password', [AuthController::class, 'changePassword'], $auth);

// ——— Dashboard ———
$router->get('', [DashboardController::class, 'index'], $perm('dashboard.read'));
$router->get('dashboard', [DashboardController::class, 'index'], $perm('dashboard.read'));

// ——— Búsqueda global ———
$router->get('busqueda', [BusquedaController::class, 'index'], $auth);

// ——— Vehículos y expediente ———
$router->get('vehiculos', [VehiculoController::class, 'index'], $perm('vehiculos.read'));
$router->get('vehiculos/create', [VehiculoController::class, 'create'], $perm('vehiculos.create'));
$router->post('vehiculos', [VehiculoController::class, 'store'], $perm('vehiculos.create'));
$router->get('vehiculos/{id}/luces', [VehiculoController::class, 'apiLuces'], $auth);
$router->get('vehiculos/{id}', [VehiculoController::class, 'show'], $perm('expediente.read'));
$router->get('vehiculos/{id}/edit', [VehiculoController::class, 'edit'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}', [VehiculoController::class, 'update'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}/baja', [VehiculoController::class, 'destroy'], $perm('vehiculos.delete'));
$router->post('vehiculos/{id}/foto', [VehiculoController::class, 'uploadFoto'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}/foto/{fotoId}/principal', [VehiculoController::class, 'setFotoPrincipal'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}/foto/{fotoId}/delete', [VehiculoController::class, 'deleteFoto'], $perm('vehiculos.update'));

// ——— Comisiones ———
$router->get('comisiones', [ComisionController::class, 'index'], $perm('comisiones.read'));
$router->get('comisiones/create', [ComisionController::class, 'create'], $perm('comisiones.create'));
$router->post('comisiones', [ComisionController::class, 'store'], $perm('comisiones.create'));
$router->get('comisiones/{id}', [ComisionController::class, 'show'], $perm('comisiones.read'));
$router->get('comisiones/{id}/edit', [ComisionController::class, 'edit'], $perm('comisiones.update'));
$router->post('comisiones/{id}', [ComisionController::class, 'update'], $perm('comisiones.update'));
$router->post('comisiones/{id}/iniciar', [ComisionController::class, 'iniciar'], $perm('comisiones.update'));
$router->post('comisiones/{id}/finalizar', [ComisionController::class, 'finalizar'], $perm('comisiones.update'));
$router->post('comisiones/{id}/cancelar', [ComisionController::class, 'cancelar'], $perm('comisiones.delete'));
$router->post('comisiones/{id}/eliminar', [ComisionController::class, 'eliminar'], $perm('comisiones.delete'));
$router->post('comisiones/{id}/documento', [ComisionController::class, 'cargarDocumento'], $perm('comisiones.update'));
$router->get('comisiones/{id}/documentos/combinado', [ComisionController::class, 'documentosCombinados'], $perm('comisiones.read'));

// ——— Inspecciones ———
$router->get('inspecciones', [InspeccionController::class, 'index'], $perm('inspecciones.read'));
$router->get('inspecciones/create', [InspeccionController::class, 'create'], $perm('inspecciones.create'));
$router->post('inspecciones', [InspeccionController::class, 'store'], $perm('inspecciones.create'));
$router->get('inspecciones/{id}', [InspeccionController::class, 'show'], $perm('inspecciones.read'));
$router->post('inspecciones/{id}/eliminar', [InspeccionController::class, 'eliminar'], $perm('inspecciones.delete'));

// ——— Daños ———
$router->get('danios', [DanioController::class, 'index'], $perm('danios.read'));
$router->get('danios/create', [DanioController::class, 'create'], $perm('danios.create'));
$router->post('danios', [DanioController::class, 'store'], $perm('danios.create'));
$router->get('danios/{id}', [DanioController::class, 'show'], $perm('danios.read'));
$router->post('danios/{id}/estado', [DanioController::class, 'updateEstado'], $perm('danios.update'));
$router->post('danios/{id}/fotos', [DanioController::class, 'uploadFotos'], $perm('danios.update'));
$router->post('danios/{id}/fotos/{fotoId}/delete', [DanioController::class, 'deleteFoto'], $perm('danios.update'));

// ——— Mantenimiento ———
$router->get('mantenimiento', [MantenimientoController::class, 'index'], $perm('mantenimiento.read'));
$router->get('mantenimiento/create', [MantenimientoController::class, 'create'], $perm('mantenimiento.create'));
$router->post('mantenimiento/servicios', [MantenimientoController::class, 'storeServicio'], $perm('mantenimiento.create'));
$router->post('mantenimiento', [MantenimientoController::class, 'store'], $perm('mantenimiento.create'));
$router->get('mantenimiento/{id}', [MantenimientoController::class, 'show'], $perm('mantenimiento.read'));
$router->get('mantenimiento/{id}/edit', [MantenimientoController::class, 'edit'], $perm('mantenimiento.update'));
$router->post('mantenimiento/{id}', [MantenimientoController::class, 'update'], $perm('mantenimiento.update'));
$router->post('mantenimiento/{id}/autorizar', [MantenimientoController::class, 'autorizar'], $perm('mantenimiento.authorize'));
$router->post('mantenimiento/{id}/finalizar', [MantenimientoController::class, 'finalizar'], $perm('mantenimiento.update'));
$router->post('mantenimiento/{id}/eliminar', [MantenimientoController::class, 'eliminar'], $perm('mantenimiento.delete'));

// ——— Proveedores ———
$router->get('proveedores', [ProveedorController::class, 'index'], $perm('proveedores.read'));
$router->get('proveedores/create', [ProveedorController::class, 'create'], $perm('proveedores.create'));
$router->post('proveedores', [ProveedorController::class, 'store'], $perm('proveedores.create'));
$router->post('proveedores/quick', [ProveedorController::class, 'quickStore'], $perm('proveedores.create'));
$router->get('proveedores/{id}/edit', [ProveedorController::class, 'edit'], $perm('proveedores.update'));
$router->post('proveedores/{id}', [ProveedorController::class, 'update'], $perm('proveedores.update'));
$router->post('proveedores/{id}/toggle', [ProveedorController::class, 'toggle'], $perm('proveedores.update'));

// ——— Combustible ———
$router->get('combustible', [CombustibleController::class, 'index'], $perm('combustible.read'));
$router->get('combustible/create', [CombustibleController::class, 'create'], $perm('combustible.create'));
$router->post('combustible', [CombustibleController::class, 'store'], $perm('combustible.create'));

// ——— Documentos ———
$router->get('documentos', [DocumentoController::class, 'index'], $perm('documentos.read'));
$router->get('documentos/create', [DocumentoController::class, 'create'], $perm('documentos.create'));
$router->post('documentos', [DocumentoController::class, 'store'], $perm('documentos.create'));
$router->get('documentos/{id}/download', [DocumentoController::class, 'download'], $perm('documentos.read'));

// ——— Herramientas (por vehículo) ———
$router->get('herramientas/vehiculo/{vehiculoId}', [HerramientaController::class, 'index'], $perm('herramientas.read'));
$router->post('herramientas/vehiculo/{vehiculoId}', [HerramientaController::class, 'update'], $perm('herramientas.update'));

// ——— Alertas ———
$router->get('alertas', [AlertaController::class, 'index'], $perm('alertas.read'));
$router->post('alertas/{id}/atender', [AlertaController::class, 'atender'], $perm('alertas.read'));
$router->get('alertas/config', [AlertaController::class, 'config'], $perm('alertas.config'));
$router->post('alertas/config', [AlertaController::class, 'config'], $perm('alertas.config'));

// ——— Reportes ———
$router->get('reportes', [ReporteController::class, 'index'], $perm('dashboard.read'));
$router->get('reportes/export/{tipo}', [ReporteController::class, 'export'], $perm('reportes.export'));

// ——— Formatos PDF imprimibles (con espacios para firma) ———
$router->get('formatos/comision', [FormularioController::class, 'comision'], $perm('comisiones.read'));
$router->get('formatos/comision/{id}', [FormularioController::class, 'comision'], $perm('comisiones.read'));
$router->get('formatos/inspeccion', [FormularioController::class, 'inspeccion'], $perm('inspecciones.read'));
$router->get('formatos/inspeccion/{id}', [FormularioController::class, 'inspeccion'], $perm('inspecciones.read'));
$router->get('formatos/mantenimiento', [FormularioController::class, 'mantenimiento'], $perm('mantenimiento.read'));
$router->get('formatos/mantenimiento/{id}', [FormularioController::class, 'mantenimiento'], $perm('mantenimiento.read'));
$router->get('formatos/danio', [FormularioController::class, 'danio'], $perm('danios.read'));
$router->get('formatos/danio/{id}', [FormularioController::class, 'danio'], $perm('danios.read'));
$router->get('formatos/combustible', [FormularioController::class, 'combustible'], $perm('combustible.read'));
$router->get('formatos/combustible/{id}', [FormularioController::class, 'combustible'], $perm('combustible.read'));

// ——— Catálogos (áreas, conductores, planteles) ———
$router->get('catalogos', [CatalogoController::class, 'index'], $perm('catalogos.read'));
$router->get('catalogos/api/planteles', [CatalogoController::class, 'apiPlanteles'], $auth);
$router->get('catalogos/api/areas', [CatalogoController::class, 'apiAreas'], $auth);
$router->get('catalogos/api/conductores', [CatalogoController::class, 'apiConductores'], $auth);
$router->get('catalogos/api/proveedores', [CatalogoController::class, 'apiProveedores'], $auth);
$router->get('catalogos/api/responsables', [CatalogoController::class, 'apiResponsables'], $auth);
$router->get('catalogos/planteles', [PlantelController::class, 'index'], $perm('catalogos.read'));
$router->get('catalogos/planteles/create', [PlantelController::class, 'create'], $perm('catalogos.create'));
$router->post('catalogos/planteles', [PlantelController::class, 'store'], $perm('catalogos.create'));
$router->post('catalogos/planteles/quick', [PlantelController::class, 'quickStore'], $perm('catalogos.create'));
$router->get('catalogos/planteles/{id}/edit', [PlantelController::class, 'edit'], $perm('catalogos.update'));
$router->post('catalogos/planteles/{id}', [PlantelController::class, 'update'], $perm('catalogos.update'));
$router->post('catalogos/planteles/{id}/toggle', [PlantelController::class, 'toggle'], $perm('catalogos.update'));
$router->get('catalogos/areas', [AreaController::class, 'index'], $perm('catalogos.read'));
$router->get('catalogos/areas/create', [AreaController::class, 'create'], $perm('catalogos.create'));
$router->post('catalogos/areas', [AreaController::class, 'store'], $perm('catalogos.create'));
$router->post('catalogos/areas/quick', [AreaController::class, 'quickStore'], $perm('catalogos.create'));
$router->get('catalogos/areas/{id}/edit', [AreaController::class, 'edit'], $perm('catalogos.update'));
$router->post('catalogos/areas/{id}', [AreaController::class, 'update'], $perm('catalogos.update'));
$router->post('catalogos/areas/{id}/toggle', [AreaController::class, 'toggle'], $perm('catalogos.update'));
$router->get('catalogos/conductores', [ConductorController::class, 'index'], $perm('catalogos.read'));
$router->get('catalogos/conductores/create', [ConductorController::class, 'create'], $perm('catalogos.create'));
$router->post('catalogos/conductores', [ConductorController::class, 'store'], $perm('catalogos.create'));
$router->post('catalogos/conductores/quick', [ConductorController::class, 'quickStore'], $perm('catalogos.create'));
$router->get('catalogos/conductores/{id}/edit', [ConductorController::class, 'edit'], $perm('catalogos.update'));
$router->post('catalogos/conductores/{id}', [ConductorController::class, 'update'], $perm('catalogos.update'));
$router->post('catalogos/conductores/{id}/toggle', [ConductorController::class, 'toggle'], $perm('catalogos.update'));

// ——— Usuarios ———
$router->get('usuarios', [UsuarioController::class, 'index'], $perm('usuarios.read'));
$router->get('usuarios/create', [UsuarioController::class, 'create'], $perm('usuarios.create'));
$router->post('usuarios', [UsuarioController::class, 'store'], $perm('usuarios.create'));
$router->post('usuarios/quick', [UsuarioController::class, 'quickStore'], $auth);
$router->get('usuarios/{id}/edit', [UsuarioController::class, 'edit'], $perm('usuarios.update'));
$router->post('usuarios/{id}', [UsuarioController::class, 'update'], $perm('usuarios.update'));

// ——— Auditoría ———
$router->get('auditoria', [AuditoriaController::class, 'index'], $perm('auditoria.read'));
