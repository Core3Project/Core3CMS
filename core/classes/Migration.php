<?php
/**
 * Database migration system.
 *
 * Tracks the installed database schema version and applies any
 * pending migrations when Core 3 is updated. Each migration is
 * a method named migrate_X_Y_Z matching the target version.
 *
 * Usage: add a new method like migrate_3_2_0() for version 3.2.0,
 * then bump C3_VERSION in bootstrap.php. On the next page load,
 * Migration::run() will detect the version mismatch and execute
 * all pending migrations in order.
 *
 * @package Core3
 */
class Migration
{
    /**
     * Ordered list of all versions that have migrations.
     * Append new versions at the end.
     */
    private static $versions = [
        '3.0.0',
        '3.1.0',
    ];

    /**
     * Compare the installed DB version against C3_VERSION and
     * apply any outstanding migrations.
     */
    public static function run()
    {
        try {
            $current = Setting::get('db_version', '0.0.0');
        } catch (Exception $e) {
            // Settings table may not exist yet (fresh install).
            return;
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

        // Always store the current code version even if there
        // are no explicit migrations for it.
        Setting::set('db_version', C3_VERSION);
    }

    /**
     * Initial schema — tables created by the installer.
     * This migration only runs on existing installs that were
     * created before the migration system existed.
     */
    private static function migrate_3_0_0()
    {
        // No-op: the 3.0.0 schema is created by install/index.php.
        // This entry exists so the version chain starts cleanly.
    }

    /**
     * 3.1.0 — Add cache_version setting, ensure db_version exists.
     */
    private static function migrate_3_1_0()
    {
        $table = DB::t('settings');

        // Ensure cache_version setting exists.
        if (!DB::row("SELECT id FROM {$table} WHERE `key` = 'cache_version'")) {
            DB::insert($table, ['key' => 'cache_version', 'value' => '0']);
        }
    }

    /*
     * --- Adding a new migration ---
     *
     * 1. Add the version string to self::$versions above.
     * 2. Create a private static method named migrate_X_Y_Z.
     * 3. Bump C3_VERSION in core/bootstrap.php.
     *
     * Example for a future 3.2.0 release that adds a tags table:
     *
     * private static function migrate_3_2_0()
     * {
     *     $prefix = DB::prefix();
     *     DB::query("CREATE TABLE IF NOT EXISTS {$prefix}tags (
     *         id INT AUTO_INCREMENT PRIMARY KEY,
     *         name VARCHAR(100) NOT NULL,
     *         slug VARCHAR(100) NOT NULL UNIQUE
     *     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
     * }
     */
}
