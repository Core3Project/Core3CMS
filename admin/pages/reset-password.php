<?php
$adminBase = dirname($_SERVER['SCRIPT_NAME']);
$token = $_GET['token'] ?? '';
$u = $token ? DB::row("SELECT * FROM " . DB::t('users') . " WHERE reset_token=? AND reset_expires>NOW()", [$token]) : null;
if (!$u) die('<div style="font-family:sans-serif;text-align:center;padding:80px"><h2>Invalid or expired link.</h2><p><a href="' . $adminBase . '/forgot-password">Request a new one</a></p></div>');
$err = $ok = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
    if (strlen($pw) < 6) $err = 'Min 6 characters.';
    elseif ($pw !== $pw2) $err = "Passwords don't match.";
    else { Auth::resetPassword($token, $pw); $ok = 'Password reset! <a href="' . $adminBase . '/login">Log in</a>'; }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password</title><link rel="stylesheet" href="<?= $adminBase ?>/assets/css/admin.css?v=<?= e(Setting::get('cache_version', '0')) ?>"></head>
<body class="auth-page"><div class="auth-box"><h1>Reset Password</h1>
<?php if ($ok): ?><div class="alert alert-success"><?= $ok ?></div>
<?php else: ?>
<?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
<form method="post" style="margin-top:12px">
<div class="fg"><label>New Password</label><input type="password" name="password" class="fc" required></div>
<div class="fg"><label>Confirm</label><input type="password" name="password2" class="fc" required></div>
<button type="submit" class="btn btn-primary" style="width:100%">Reset Password</button></form>
<?php endif; ?></div></body></html>
