<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin', 'editor');
$t = DB::prefix(); $csrf = Auth::csrf();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $name = trim($_POST['name'] ?? ''); $desc = trim($_POST['description'] ?? ''); $eid = (int)($_POST['edit_id'] ?? 0);
    if ($name) {
        $slug = slugify($name);
        if ($eid) { DB::update($t.'categories', ['name' => $name, 'slug' => $slug, 'description' => $desc], 'id=?', [$eid]); flash('success', 'Updated.'); }
        else { DB::insert($t.'categories', ['name' => $name, 'slug' => $slug, 'description' => $desc]); flash('success', 'Created.'); }
    }
    header('Location: ' . adm('categories')); exit;
}
if (isset($_GET['delete'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    DB::update($t.'posts', ['category_id' => null], 'category_id=?', [(int)$_GET['delete']]);
    DB::delete($t.'categories', 'id=?', [(int)$_GET['delete']]);
    flash('success', 'Deleted.'); header('Location: ' . adm('categories')); exit;
}
$cats = DB::rows("SELECT c.*,(SELECT COUNT(*) FROM {$t}posts WHERE category_id=c.id) as pc FROM {$t}categories c ORDER BY c.name");
$editing = isset($_GET['edit']) ? DB::row("SELECT * FROM {$t}categories WHERE id=?", [(int)$_GET['edit']]) : null;
adm_header('Categories');
?>
<h2 style="margin-bottom:16px">Categories</h2>
<div class="adm-grid">
<div>
<div class="panel"><table class="tbl"><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th></th></tr></thead><tbody>
<?php foreach ($cats as $c): ?>
<tr><td><strong><?= e($c['name']) ?></strong><?php if ($c['description']): ?><br><span style="font-size:11px;color:var(--m)"><?= e($c['description']) ?></span><?php endif; ?></td>
<td style="color:var(--m)"><?= e($c['slug']) ?></td><td style="text-align:center"><?= $c['pc'] ?></td>
<td class="acts"><a href="<?= adm('categories?edit=' . $c['id']) ?>">Edit</a> <a href="<?= adm('categories?delete=' . $c['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Delete?')" class="del">Delete</a></td></tr>
<?php endforeach; ?>
</tbody></table></div></div>
<div>
<div class="panel"><div class="panel-hd"><?= $editing ? 'Edit' : 'Add' ?> Category</div><div class="panel-bd">
<form method="post"><?= Auth::csrfField() ?><input type="hidden" name="edit_id" value="<?= $editing['id'] ?? 0 ?>">
<div class="fg"><label>Name</label><input type="text" name="name" class="fc" value="<?= e($editing['name'] ?? '') ?>" required></div>
<div class="fg"><label>Description</label><textarea name="description" class="fc" style="min-height:60px"><?= e($editing['description'] ?? '') ?></textarea></div>
<button type="submit" class="btn btn-primary"><?= $editing ? 'Update' : 'Add' ?></button>
<?php if ($editing): ?> <a href="<?= adm('categories') ?>" class="btn btn-outline">Cancel</a><?php endif; ?>
</form></div></div></div></div>
<?php adm_footer(); ?>
