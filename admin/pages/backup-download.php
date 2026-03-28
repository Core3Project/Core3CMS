<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');

if (!Auth::checkCsrf($_GET['t'] ?? '')) {
    flash('error', 'Invalid request.'); header('Location: ' . adm()); exit;
}

$prefix = DB::prefix();
$tables = DB::rows("SHOW TABLES LIKE '{$prefix}%'");
if (!$tables) {
    flash('error', 'No tables found.'); header('Location: ' . adm()); exit;
}

$filename = 'core3-backup-' . date('Y-m-d-His') . '.sql';
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');

echo "-- Core 3 CMS Database Backup\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . "\n";
echo "-- Tables: " . count($tables) . "\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

foreach ($tables as $row) {
    $table = array_values($row)[0];

    // CREATE TABLE
    $create = DB::row("SHOW CREATE TABLE `{$table}`");
    echo "DROP TABLE IF EXISTS `{$table}`;\n";
    echo $create['Create Table'] . ";\n\n";

    // INSERT rows
    $rows = DB::rows("SELECT * FROM `{$table}`");
    if ($rows) {
        $cols = array_keys($rows[0]);
        $colList = '`' . implode('`,`', $cols) . '`';
        foreach ($rows as $r) {
            $vals = array_map(function($v) {
                if ($v === null) return 'NULL';
                return "'" . addslashes($v) . "'";
            }, array_values($r));
            echo "INSERT INTO `{$table}` ({$colList}) VALUES(" . implode(',', $vals) . ");\n";
        }
        echo "\n";
    }
}

echo "SET FOREIGN_KEY_CHECKS=1;\n";
exit;
