<?php

declare(strict_types=1);

use App\Controllers\AlertaController;
use App\Controllers\AuditoriaController;
use App\Controllers\AuthController;
use App\Controllers\BusquedaController;
use App\Controllers\CombustibleController;
use App\Controllers\ComisionController;
use App\Controllers\DanioController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentoController;
use App\Controllers\HerramientaController;
use App\Controllers\InspeccionController;
use App\Controllers\MantenimientoController;
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
$router->get('vehiculos/{id}', [VehiculoController::class, 'show'], $perm('expediente.read'));
$router->get('vehiculos/{id}/edit', [VehiculoController::class, 'edit'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}', [VehiculoController::class, 'update'], $perm('vehiculos.update'));
$router->post('vehiculos/{id}/baja', [VehiculoController::class, 'destroy'], $perm('vehiculos.delete'));
$router->post('vehiculos/{id}/foto', [VehiculoController::class, 'uploadFoto'], $perm('vehiculos.update'));

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

// ——— Inspecciones ———
$router->get('inspecciones', [InspeccionController::class, 'index'], $perm('inspecciones.read'));
$router->get('inspecciones/create', [InspeccionController::class, 'create'], $perm('inspecciones.create'));
$router->post('inspecciones', [InspeccionController::class, 'store'], $perm('inspecciones.create'));
$router->get('inspecciones/{id}', [InspeccionController::class, 'show'], $perm('inspecciones.read'));

// ——— Daños ———
$router->get('danios', [DanioController::class, 'index'], $perm('danios.read'));
$router->get('danios/create', [DanioController::class, 'create'], $perm('danios.create'));
$router->post('danios', [DanioController::class, 'store'], $perm('danios.create'));
$router->get('danios/{id}', [DanioController::class, 'show'], $perm('danios.read'));
$router->post('danios/{id}/estado', [DanioController::class, 'updateEstado'], $perm('danios.update'));

// ——— Mantenimiento ———
$router->get('mantenimiento', [MantenimientoController::class, 'index'], $perm('mantenimiento.read'));
$router->get('mantenimiento/create', [MantenimientoController::class, 'create'], $perm('mantenimiento.create'));
$router->post('mantenimiento', [MantenimientoController::class, 'store'], $perm('mantenimiento.create'));
$router->get('mantenimiento/{id}', [MantenimientoController::class, 'show'], $perm('mantenimiento.read'));
$router->get('mantenimiento/{id}/edit', [MantenimientoController::class, 'edit'], $perm('mantenimiento.update'));
$router->post('mantenimiento/{id}', [MantenimientoController::class, 'update'], $perm('mantenimiento.update'));
$router->post('mantenimiento/{id}/autorizar', [MantenimientoController::class, 'autorizar'], $perm('mantenimiento.authorize'));
$router->post('mantenimiento/{id}/finalizar', [MantenimientoController::class, 'finalizar'], $perm('mantenimiento.update'));

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

// ——— Usuarios ———
$router->get('usuarios', [UsuarioController::class, 'index'], $perm('usuarios.read'));
$router->get('usuarios/create', [UsuarioController::class, 'create'], $perm('usuarios.create'));
$router->post('usuarios', [UsuarioController::class, 'store'], $perm('usuarios.create'));
$router->get('usuarios/{id}/edit', [UsuarioController::class, 'edit'], $perm('usuarios.update'));
$router->post('usuarios/{id}', [UsuarioController::class, 'update'], $perm('usuarios.update'));

// ——— Auditoría ———
$router->get('auditoria', [AuditoriaController::class, 'index'], $perm('auditoria.read'));
