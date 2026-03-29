<?php
require __DIR__ . '/../layout.php';
$t = DB::prefix();
$stats = [
    'Posts' => DB::count($t.'posts', "status!='trash'"),
    'Pages' => DB::count($t.'pages'),
    'Comments' => DB::count($t.'comments'),
    'Pending' => DB::count($t.'comments', "status='pending'"),
    'Users' => DB::count($t.'users'),
    'Views' => (int)(DB::row("SELECT COALESCE(SUM(views),0) as v FROM {$t}posts")['v']),
];
$recent = DB::rows("SELECT p.*,u.display_name as author FROM {$t}posts p LEFT JOIN {$t}users u ON p.author_id=u.id WHERE p.status!='trash' ORDER BY p.created_at DESC LIMIT 5");
adm_header('Dashboard');
?>
<div class="action-bar"><h1>Dashboard</h1></div>
<?php
// built-in update check
if (Auth::isAdmin()) {
    $update = Updater::check();
    if ($update) {
        $latestVersion = ltrim($update['tag'], 'v');
        $releaseDate = isset($update['date']) ? formatDate($update['date']) : '';
        echo '<div class="alert alert-info" style="margin-bottom:16px">'
            . '<strong>Core 3 CMS ' . e($latestVersion) . '</strong> is available. '
            . 'You are running ' . e(C3_VERSION) . '. '
            . ($releaseDate ? 'Released ' . e($releaseDate) . '. ' : '')
            . '<a href="' . e($update['url']) . '" target="_blank" rel="noopener">View release &rarr;</a>'
            . '</div>';
    }
}
?>
<?= Modules::html('admin_dashboard_before') ?>
<div class="stats">
<?php foreach ($stats as $l => $n): ?>
<div class="stat"><div class="n"><?= number_format($n) ?></div><div class="l"><?= $l ?></div></div>
<?php endforeach; ?>
</div>
<div class="adm-grid">
<div>
<div class="panel"><div class="panel-hd">Recent Posts <a href="<?= adm('post-edit') ?>" class="btn btn-primary btn-sm">+ New Post</a></div>
<table class="tbl"><thead><tr><th>Title</th><th>Status</th><th>Date</th></tr></thead><tbody>
<?php if (!$recent): ?><tr><td colspan="3" style="text-align:center;color:var(--muted);padding:20px">No posts yet. <a href="<?= adm('post-edit') ?>">Write your first post!</a></td></tr>
<?php else: foreach ($recent as $r): ?>
<tr><td><a href="<?= adm('post-edit?id=' . $r['id']) ?>"><?= e($r['title']) ?></a><br><span style="color:var(--muted)"><?= e($r['author']) ?></span></td>
<td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
<td style="color:var(--muted)"><?= formatDate($r['created_at']) ?></td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
</div>
<div>
<div class="panel"><div class="panel-hd">Quick Actions</div><div class="panel-bd" style="display:flex;flex-direction:column;gap:8px">
<a href="<?= adm('post-edit') ?>" class="btn btn-primary" style="justify-content:center">Write New Post</a>
<a href="<?= adm('page-edit') ?>" class="btn btn-outline" style="justify-content:center">Add Page</a>
<a href="<?= adm('comments') ?>" class="btn btn-outline" style="justify-content:center">Moderate Comments</a>
</div></div>
<div class="panel"><div class="panel-hd">At a Glance</div><div class="panel-bd" style="font-size:13px;color:var(--muted);line-height:2">
<?php if (Auth::isAdmin() && Updater::isUpToDate()): ?>
<span style="color:#00a32a">&#10003;</span> Core 3 CMS v<?= C3_VERSION ?> — up to date<br>
<?php else: ?>
Core 3 CMS v<?= C3_VERSION ?><br>
<?php endif; ?>
PHP <?= PHP_VERSION ?><br>
Theme: <?= e(Setting::get('theme', 'default')) ?><br>
Modules: <?= count(Modules::loaded()) ?> active
</div></div>
<?= Modules::html('admin_dashboard_after') ?>
</div>
</div>
