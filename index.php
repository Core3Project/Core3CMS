<?php
/**
 * Core 3 CMS - Front Controller
 * All public requests route through this file.
 */
define('C3_ROOT', __DIR__);
define('C3_START', microtime(true));

require_once C3_ROOT . '/core/bootstrap.php';
Theme::init();

$router = new Router();
$router->dispatch();
