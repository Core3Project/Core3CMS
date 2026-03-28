<?php
/**
 * Analytics Module - Simple page view counter
 * Creates a c3_analytics table on first run.
 */

// Ensure table exists
try {
    $t = DB::t('analytics');
    DB::query("CREATE TABLE IF NOT EXISTS $t (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(255) NOT NULL,
        views INT DEFAULT 1,
        date DATE NOT NULL,
        UNIQUE KEY page_date (page, date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (\Exception $e) {}

// Track page view on frontend
Modules::on('footer', function() {
    if (defined('C3_ADMIN')) return '';
    $page = $_SERVER['REQUEST_URI'] ?? '/';
    $page = substr($page, 0, 255);
    $date = date('Y-m-d');
    $t = DB::t('analytics');
    try {
        DB::query("INSERT INTO $t (page, views, date) VALUES (?, 1, ?) ON DUPLICATE KEY UPDATE views = views + 1", [$page, $date]);
    } catch (\Exception $e) {}
    return '';
});

// Sidebar widget: total views today
Modules::on('sidebar', function() {
    $t = DB::t('analytics');
    try {
        $today = DB::row("SELECT COALESCE(SUM(views), 0) as v FROM $t WHERE date = ?", [date('Y-m-d')]);
        $total = DB::row("SELECT COALESCE(SUM(views), 0) as v FROM $t");
    } catch (\Exception $e) {
        return '';
    }
    return '<div class="widget"><h3>Stats</h3><div style="display:flex;gap:16px;padding:4px 0"><div><strong>' . number_format($today['v']) . '</strong><br><span style="font-size:11px;color:var(--m)">Today</span></div><div><strong>' . number_format($total['v']) . '</strong><br><span style="font-size:11px;color:var(--m)">Total</span></div></div></div>';
});
