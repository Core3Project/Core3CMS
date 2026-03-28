<?php
/**
 * Core 3 CMS - Bootstrap
 *
 * Loads configuration, core classes, helper functions,
 * and initialises the module system.
 *
 * @package Core3
 */

define('C3_VERSION', '3.1.0');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$configPath = C3_ROOT . '/core/config.php';

if (!file_exists($configPath)) {
    $uri = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header("Location: {$uri}/install/");
    exit;
}

require_once $configPath;

foreach (glob(C3_ROOT . '/core/classes/*.php') as $classFile) {
    require_once $classFile;
}

foreach (glob(C3_ROOT . '/core/functions/*.php') as $funcFile) {
    require_once $funcFile;
}

Migration::run();
Modules::init();
