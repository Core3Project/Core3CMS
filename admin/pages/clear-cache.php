<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');

if (!Auth::checkCsrf($_GET['t'] ?? '')) {
    flash('error', 'Invalid request.');
    header('Location: ' . adm()); exit;
}

$cleared = [];

// 1. Clear PHP OPcache (bytecode cache)
if (function_exists('opcache_reset')) {
    opcache_reset();
    $cleared[] = 'PHP OPcache';
}

// 2. Bump the asset version to bust browser CSS/JS cache
// Store a cache version number in settings - theme/admin CSS will append ?v=X
$version = (int) Setting::get('cache_version', '0') + 1;
Setting::set('cache_version', (string) $version);
$cleared[] = 'Browser cache (v' . $version . ')';

// 3. Clear any file-based cache (if modules create cache files)
$cacheDir = C3_ROOT . '/content/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $count = 0;
    foreach ($files as $f) {
        if (is_file($f) && basename($f) !== '.htaccess' && basename($f) !== 'index.php') {
            unlink($f);
            $count++;
        }
    }
    if ($count) $cleared[] = $count . ' cache files';
}

// 4. Clear the in-memory settings cache for this request
// (Setting class uses a static cache that resets on next page load anyway)
$cleared[] = 'Settings cache';

if ($cleared) {
    flash('success', 'Cache cleared: ' . implode(', ', $cleared) . '.');
} else {
    flash('info', 'No caches to clear.');
}

// Redirect back to referring page or dashboard
$ref = $_SERVER['HTTP_REFERER'] ?? '';
$adminBase = dirname($_SERVER['SCRIPT_NAME']);
if ($ref && strpos($ref, $adminBase) !== false) {
    header('Location: ' . $ref);
} else {
    header('Location: ' . adm());
}
exit;
