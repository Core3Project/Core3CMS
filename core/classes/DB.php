<?php
/**
 * Database abstraction layer.
 *
 * Wraps PDO with convenience methods for common operations.
 * All queries use prepared statements to prevent SQL injection.
 *
 * @package Core3
 */
class DB
{
    private static $pdo = null;

    /**
     * Get or create the PDO connection.
     */
    public static function connect()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        self::$pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );

        return self::$pdo;
    }

    /**
     * Execute a prepared statement and return the PDOStatement.
     */
    public static function query($sql, $params = [])
    {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch a single row or null.
     */
    public static function row($sql, $params = [])
    {
        $result = self::query($sql, $params)->fetch();
        return $result ?: null;
    }

    /**
     * Fetch all matching rows.
     */
    public static function rows($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert a row and return the new ID.
     */
    public static function insert($table, $data)
    {
        $columns = implode(',', array_map(function ($col) {
            return "`{$col}`";
        }, array_keys($data)));

        $placeholders = implode(',', array_fill(0, count($data), '?'));

        self::query(
            "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})",
            array_values($data)
        );

        return (int) self::connect()->lastInsertId();
    }

    /**
     * Update rows matching a WHERE clause.
     */
    public static function update($table, $data, $where, $whereParams = [])
    {
        $set = implode(',', array_map(function ($col) {
            return "`{$col}` = ?";
        }, array_keys($data)));

        self::query(
            "UPDATE {$table} SET {$set} WHERE {$where}",
            array_merge(array_values($data), $whereParams)
        );
    }

    /**
     * Delete rows matching a WHERE clause.
     */
    public static function delete($table, $where, $params = [])
    {
        self::query("DELETE FROM {$table} WHERE {$where}", $params);
    }

    /**
     * Count rows matching a WHERE clause.
     */
    public static function count($table, $where = '1=1', $params = [])
    {
        $row = self::row("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}", $params);
        return (int) $row['total'];
    }

    /**
     * Return the configured table prefix.
     */
    public static function prefix()
    {
        return DB_PREFIX;
    }

    /**
     * Return a fully prefixed table name.
     */
    public static function t($name)
    {
        return DB_PREFIX . $name;
    }
}
