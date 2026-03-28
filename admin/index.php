<?php
define('C3_ROOT', dirname(__DIR__));
define('C3_ADMIN', true);
require_once C3_ROOT . '/core/bootstrap.php';
Theme::init();

$requestUri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$path = $requestUri;
if ($scriptDir !== '/' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
$path = trim($path, '/');
$path = preg_replace('#^admin/#', '', $path);
$path = preg_replace('#^admin$#', '', $path);
if ($path === '' || $path === false) $path = 'dashboard';

$public = ['login', 'forgot-password', 'reset-password', 'logout'];
if (!in_array($path, $public)) {
    if (!Auth::check()) {
        header('Location: ' . $scriptDir . '/login');
        exit;
    }
}

$file = __DIR__ . '/pages/' . $path . '.php';
if (!preg_match('/^[a-z0-9\-]+$/', $path) || !file_exists($file)) {
    http_response_code(404);
    echo '<!DOCTYPE html><html><body><h1>Not found</h1><p><a href="' . $scriptDir . '/">Dashboard</a></p></body></html>';
    exit;
}

$_adminPage = $path;
$_flash = getFlash();
include $file;
