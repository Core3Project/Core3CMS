<?php
/**
 * Theme engine.
 *
 * Loads templates and assets from the active theme, falling back
 * to the 'default' theme for any missing files. This allows child
 * themes to override only what they need.
 *
 * @package Core3
 */
class Theme
{
    private static $active = 'default';
    private static $data   = [];

    /**
     * Read the active theme slug from the database.
     *
     * Must be called before any rendering. The front controller
     * and admin router both call this during bootstrap.
     */
    public static function init()
    {
        self::$active = Setting::get('theme', 'default');
    }

    /**
     * Filesystem path to the active theme directory.
     */
    public static function path()
    {
        return C3_ROOT . '/content/themes/' . self::$active;
    }

    /**
     * Public URL to the active theme directory.
     */
    public static function url()
    {
        return Router::url('content/themes/' . self::$active);
    }

    /**
     * Render a full-page template.
     *
     * Looks in the active theme first; falls back to default.
     * Data passed here is available as local variables inside
     * the template via extract().
     */
    public static function render($template, $data = [])
    {
        self::$data = array_merge(self::$data, $data);
        extract(self::$data);

        $file = self::resolve('templates/' . $template . '.php');

        if ($file) {
            include $file;
        } else {
            echo "<h1>Template not found: {$template}</h1>";
        }
    }

    /**
     * Render a partial template (header, footer, sidebar, etc.).
     *
     * Partials share the same data scope as the parent template.
     */
    public static function partial($name, $extra = [])
    {
        $data = array_merge(self::$data, $extra);
        extract($data);

        $file = self::resolve('templates/partials/' . $name . '.php');

        if ($file) {
            include $file;
        }
    }

    /**
     * Return the public URL to a theme asset file.
     */
    public static function asset($file)
    {
        return self::url() . '/assets/' . $file;
    }

    /**
     * Store a value in the template data scope.
     */
    public static function set($key, $value)
    {
        self::$data[$key] = $value;
    }

    /**
     * Resolve a relative path within the active theme,
     * falling back to the default theme.
     *
     * Returns the absolute filesystem path or null.
     */
    private static function resolve($relative)
    {
        $primary = self::path() . '/' . $relative;

        if (file_exists($primary)) {
            return $primary;
        }

        $fallback = C3_ROOT . '/content/themes/default/' . $relative;

        if (file_exists($fallback)) {
            return $fallback;
        }

        return null;
    }

    /**
     * List all installed themes with their metadata.
     */
    public static function installed()
    {
        $themes = [];
        $dir = C3_ROOT . '/content/themes';

        foreach (glob($dir . '/*/theme.json') as $jsonFile) {
            $info = json_decode(file_get_contents($jsonFile), true);
            $info['slug'] = basename(dirname($jsonFile));
            $themes[] = $info;
        }

        return $themes;
    }

    /**
     * Install a theme from a ZIP archive.
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
            if (basename($zip->getNameIndex($i)) === 'theme.json') {
                $found = true;
                break;
            }
        }

        if (!$found) {
            $zip->close();
            return 'No theme.json found in archive.';
        }

        $zip->extractTo(C3_ROOT . '/content/themes');
        $zip->close();

        return true;
    }
}
