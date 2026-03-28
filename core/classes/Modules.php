<?php

defined('C3_ROOT') || exit;

/**
 * Module loader and hook system
 *
 * Modules register callbacks against named hooks. Core and theme
 * code fires those hooks at key extension points, allowing modules
 * to inject content, modify data, or register routes without
 * touching any core files.
 *
 * @see HOOKS.md for the full developer reference
 */
class Modules
{
    /**
     * Registered hook callbacks grouped by hook name
     *
     * @var array
     */
    private static $hooks = [];

    /**
     * Metadata for modules that were successfully booted
     *
     * @var array
     */
    private static $loaded = [];

    /**
     * Scan the modules directory and boot every active module
     *
     * @return void
     */
    public static function init()
    {
        $dir = C3_ROOT . '/content/modules';

        if ( !  is_dir($dir)) {
            return;
        }

        foreach (glob($dir . '/*/module.json') as $jsonFile) {
            $info    = json_decode(file_get_contents($jsonFile), true);
            $slug    = basename(dirname($jsonFile));
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
     * Register a callback for a named hook
     *
     * @param string   $hook
     * @param callable $callback
     * @param int      $priority lower numbers run first (default 10)
     *
     * @return void
     */
    public static function on($hook, $callback, $priority = 10)
    {
        self::$hooks[$hook][] = [
            'fn'       => $callback,
            'priority' => $priority,
        ];
    }

    /**
     * Remove a previously registered callback
     *
     * @param string   $hook
     * @param callable $callback
     *
     * @return void
     */
    public static function off($hook, $callback)
    {
        if (empty(self::$hooks[$hook])) {
            return;
        }

        self::$hooks[$hook] = array_filter(
            self::$hooks[$hook],
            function ($entry) use ($callback) {
                return $entry['fn'] !== $callback;
            }
        );
    }

    /**
     * Check whether any callbacks exist for a hook
     *
     * @param string $hook
     *
     * @return bool
     */
    public static function has($hook)
    {
        return ! empty(self::$hooks[$hook]);
    }

    /**
     * Fire a data hook
     *
     * Callbacks receive $data by reference and may modify it.
     * Additional arguments are forwarded after $data.
     *
     * @param string $hook
     * @param mixed  &$data
     *
     * @return void
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
            $result = call_user_func_array(
                $entry['fn'],
                array_merge([&$data], $extra)
            );

            if ($result !== null) {
                $data = $result;
            }
        }
    }

    /**
     * Fire an output hook
     *
     * Each callback returns an HTML string. All strings are
     * concatenated and returned.
     *
     * @param string $hook
     *
     * @return string
     */
    public static function html($hook)
    {
        $args   = array_slice(func_get_args(), 1);
        $output = '';

        if (empty(self::$hooks[$hook])) {
            return '';
        }

        usort(self::$hooks[$hook], function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        foreach (self::$hooks[$hook] as $entry) {
            $result = call_user_func_array($entry['fn'], $args);

            if ($result !== null) {
                $output .= $result;
            }
        }

        return $output;
    }

    /**
     * Check if a specific module is active
     *
     * @param string $slug
     *
     * @return bool
     */
    public static function active($slug)
    {
        return isset(self::$loaded[$slug]);
    }

    /**
     * Return metadata for all booted modules
     *
     * @return array
     */
    public static function loaded()
    {
        return self::$loaded;
    }

    /**
     * Return metadata for every installed module (active or not)
     *
     * @return array
     */
    public static function all()
    {
        $modules = [];
        $dir     = C3_ROOT . '/content/modules';

        if ( !  is_dir($dir)) {
            return [];
        }

        foreach (glob($dir . '/*/module.json') as $jsonFile) {
            $info    = json_decode(file_get_contents($jsonFile), true);
            $slug    = basename(dirname($jsonFile));
            $default = isset($info['default_enabled']) ? $info['default_enabled'] : '0';

            $info['slug']   = $slug;
            $info['active'] = Setting::get("module_{$slug}", $default) === '1';

            $modules[] = $info;
        }

        return $modules;
    }

    /**
     * Extract and install a module from a ZIP archive
     *
     * @param string $zipPath path to the uploaded file
     *
     * @return true|string true on success, error message on failure
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

        if ( !  $found) {
            $zip->close();
            return 'No module.json found in archive.';
        }

        $zip->extractTo(C3_ROOT . '/content/modules');
        $zip->close();

        return true;
    }
}
