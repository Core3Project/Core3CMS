<?php
// Maintenance Mode Module
// Runs early - blocks all frontend requests for non-admins

// Don't block admin pages or the login page
if (defined('C3_ADMIN')) return;

$allowAdmin = Setting::get('maintenance_allow_admin', '1') === '1';
if ($allowAdmin && Auth::check() && Auth::isAdmin()) return;

// Don't block RSS feed or sitemap
$uri = $_SERVER['REQUEST_URI'] ?? '';
if (strpos($uri, '/feed') !== false || strpos($uri, '/sitemap') !== false) return;

$title = Setting::get('maintenance_title', 'Under Maintenance');
$message = Setting::get('maintenance_message', "We're working on some improvements. We'll be back shortly.");
$siteName = Setting::get('site_name', 'Core 3 CMS');

http_response_code(503);
header('Retry-After: 3600');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — <?= htmlspecialchars($siteName) ?></title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#18181b;color:#e4e4e7;text-align:center;padding:40px 20px}
.wrap{max-width:480px}
h1{font-size:28px;font-weight:700;margin-bottom:12px}
p{font-size:16px;color:#a1a1aa;line-height:1.7;margin-bottom:24px}
.bar{width:60px;height:3px;background:#dc2626;border-radius:2px;margin:0 auto 24px}
a{color:#dc2626;text-decoration:none;font-size:14px}
a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="wrap">
    <h1><?= htmlspecialchars($title) ?></h1>
    <div class="bar"></div>
    <p><?= nl2br(htmlspecialchars($message)) ?></p>
    <a href="<?= Router::url('admin/login') ?>">Admin Login →</a>
</div>
</body>
</html>
<?php exit;
