<?php
require __DIR__ . '/../layout.php';
$t = DB::prefix(); $csrf = Auth::csrf();

if (isset($_GET['trash'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    DB::update($t.'posts', ['status' => 'trash'], 'id=?', [(int)$_GET['trash']]);
    flash('success', 'Post trashed.'); header('Location: ' . adm('posts')); exit;
}
if (isset($_GET['restore'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    DB::update($t.'posts', ['status' => 'draft'], 'id=?', [(int)$_GET['restore']]);
    flash('success', 'Restored.'); header('Location: ' . adm('posts?status=trash')); exit;
}

$status = $_GET['status'] ?? 'all';
$pg = max(1, (int)($_GET['page'] ?? 1));
$w = "p.status!='trash'"; $wp = [];
if ($status !== 'all') { $w = "p.status=?"; $wp = [$status]; }
if (!Auth::canEdit()) { $w .= " AND p.author_id=?"; $wp[] = Auth::id(); }
$total = DB::count($t.'posts p', $w, $wp);
$pag = paginate($total, 20, $pg);
$posts = DB::rows("SELECT p.*,u.display_name as author,c.name as cat,(SELECT COUNT(*) FROM {$t}comments WHERE post_id=p.id) as cc FROM {$t}posts p LEFT JOIN {$t}users u ON p.author_id=u.id LEFT JOIN {$t}categories c ON p.category_id=c.id WHERE $w ORDER BY p.created_at DESC LIMIT {$pag['per_page']} OFFSET {$pag['offset']}", $wp);

adm_header('Posts');
?>
<div class="action-bar"><h2>Posts</h2><a href="<?= adm('post-edit') ?>" class="btn btn-primary">+ New Post</a></div>
<div style="margin-bottom:12px;font-size:12px;display:flex;gap:8px">
<?php foreach (['all' => "status!='trash'", 'published' => "status='published'", 'draft' => "status='draft'", 'trash' => "status='trash'"] as $k => $cw):
    $cwp = []; if (!Auth::canEdit()) { $cw .= " AND author_id=" . Auth::id(); }
    $cnt = DB::count($t.'posts', $cw, $cwp);
?><a href="<?= adm('posts?status=' . $k) ?>" <?= $status === $k ? 'style="font-weight:700;color:var(--c)"' : '' ?>><?= ucfirst($k) ?> (<?= $cnt ?>)</a><?php endforeach; ?>
</div>
<div class="panel">
<table class="tbl"><thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php if (!$posts): ?><tr><td colspan="5" style="text-align:center;color:var(--m);padding:24px">No posts.</td></tr>
<?php else: foreach ($posts as $r): ?>
<tr><td><a href="<?= adm('post-edit?id=' . $r['id']) ?>"><strong><?= e($r['title']) ?></strong></a>
<div class="acts" style="margin-top:2px"><a href="<?= adm('post-edit?id=' . $r['id']) ?>">Edit</a>
<?php if ($r['status'] === 'published'): ?><a href="<?= url('post/' . $r['slug']) ?>" target="_blank">View</a><?php endif; ?>
<?php if ($r['status'] === 'trash'): ?><a href="<?= adm('posts?restore=' . $r['id'] . '&t=' . $csrf) ?>">Restore</a>
<?php else: ?><a href="<?= adm('posts?trash=' . $r['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Trash?')" class="del">Trash</a><?php endif; ?>
</div></td>
<td><?= e($r['author']) ?></td><td><?= e($r['cat'] ?? '—') ?></td>
<td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
<td style="color:var(--m);white-space:nowrap"><?= formatDate($r['created_at']) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php adm_footer(); ?>
