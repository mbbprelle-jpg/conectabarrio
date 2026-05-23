<?php
// Asegurar integridad de codificación de caracteres en todas las respuestas
header('Content-Type: text/html; charset=UTF-8');

// Configuración de la base de datos
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'conectabarrio');

// Ruta de la aplicación (Directorio interno)
define('APPROOT', dirname(dirname(__FILE__)));

// URL de la aplicación (Auto-detecta automáticamente si está en local XAMPP o producción)
$resolvedUrl = '';
if (strpos($_SERVER['REQUEST_URI'] ?? '', '/CONECTABARRIO') === 0 || strpos($_SERVER['SCRIPT_NAME'] ?? '', '/CONECTABARRIO') === 0) {
    $resolvedUrl = '/CONECTABARRIO';
}
define('URLROOT', $resolvedUrl);

// Nombre del sitio
define('SITENAME', 'CONECTABARRIO');

// Configuración de Seguridad de Sesiones
ini_set('session.cookie_httponly', 1);
if ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
    ini_set('session.cookie_secure', 1);
}
// Establecer tiempo de vida máximo de sesión
ini_set('session.gc_maxlifetime', 3600);

// Iniciar sesión globalmente si no se ha iniciado
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
