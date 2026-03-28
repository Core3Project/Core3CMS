<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin', 'editor');
$t = DB::prefix(); $csrf = Auth::csrf();
if (isset($_GET['action'], $_GET['id'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    $cid = (int)$_GET['id'];
    $act = $_GET['action'];
    if ($act === 'approve') DB::update($t.'comments', ['status' => 'approved'], 'id=?', [$cid]);
    elseif ($act === 'pending') DB::update($t.'comments', ['status' => 'pending'], 'id=?', [$cid]);
    elseif ($act === 'spam') DB::update($t.'comments', ['status' => 'spam'], 'id=?', [$cid]);
    elseif ($act === 'delete') DB::delete($t.'comments', 'id=?', [$cid]);
    flash('success', 'Done.'); header('Location: ' . adm('comments' . (!empty($_GET['filter']) ? '?filter=' . $_GET['filter'] : ''))); exit;
}
$filter = $_GET['filter'] ?? 'all'; $pg = max(1, (int)($_GET['page'] ?? 1));
$w = '1=1'; $wp = [];
if ($filter !== 'all') { $w = "c.status=?"; $wp = [$filter]; }
$total = DB::count($t.'comments c', $w, $wp);
$pag = paginate($total, 20, $pg);
$comments = DB::rows("SELECT c.*,p.title as pt,p.slug as ps FROM {$t}comments c LEFT JOIN {$t}posts p ON c.post_id=p.id WHERE $w ORDER BY c.created_at DESC LIMIT {$pag['per_page']} OFFSET {$pag['offset']}", $wp);
adm_header('Comments');
?>
<h2 style="margin-bottom:16px">Comments</h2>
<div style="margin-bottom:12px;font-size:12px;display:flex;gap:8px">
<?php foreach (['all','pending','approved','spam'] as $k):
    $cw = $k === 'all' ? '1=1' : "status='$k'";
?><a href="<?= adm('comments?filter=' . $k) ?>" <?= $filter === $k ? 'style="font-weight:700;color:var(--c)"' : '' ?>><?= ucfirst($k) ?> (<?= DB::count($t.'comments', $cw) ?>)</a><?php endforeach; ?>
</div>
<div class="panel"><table class="tbl"><thead><tr><th>Author</th><th>Comment</th><th>Post</th><th>Status</th><th>Date</th><th></th></tr></thead><tbody>
<?php if (!$comments): ?><tr><td colspan="6" style="text-align:center;color:var(--m);padding:24px">No comments.</td></tr>
<?php else: foreach ($comments as $c): ?>
<tr><td><strong><?= e($c['author_name']) ?></strong><br><span style="font-size:10px;color:var(--m)"><?= e($c['author_email']) ?></span></td>
<td style="max-width:260px"><?= e(excerpt($c['content'], 100)) ?></td>
<td><a href="<?= url('post/' . ($c['ps'] ?? '')) ?>" target="_blank"><?= e(excerpt($c['pt'] ?? '?', 30)) ?></a></td>
<td><span class="badge badge-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
<td style="color:var(--m);white-space:nowrap"><?= timeAgo($c['created_at']) ?></td>
<td class="acts" style="white-space:nowrap">
<?php $base = 'admin/comments?filter=' . e($filter) . '&t=' . $csrf . '&id=' . $c['id'] . '&action='; ?>
<?php if ($c['status'] !== 'approved'): ?><a href="<?= url($base . 'approve') ?>">Approve</a><?php endif; ?>
<?php if ($c['status'] !== 'pending'): ?><a href="<?= url($base . 'pending') ?>">Pending</a><?php endif; ?>
<?php if ($c['status'] !== 'spam'): ?><a href="<?= url($base . 'spam') ?>">Spam</a><?php endif; ?>
<a href="<?= url($base . 'delete') ?>" onclick="return confirm('Delete permanently?')" class="del">Delete</a>
</td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php adm_footer(); ?>
