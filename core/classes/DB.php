<?php

defined('C3_ROOT') || exit;

/**
 * Database abstraction layer
 *
 * Thin wrapper around PDO providing convenience methods for
 * queries, inserts, updates, and deletes. Every query uses
 * prepared statements to guard against SQL injection.
 */
class DB
{
    /**
     * Shared PDO instance
     *
     * @var \PDO|null
     */
    private static $pdo = null;

    /**
     * Return the PDO connection, creating it on first use
     *
     * @return \PDO
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
     * Execute a prepared statement
     *
     * @param string $sql
     * @param array  $params
     *
     * @return \PDOStatement
     */
    public static function query($sql, $params = [])
    {
        $statement = self::connect()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * Fetch a single row
     *
     * @param string $sql
     * @param array  $params
     *
     * @return array|null
     */
    public static function row($sql, $params = [])
    {
        $result = self::query($sql, $params)->fetch();

        return $result ?: null;
    }

    /**
     * Fetch all matching rows
     *
     * @param string $sql
     * @param array  $params
     *
     * @return array
     */
    public static function rows($sql, $params = [])
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * Insert a row and return the new auto-increment ID
     *
     * @param string $table
     * @param array  $data column => value pairs
     *
     * @return int
     */
    public static function insert($table, $data)
    {
        $columns = [];

        foreach (array_keys($data) as $col) {
            $columns[] = "`{$col}`";
        }

        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        self::query(
            "INSERT INTO {$table} (" . implode(', ', $columns) . ") VALUES ({$placeholders})",
            array_values($data)
        );

        return (int) self::connect()->lastInsertId();
    }

    /**
     * Update rows matching a WHERE clause
     *
     * @param string $table
     * @param array  $data        column => value pairs to set
     * @param string $where       SQL condition
     * @param array  $whereParams bind values for the condition
     *
     * @return void
     */
    public static function update($table, $data, $where, $whereParams = [])
    {
        $setParts = [];

        foreach (array_keys($data) as $col) {
            $setParts[] = "`{$col}` = ?";
        }

        self::query(
            "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}",
            array_merge(array_values($data), $whereParams)
        );
    }

    /**
     * Delete rows matching a WHERE clause
     *
     * @param string $table
     * @param string $where
     * @param array  $params
     *
     * @return void
     */
    public static function delete($table, $where, $params = [])
    {
        self::query("DELETE FROM {$table} WHERE {$where}", $params);
    }

    /**
     * Count rows matching a WHERE clause
     *
     * @param string $table
     * @param string $where
     * @param array  $params
     *
     * @return int
     */
    public static function count($table, $where = '1=1', $params = [])
    {
        $row = self::row("SELECT COUNT(*) AS total FROM {$table} WHERE {$where}", $params);

        return (int) $row['total'];
    }

    /**
     * Return the configured table prefix
     *
     * @return string
     */
    public static function prefix()
    {
        return DB_PREFIX;
    }

    /**
     * Return a prefixed table name
     *
     * @param string $name
     *
     * @return string
     */
    public static function t($name)
    {
        return DB_PREFIX . $name;
    }
}
