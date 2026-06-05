<?php
/**
 * Manejo de errores según entorno.
 * Producción: registra en log del servidor, no imprime avisos/deprecations en pantalla.
 * Desarrollo (APP_ENV=local|development): muestra errores completos.
 */
$appEnv = strtolower(trim((string)(getenv('APP_ENV') ?: 'production')));
$isDev = in_array($appEnv, ['local', 'development', 'dev'], true);

error_reporting(E_ALL);
ini_set('log_errors', '1');

if ($isDev) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    return;
}

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    error_log(sprintf('PHP [%d] %s in %s:%d', $severity, $message, $file, $line));
    return true;
});

set_exception_handler(static function (Throwable $e): void {
    error_log('Uncaught ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ConectaBarrio</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;">';
    echo '<h1>No pudimos completar la operación</h1>';
    echo '<p>Ocurrió un error en el servidor. Intente nuevamente en unos minutos.</p>';
    echo '<p><a href="/">Volver al inicio</a></p>';
    echo '</body></html>';
    exit;
});

register_shutdown_function(static function (): void {
    $error = error_get_last();
    if (!$error) {
        return;
    }
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($error['type'], $fatalTypes, true)) {
        return;
    }
    error_log(sprintf('Fatal [%d] %s in %s:%d', $error['type'], $error['message'], $error['file'], $error['line']));
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>ConectaBarrio</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;">';
    echo '<h1>No pudimos completar la operación</h1>';
    echo '<p>Ocurrió un error en el servidor. Intente nuevamente en unos minutos.</p>';
    echo '</body></html>';
});
