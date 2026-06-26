<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$etiquetas = permiso_etiquetas();

$pdo = App\Core\Database::getInstance()->pdo();
$stmt = $pdo->prepare('UPDATE permissions SET descripcion = ? WHERE slug = ?');

foreach ($etiquetas as $slug => $texto) {
    $stmt->execute([$texto, $slug]);
    echo "OK: {$slug}\n";
}

$row = $pdo->query("SELECT descripcion FROM permissions WHERE slug = 'catalogos.read'")->fetchColumn();
echo "\nVerificación catálogos: {$row}\n";
