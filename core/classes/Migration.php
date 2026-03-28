<?php

defined('C3_ROOT') || exit;

/**
 * Database migration system
 *
 * Tracks the installed schema version and applies pending
 * migrations automatically when Core 3 is updated. Each
 * migration is a method named migrate_X_Y_Z matching the
 * target version number.
 *
 * To add a migration:
 *  1. Append the version to self::$versions
 *  2. Create a matching private static method
 *  3. Bump C3_VERSION in core/bootstrap.php
 *
 * @see HOOKS.md for detailed contributor instructions
 */
class Migration
{
    /**
     * Ordered list of versions that carry a migration.
     * Append new entries at the end.
     *
     * @var array
     */
    private static $versions = [
        '3.0.0',
        '3.1.0',
    ];

    /**
     * Compare the stored DB version against C3_VERSION and
     * run every outstanding migration in sequence
     *
     * @return void
     */
    public static function run()
    {
        try {
            $current = Setting::get('db_version', '0.0.0');
        } catch (Exception $e) {
            return; // settings table may not exist yet
        }

        if (version_compare($current, C3_VERSION, '>=')) {
            return;
        }

        foreach (self::$versions as $version) {
            if (version_compare($current, $version, '>=')) {
                continue;
            }

            $method = 'migrate_' . str_replace('.', '_', $version);

            if (method_exists(__CLASS__, $method)) {
                self::$method();
            }

            Setting::set('db_version', $version);
        }

        // always record the running code version
        Setting::set('db_version', C3_VERSION);
    }

    // ----- migrations -----

    /**
     * 3.0.0 — baseline schema created by the installer
     *
     * @return void
     */
    private static function migrate_3_0_0()
    {
        // no-op: tables are created by install/index.php
    }

    /**
     * 3.1.0 — ensure cache_version setting exists
     *
     * @return void
     */
    private static function migrate_3_1_0()
    {
        $table = DB::t('settings');

        if ( ! DB::row("SELECT id FROM {$table} WHERE `key` = 'cache_version'")) {
            DB::insert($table, ['key' => 'cache_version', 'value' => '0']);
        }
    }
}
