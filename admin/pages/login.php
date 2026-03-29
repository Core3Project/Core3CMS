<?php
$adminBase = dirname($_SERVER['SCRIPT_NAME']);
if (Auth::check()) { header('Location: ' . $adminBase . '/'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (Auth::login(trim($_POST['login'] ?? ''), $_POST['password'] ?? '')) {
        header('Location: ' . $adminBase . '/'); exit;
    }
    $error = 'The username or password you entered is incorrect.';
}
$siteName = Setting::get('site_name', 'Core 3 CMS');
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Log In &lsaquo; <?= e($siteName) ?> &#8212; Core 3 CMS</title>
<link rel="icon" type="image/svg+xml" href="<?= url('assets/images/favicon.svg') ?>">
<link rel="icon" type="image/x-icon" href="<?= url('assets/images/favicon.ico') ?>">
<link rel="stylesheet" href="<?= $adminBase ?>/assets/css/admin.css?v=<?= e(Setting::get('cache_version', '0')) ?>">
</head><body class="auth-page">
<div class="auth-logo"><img src="<?= url('assets/images/logo.svg') ?>" alt="Core 3 CMS"></div>
<div class="auth-box">
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post">
        <div class="fg"><label>Username or Email Address</label><input type="text" name="login" class="fc" required autofocus></div>
        <div class="fg"><label>Password</label><input type="password" name="password" class="fc" required id="pass-field"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
            <div class="remember"><input type="checkbox" id="rem"><label for="rem">Remember Me</label></div>
            <button type="submit" class="btn btn-primary btn-lg">Log In</button>
        </div>
    </form>
</div>
<div class="auth-links">
    <a href="<?= $adminBase ?>/forgot-password">Lost your password?</a><br>
    <a href="<?= url() ?>">&larr; Go to <?= e($siteName) ?></a>
</div>
</body></html>
