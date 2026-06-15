<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

$pdo = App\Core\Database::getInstance()->pdo();
echo 'Vehículos: ' . $pdo->query('SELECT COUNT(*) FROM vehiculos')->fetchColumn() . PHP_EOL;
$hash = $pdo->query("SELECT password_hash FROM users WHERE email='admin@cecytebcs.edu.mx'")->fetchColumn();
echo password_verify('Admin123!', $hash) ? "Login OK\n" : "Login FAIL\n";
