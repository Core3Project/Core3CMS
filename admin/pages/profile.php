<?php
require __DIR__ . '/../layout.php';
$t = DB::prefix(); $user = Auth::user(); $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $display = trim($_POST['display_name'] ?? ''); $email = trim($_POST['email'] ?? '');
    $bio = trim($_POST['bio'] ?? ''); $pw = $_POST['password'] ?? ''; $pw2 = $_POST['password2'] ?? '';
    if (!$email) $error = 'Email required.';
    elseif ($pw && $pw !== $pw2) $error = "Passwords don't match.";
    elseif ($pw && strlen($pw) < 6) $error = 'Min 6 chars.';
    elseif (DB::row("SELECT id FROM {$t}users WHERE email=? AND id!=?", [$email, $user['id']])) $error = 'Email taken.';
    if (!$error) {
        $data = ['display_name' => $display, 'email' => $email, 'bio' => $bio];
        if ($pw) $data['password'] = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
        DB::update($t.'users', $data, 'id=?', [$user['id']]);
        flash('success', 'Profile updated.'); header('Location: ' . adm('profile')); exit;
    }
}
adm_header('Profile');
?>
<div class="adm-full">
<h2 style="margin-bottom:16px">Edit Profile</h2>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= Auth::csrfField() ?>
<div class="panel"><div class="panel-hd">Account</div><div class="panel-bd">
    <div class="fg"><label>Username</label><input type="text" class="fc" value="<?= e($user['username']) ?>" disabled></div>
    <div class="fg"><label>Display Name</label><input type="text" name="display_name" class="fc" value="<?= e($user['display_name'] ?? '') ?>"></div>
    <div class="fg"><label>Email</label><input type="email" name="email" class="fc" value="<?= e($user['email']) ?>" required></div>
    <div class="fg"><label>Bio</label><textarea name="bio" class="fc" style="min-height:60px"><?= e($user['bio'] ?? '') ?></textarea></div>
</div></div>
<div class="panel"><div class="panel-hd">Change Password</div><div class="panel-bd">
    <div class="fg"><label>New Password</label><input type="password" name="password" class="fc" placeholder="Leave blank to keep"></div>
    <div class="fg"><label>Confirm</label><input type="password" name="password2" class="fc"></div>
</div></div>
<button type="submit" class="btn btn-primary">Save Profile</button>
</form></div>
<?php adm_footer(); ?>
