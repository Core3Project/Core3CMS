<?php
/**
 * Key-value settings stored in the database.
 *
 * Uses a static cache so each key is only read from the
 * database once per request.
 *
 * @package Core3
 */
class Setting
{
    private static $cache = [];
    private static $loaded = false;

    /**
     * Preload all settings into memory.
     */
    private static function load()
    {
        if (self::$loaded) {
            return;
        }

        try {
            $rows = DB::rows('SELECT `key`, `value` FROM ' . DB::t('settings'));
            foreach ($rows as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Table may not exist during installation.
        }

        self::$loaded = true;
    }

    /**
     * Retrieve a setting value.
     */
    public static function get($key, $default = '')
    {
        self::load();

        if (isset(self::$cache[$key]) && self::$cache[$key] !== null) {
            return self::$cache[$key];
        }

        return $default;
    }

    /**
     * Create or update a setting.
     */
    public static function set($key, $value)
    {
        self::load();

        $table = DB::t('settings');
        $existing = DB::row(
            "SELECT id FROM {$table} WHERE `key` = ?",
            [$key]
        );

        if ($existing) {
            DB::update($table, ['value' => $value], '`key` = ?', [$key]);
        } else {
            DB::insert($table, ['key' => $key, 'value' => $value]);
        }

        self::$cache[$key] = $value;
    }

    /**
     * Delete a setting.
     */
    public static function delete($key)
    {
        DB::delete(DB::t('settings'), '`key` = ?', [$key]);
        unset(self::$cache[$key]);
    }

    /**
     * Reset the in-memory cache (useful after bulk operations).
     */
    public static function flush()
    {
        self::$cache = [];
        self::$loaded = false;
    }
}
