<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__, 2));

require BASE_PATH . '/vendor/autoload.php';

$dotenv = BASE_PATH . '/.env';
if (is_file($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (str_starts_with(trim($line), '#')) {
            continue;
        }
        [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
}

date_default_timezone_set('America/Mazatlan');

use App\Services\AlertaService;

echo "Ejecutando cron de alertas SICV — " . date('Y-m-d H:i:s') . PHP_EOL;

try {
    $service = new AlertaService();
    $result = $service->runDailyCron();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    echo "Cron completado." . PHP_EOL;
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
