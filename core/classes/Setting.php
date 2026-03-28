<?php

defined('C3_ROOT') || exit;

/**
 * Site settings
 *
 * Key-value pairs stored in the database with an in-memory
 * cache so each key is only queried once per request.
 */
class Setting
{
    /**
     * In-memory cache of all settings
     *
     * @var array
     */
    private static $cache = [];

    /**
     * Whether settings have been loaded from the database
     *
     * @var bool
     */
    private static $loaded = false;

    /**
     * Preload every setting into the cache
     *
     * @return void
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
            // the table may not exist during installation
        }

        self::$loaded = true;
    }

    /**
     * Retrieve a setting value
     *
     * @param string $key
     * @param string $default fallback when the key does not exist
     *
     * @return string
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
     * Create or update a setting
     *
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public static function set($key, $value)
    {
        self::load();

        $table    = DB::t('settings');
        $existing = DB::row("SELECT id FROM {$table} WHERE `key` = ?", [$key]);

        if ($existing) {
            DB::update($table, ['value' => $value], '`key` = ?', [$key]);
        } else {
            DB::insert($table, ['key' => $key, 'value' => $value]);
        }

        self::$cache[$key] = $value;
    }

    /**
     * Remove a setting
     *
     * @param string $key
     *
     * @return void
     */
    public static function delete($key)
    {
        DB::delete(DB::t('settings'), '`key` = ?', [$key]);
        unset(self::$cache[$key]);
    }

    /**
     * Clear the in-memory cache so settings are re-read
     * from the database on the next get() call
     *
     * @return void
     */
    public static function flush()
    {
        self::$cache  = [];
        self::$loaded = false;
    }
}
