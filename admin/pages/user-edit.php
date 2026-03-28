<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
$t = DB::prefix(); $id = (int)($_GET['id'] ?? 0); $user = null;
if ($id) { $user = DB::row("SELECT * FROM {$t}users WHERE id=?", [$id]); if (!$user) { flash('error', 'Not found.'); header('Location: ' . adm('users')); exit; } }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $username = trim($_POST['username'] ?? ''); $email = trim($_POST['email'] ?? '');
    $display = trim($_POST['display_name'] ?? ''); $role = $_POST['role'] ?? 'subscriber';
    $status = $_POST['status'] ?? 'active'; $pw = $_POST['password'] ?? ''; $bio = trim($_POST['bio'] ?? '');
    if (!$username || !$email) $error = 'Username and email required.';
    elseif (DB::row("SELECT id FROM {$t}users WHERE (username=? OR email=?) AND id!=?", [$username, $email, $id])) $error = 'Already taken.';
    elseif (!$id && !$pw) $error = 'Password required.';
    elseif ($pw && strlen($pw) < 6) $error = 'Min 6 chars.';
    if (!$error) {
        $data = ['username' => $username, 'email' => $email, 'display_name' => $display ?: $username, 'role' => $role, 'status' => $status, 'bio' => $bio];
        if ($pw) $data['password'] = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
        if ($id) { DB::update($t.'users', $data, 'id=?', [$id]); flash('success', 'Updated.'); }
        else { $id = DB::insert($t.'users', $data); flash('success', 'Created.'); }
        header('Location: ' . adm('user-edit?id=' . $id)); exit;
    }
}
adm_header($id ? 'Edit User' : 'Add User');
?>
<form method="post"><?= Auth::csrfField() ?>
<div class="action-bar"><h2><?= $id ? 'Edit User' : 'Add User' ?></h2><a href="<?= adm('users') ?>" class="btn btn-outline btn-sm">← Users</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="adm-grid"><div>
<div class="panel"><div class="panel-hd">Account</div><div class="panel-bd">
    <div class="fg"><label>Username</label><input type="text" name="username" class="fc" value="<?= e($user['username'] ?? '') ?>" required></div>
    <div class="fg"><label>Email</label><input type="email" name="email" class="fc" value="<?= e($user['email'] ?? '') ?>" required></div>
    <div class="fg"><label>Display Name</label><input type="text" name="display_name" class="fc" value="<?= e($user['display_name'] ?? '') ?>"></div>
    <div class="fg"><label>Bio</label><textarea name="bio" class="fc" style="min-height:60px"><?= e($user['bio'] ?? '') ?></textarea></div>
    <div class="fg"><label>Password<?= $id ? ' (blank = keep)' : '' ?></label><input type="password" name="password" class="fc" <?= $id ? '' : 'required' ?>></div>
</div></div></div>
<div>
<div class="panel"><div class="panel-hd">Role & Status</div><div class="panel-bd">
    <div class="fg"><label>Role</label><select name="role" class="fc"><?php foreach (['admin','editor','author','subscriber'] as $r): ?><option value="<?= $r ?>" <?= ($user['role'] ?? 'subscriber') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select>
    <p class="hint" style="margin-top:6px">Admin=full, Editor=content, Author=own posts, Subscriber=profile</p></div>
    <div class="fg"><label>Status</label><select name="status" class="fc"><?php foreach (['active','inactive','banned'] as $s): ?><option value="<?= $s ?>" <?= ($user['status'] ?? 'active') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?></select></div>
    <button type="submit" class="btn btn-primary" style="width:100%"><?= $id ? 'Update' : 'Create' ?></button>
</div></div></div></div></form>
<?php adm_footer(); ?>
