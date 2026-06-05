<?php
require_once __DIR__ . '/../app/bootstrap/errors.php';

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

// Cargar la configuración global
require_once __DIR__ . '/../app/config/config.php';

// Autocargar las clases core de la aplicación
spl_autoload_register(function($className) {
    if (file_exists(__DIR__ . '/../app/core/' . $className . '.php')) {
        require_once __DIR__ . '/../app/core/' . $className . '.php';
    }
});

// Inicializar el Enrutador principal
$init = new Router();
