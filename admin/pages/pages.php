<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin', 'editor');
$t = DB::prefix(); $csrf = Auth::csrf();
if (isset($_GET['delete'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    DB::delete($t.'pages', 'id=?', [(int)$_GET['delete']]);
    flash('success', 'Deleted.'); header('Location: ' . adm('pages')); exit;
}
$pages = DB::rows("SELECT * FROM {$t}pages ORDER BY sort_order,title");
adm_header('Pages');
?>
<div class="action-bar"><h2>Pages</h2><a href="<?= adm('page-edit') ?>" class="btn btn-primary">+ New Page</a></div>
<div class="panel"><table class="tbl"><thead><tr><th>Title</th><th>Slug</th><th>Nav</th><th>Status</th><th>Order</th><th></th></tr></thead><tbody>
<?php if (!$pages): ?><tr><td colspan="6" style="text-align:center;color:var(--m);padding:24px">No pages.</td></tr>
<?php else: foreach ($pages as $p): ?>
<tr><td><a href="<?= adm('page-edit?id=' . $p['id']) ?>"><strong><?= e($p['title']) ?></strong></a></td>
<td style="color:var(--m)"><?= e($p['slug']) ?></td><td><?= $p['show_in_nav'] ? '✓' : '' ?></td>
<td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst($p['status']) ?></span></td><td><?= $p['sort_order'] ?></td>
<td class="acts"><a href="<?= adm('page-edit?id=' . $p['id']) ?>">Edit</a> <a href="<?= adm('pages?delete=' . $p['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Delete?')" class="del">Delete</a></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php adm_footer(); ?>
