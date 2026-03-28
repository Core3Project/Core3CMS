<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
$t = DB::prefix(); $csrf = Auth::csrf();
if (isset($_GET['action'], $_GET['id'], $_GET['t']) && Auth::checkCsrf($_GET['t']) && (int)$_GET['id'] !== Auth::id()) {
    $uid = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'ban') DB::update($t.'users', ['status' => 'banned'], 'id=?', [$uid]);
    elseif ($act === 'activate') DB::update($t.'users', ['status' => 'active'], 'id=?', [$uid]);
    elseif ($act === 'delete') DB::delete($t.'users', 'id=?', [$uid]);
    flash('success', 'Done.'); header('Location: ' . adm('users')); exit;
}
$users = DB::rows("SELECT * FROM {$t}users ORDER BY created_at DESC");
adm_header('Users');
?>
<div class="action-bar"><h2>Users</h2><a href="<?= adm('user-edit') ?>" class="btn btn-primary">+ Add User</a></div>
<div class="panel"><table class="tbl"><thead><tr><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th>Last Login</th><th></th></tr></thead><tbody>
<?php foreach ($users as $u): ?>
<tr><td><a href="<?= adm('user-edit?id=' . $u['id']) ?>"><strong><?= e($u['username']) ?></strong></a><br><span style="font-size:11px;color:var(--m)"><?= e($u['display_name']) ?></span></td>
<td style="font-size:13px"><?= e($u['email']) ?></td>
<td><span class="adm-badge <?= $u['role'] ?>"><?= ucfirst($u['role']) ?></span></td>
<td><span class="badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
<td style="color:var(--m)"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
<td class="acts"><?php if ($u['id'] != Auth::id()): ?>
<a href="<?= adm('user-edit?id=' . $u['id']) ?>">Edit</a>
<?php if ($u['status'] === 'active'): ?><a href="<?= adm('users?action=ban&id=' . $u['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Ban?')">Ban</a>
<?php else: ?><a href="<?= adm('users?action=activate&id=' . $u['id'] . '&t=' . $csrf) ?>">Activate</a><?php endif; ?>
<a href="<?= adm('users?action=delete&id=' . $u['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Delete user?')" class="del">Delete</a>
<?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php adm_footer(); ?>
