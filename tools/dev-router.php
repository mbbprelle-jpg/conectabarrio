<?php
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
$public = dirname(__DIR__) . '/public';
$file = $public . str_replace('/', DIRECTORY_SEPARATOR, $uri);
if ($uri !== '/' && is_file($file)) {
    return false;
}
$_GET['url'] = trim($uri, '/');
require $public . '/index.php';
