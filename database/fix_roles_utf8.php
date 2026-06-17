<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$descripciones = [
    'admin_general' => 'Acceso completo al sistema. Puede crear, editar y eliminar usuarios; consultar la auditoría de cambios; y gestionar todos los módulos: vehículos, comisiones, inspecciones, daños, mantenimiento, combustible, proveedores, herramientas, documentos, alertas y reportes. Rol de más alto nivel, destinado a dirección general o responsable de TI.',
    'admin_transporte' => 'Gestión operativa integral del parque vehicular. Puede administrar vehículos, comisiones, inspecciones, daños, mantenimiento, combustible, proveedores, herramientas y documentación; configurar alertas y exportar reportes. No puede crear, modificar ni eliminar usuarios del sistema ni consultar la auditoría de cuentas.',
    'supervisor' => 'Supervisa y autoriza la operación del parque. Puede consultar y actualizar información en la mayoría de módulos, autorizar comisiones y mantenimientos, y exportar reportes. No crea ni elimina registros operativos ni gestiona usuarios. Ideal para jefes de área o coordinadores de transporte.',
    'responsable_vehiculo' => 'Operación diaria de las unidades asignadas. Puede registrar comisiones, inspecciones, reportes de daños y cargas de combustible; actualizar el estado de sus vehículos y herramientas; y consultar expedientes, alertas y reportes de sus unidades. No autoriza comisiones ni mantenimientos ni accede a la configuración del sistema.',
    'consulta' => 'Acceso de solo lectura. Puede consultar vehículos, expedientes, comisiones, inspecciones, mantenimiento, combustible y alertas; ver el panel principal y exportar reportes informativos. No puede crear, editar ni eliminar ningún registro. Pensado para personal administrativo o consulta externa.',
];

$pdo = App\Core\Database::getInstance()->pdo();
$stmt = $pdo->prepare('UPDATE roles SET descripcion = ? WHERE slug = ?');

foreach ($descripciones as $slug => $texto) {
    $stmt->execute([$texto, $slug]);
    echo "OK: {$slug}\n";
}

$row = $pdo->query("SELECT descripcion FROM roles WHERE slug = 'supervisor'")->fetchColumn();
echo "\nVerificación: " . $row . "\n";
