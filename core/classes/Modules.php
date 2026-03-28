<?php
/**
 * Module loader and hook system.
 *
 * Modules register callbacks against named hooks. Core and theme
 * code fires those hooks at key points, allowing modules to inject
 * content, modify data, or register new routes without editing
 * core files.
 *
 * @package Core3
 */
class Modules
{
    private static $hooks  = [];
    private static $loaded = [];

    /**
     * Scan the modules directory and boot any active modules.
     */
    public static function init()
    {
        $dir = C3_ROOT . '/content/modules';

        if (!is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*/module.json') as $jsonFile) {
            $info = json_decode(file_get_contents($jsonFile), true);
            $slug = basename(dirname($jsonFile));
            $default = isset($info['default_enabled']) ? $info['default_enabled'] : '0';

            if (Setting::get("module_{$slug}", $default) !== '1') {
                continue;
            }

            $bootFile = dirname($jsonFile) . '/boot.php';

            if (file_exists($bootFile)) {
                require_once $bootFile;
                self::$loaded[$slug] = $info;
            }
        }
    }

    /**
     * Register a callback for a hook.
     *
     * Lower priority numbers run first (default: 10).
     */
    public static function on($hook, $callback, $priority = 10)
    {
        self::$hooks[$hook][] = [
            'fn'       => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Remove a previously registered callback from a hook.
     */
    public static function off($hook, $callback)
    {
        if (empty(self::$hooks[$hook])) {
            return;
        }

        self::$hooks[$hook] = array_filter(self::$hooks[$hook], function ($entry) use ($callback) {
            return $entry['fn'] !== $callback;
        });
    }

    /**
     * Check whether any callbacks are registered for a hook.
     */
    public static function has($hook)
    {
        return !empty(self::$hooks[$hook]);
    }

    /**
     * Fire a data hook — callbacks receive a reference and can modify it.
     *
     * Used for hooks like 'routes' where modules add to an array.
     */
    public static function hook($hook, &$data = null)
    {
        $extra = array_slice(func_get_args(), 2);

        if (empty(self::$hooks[$hook])) {
            return;
        }

        usort(self::$hooks[$hook], function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach (self::$hooks[$hook] as $entry) {
            $result = call_user_func_array($entry['fn'], array_merge([&$data], $extra));

            if ($result !== null) {
                $data = $result;
            }
        }
    }

    /**
     * Fire an output hook — callbacks return HTML strings that are
     * concatenated together.
     *
     * Used for hooks like 'head', 'footer', 'post_content_after'.
     */
    public static function html($hook)
    {
        $args = array_slice(func_get_args(), 1);
        $out  = '';

        if (empty(self::$hooks[$hook])) {
            return '';
        }

        usort(self::$hooks[$hook], function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach (self::$hooks[$hook] as $entry) {
            $result = call_user_func_array($entry['fn'], $args);
            if ($result !== null) {
                $out .= $result;
            }
        }

        return $out;
    }

    /**
     * Check if a module is currently active.
     */
    public static function active($slug)
    {
        return isset(self::$loaded[$slug]);
    }

    /**
     * Get all loaded modules and their metadata.
     */
    public static function loaded()
    {
        return self::$loaded;
    }

    /**
     * Get metadata for all installed modules (active or not).
     */
    public static function all()
    {
        $modules = [];
        $dir = C3_ROOT . '/content/modules';

        if (!is_dir($dir)) {
            return [];
        }

        foreach (glob($dir . '/*/module.json') as $jsonFile) {
            $info = json_decode(file_get_contents($jsonFile), true);
            $slug = basename(dirname($jsonFile));
            $default = isset($info['default_enabled']) ? $info['default_enabled'] : '0';

            $info['slug']   = $slug;
            $info['active'] = Setting::get("module_{$slug}", $default) === '1';
            $modules[] = $info;
        }

        return $modules;
    }

    /**
     * Install a module from a ZIP archive.
     *
     * Returns true on success or an error string.
     */
    public static function installZip($zipPath)
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            return 'Cannot open ZIP archive.';
        }

        $found = false;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (basename($zip->getNameIndex($i)) === 'module.json') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $zip->close();
            return 'No module.json found in archive.';
        }

        $zip->extractTo(C3_ROOT . '/content/modules');
        $zip->close();

        return true;
    }
}
