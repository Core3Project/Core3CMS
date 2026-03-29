<?php

/**
 * Core 3 CMS bootstrap
 *
 * Loads configuration, classes, helpers, runs pending
 * database migrations, and boots active modules.
 */

define('C3_VERSION', '3.1.0');

if (session_status() === PHP_SESSION_NONE) {
    // fix misconfigured session save paths on some shared hosts
    $savePath = session_save_path();
    if ( ! $savePath || ! is_writable($savePath)) {
        $tmpPath = sys_get_temp_dir();
        if (is_writable($tmpPath)) {
            session_save_path($tmpPath);
        }
    }
    session_set_cookie_params(0, '/', '', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'), true);
    session_start();
}

// redirect to installer when no config exists
$configPath = C3_ROOT . '/core/config.php';

if ( ! file_exists($configPath)) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
    header("Location: {$base}/install/");
    exit;
}

require_once $configPath;

// autoload core classes and helper functions
foreach (glob(C3_ROOT . '/core/classes/*.php') as $file) {
    require_once $file;
}

foreach (glob(C3_ROOT . '/core/functions/*.php') as $file) {
    require_once $file;
}

// apply any outstanding database migrations
Migration::run();

// boot active modules
Modules::init();
