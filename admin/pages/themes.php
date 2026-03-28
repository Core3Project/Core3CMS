<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
if (isset($_GET['activate'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    $slug = preg_replace('/[^a-z0-9_-]/', '', $_GET['activate']);
    Setting::set('theme', $slug);
    // Bump cache so new theme CSS loads
    Setting::set('cache_version', (string)((int)Setting::get('cache_version','0') + 1));
    flash('success', 'Theme activated.'); header('Location: ' . adm('themes')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['theme_zip']['name']) && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $result = Theme::installZip($_FILES['theme_zip']['tmp_name']);
    flash($result === true ? 'success' : 'error', $result === true ? 'Theme installed.' : $result);
    header('Location: ' . adm('themes')); exit;
}
$themes = Theme::installed();
$active = Setting::get('theme', 'default');
adm_header('Appearance');
$csrf = Auth::csrf();
?>
<div class="action-bar"><h1>Themes</h1><a href="<?= adm('customize') ?>" class="btn btn-primary">Customize</a></div>
<div class="adm-grid"><div>
<div class="panel"><table class="tbl"><thead><tr><th>Theme</th><th>Author</th><th>Version</th><th>Status</th><th></th></tr></thead><tbody>
<?php foreach ($themes as $th): ?>
<tr><td><strong><?= e(isset($th['name']) ? $th['name'] : $th['slug']) ?></strong><br><span style="font-size:12px;color:var(--muted)"><?= e(isset($th['description']) ? $th['description'] : '') ?></span></td>
<td><?= e(isset($th['author']) ? $th['author'] : '?') ?></td><td><?= e(isset($th['version']) ? $th['version'] : '?') ?></td>
<td><?= $th['slug'] === $active ? '<span class="badge badge-active">Active</span>' : '' ?></td>
<td class="acts">
<?php if ($th['slug'] === $active): ?>
    <a href="<?= adm('customize') ?>">Customize</a>
<?php else: ?>
    <a href="<?= adm('themes?activate=' . e($th['slug']) . '&t=' . $csrf) ?>">Activate</a>
<?php endif; ?>
</td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div>
<div class="panel"><div class="panel-hd">Install Theme</div><div class="panel-bd">
<form method="post" enctype="multipart/form-data"><?= Auth::csrfField() ?>
<p style="font-size:12px;color:var(--muted);margin-bottom:10px">Upload a .zip containing a theme.json</p>
<input type="file" name="theme_zip" accept=".zip" class="fc" style="padding:6px;height:auto" required>
<button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">Install</button>
</form></div></div>
</div></div>
<?php adm_footer(); ?>
