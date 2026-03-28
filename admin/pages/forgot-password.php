<?php
$adminBase = dirname($_SERVER['SCRIPT_NAME']);
$msg = $err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if ($email) { $r = Auth::createResetToken($email); if ($r) Mailer::sendPasswordReset($r['user']['email'], $r['token']); }
    $msg = 'If that email exists, a reset link has been sent.';
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password — Core 3 CMS</title><link rel="stylesheet" href="<?= $adminBase ?>/assets/css/admin.css?v=<?= e(Setting::get('cache_version', '0')) ?>">
</head><body class="auth-page">
<div class="auth-box">
    <h1>Forgot Password</h1><p class="sub">Enter your email to receive a reset link</p>
    <?php if ($msg): ?><div class="alert alert-success"><?= e($msg) ?></div><?php endif; ?>
    <form method="post"><div class="fg"><label>Email</label><input type="email" name="email" class="fc" required autofocus></div>
    <button type="submit" class="btn btn-primary" style="width:100%">Send Reset Link</button></form>
    <p style="margin-top:14px;font-size:13px;text-align:center"><a href="<?= $adminBase ?>/login">← Back to login</a></p>
</div></body></html>
