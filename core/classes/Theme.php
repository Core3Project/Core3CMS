<?php

defined('C3_ROOT') || exit;
class Theme {
    private static $active = 'default';
    private static $data = [];

    public static function init() {
        self::$active = Setting::get('theme', 'default');
    }

    public static function path() {
        return C3_ROOT . '/content/themes/' . self::$active;
    }

    public static function url() {
        return Router::url('content/themes/' . self::$active);
    }

    public static function render($template, $data = []) {
        self::$data = array_merge(self::$data, $data);
        extract(self::$data);
        $file = self::path() . '/templates/' . $template . '.php';
        if ( ! file_exists($file)) {
            $file = C3_ROOT . '/content/themes/default/templates/' . $template . '.php';
        }
        if (file_exists($file)) { include $file; }
        else { echo "<h1>Template not found: {$template}</h1>"; }
    }

    public static function partial($name, $extra = []) {
        $data = array_merge(self::$data, $extra);
        extract($data);
        $file = self::path() . '/templates/partials/' . $name . '.php';
        if ( ! file_exists($file)) {
            $file = C3_ROOT . '/content/themes/default/templates/partials/' . $name . '.php';
        }
        if (file_exists($file)) include $file;
    }

    public static function asset($file) {
        return self::url() . '/assets/' . $file;
    }

    public static function set($key, $value) {
        self::$data[$key] = $value;
    }

    public static function installed() {
        $themes = [];
        $dir = C3_ROOT . '/content/themes';
        foreach (glob($dir . '/*/theme.json') as $f) {
            $info = json_decode(file_get_contents($f), true);
            $info['slug'] = basename(dirname($f));
            $themes[] = $info;
        }
        return $themes;
    }

    public static function installZip($zipPath) {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) return 'Cannot open ZIP.';
        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (basename($zip->getNameIndex($i)) === 'theme.json') { $found = true; break; }
        }
        if ( ! $found) { $zip->close(); return 'No theme.json found.'; }
        $zip->extractTo(C3_ROOT . '/content/themes');
        $zip->close();
        return true;
    }
}
