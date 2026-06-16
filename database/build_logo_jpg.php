<?php
define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/helpers/image.php';

$png = BASE_PATH . '/public/assets/images/logo_Cecyte_vertical_sin_fondo.png';
$jpg = BASE_PATH . '/public/assets/images/logo_Cecyte_vertical_sin_fondo.jpg';

if (!is_file($png)) {
    fwrite(STDERR, "No se encontró el logo PNG\n");
    exit(1);
}

if (!image_save_as_jpeg($png, $jpg)) {
    fwrite(STDERR, "No se pudo generar el JPEG del logo\n");
    exit(1);
}

$size = getimagesize($jpg);
echo 'OK: ' . ($size[0] ?? '?') . 'x' . ($size[1] ?? '?') . PHP_EOL;
