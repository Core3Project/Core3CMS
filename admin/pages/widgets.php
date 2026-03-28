<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
$t = DB::prefix(); $csrf = Auth::csrf();

if (isset($_GET['delete'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    DB::delete($t.'widgets', 'id=?', [(int)$_GET['delete']]);
    flash('success', 'Deleted.'); header('Location: ' . adm('widgets')); exit;
}
if (isset($_GET['toggle'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    $w = DB::row("SELECT * FROM {$t}widgets WHERE id=?", [(int)$_GET['toggle']]);
    if ($w) DB::update($t.'widgets', ['active' => $w['active'] ? 0 : 1], 'id=?', [$w['id']]);
    flash('success', 'Toggled.'); header('Location: ' . adm('widgets')); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $eid = (int)($_POST['edit_id'] ?? 0);
    $type = $_POST['type'] ?? 'custom_html';
    $title = trim($_POST['title'] ?? '');
    $zone = $_POST['zone'] ?? 'sidebar';
    $order = (int)($_POST['sort_order'] ?? 0);
    $active = isset($_POST['active']) ? 1 : 0;
    $config = [];
    if ($type === 'custom_html') { $config['html'] = $_POST['widget_html'] ?? ''; }
    elseif ($type === 'recent_posts' || $type === 'recent_comments') { $config['limit'] = max(1, min(20, (int)($_POST['limit'] ?? 5))); }
    $data = ['zone' => $zone, 'type' => $type, 'title' => $title, 'config' => json_encode($config), 'active' => $active, 'sort_order' => $order];
    if ($eid) { DB::update($t.'widgets', $data, 'id=?', [$eid]); flash('success', 'Updated.'); }
    else { DB::insert($t.'widgets', $data); flash('success', 'Added.'); }
    header('Location: ' . adm('widgets')); exit;
}

$widgets = Widget::all();
$editing = isset($_GET['edit']) ? DB::row("SELECT * FROM {$t}widgets WHERE id=?", [(int)$_GET['edit']]) : null;
$editConfig = $editing ? (json_decode($editing['config'] ?? '{}', true) ?: []) : [];
$types = Widget::types();
adm_header('Widgets');
?>
<h2 style="margin-bottom:16px">Widgets</h2>
<div class="adm-grid"><div>
<?php foreach (['sidebar' => 'Sidebar', 'footer' => 'Footer'] as $zone => $zoneLabel): ?>
<div class="panel"><div class="panel-hd"><?= $zoneLabel ?></div>
<table class="tbl"><thead><tr><th>Widget</th><th>Title</th><th>Order</th><th>Active</th><th></th></tr></thead><tbody>
<?php $zw = array_filter($widgets, function($w) use ($zone) { return $w['zone'] === $zone; });
if (!$zw): ?><tr><td colspan="5" style="text-align:center;color:var(--m);padding:16px">No widgets.</td></tr>
<?php else: foreach ($zw as $w): ?>
<tr><td><strong><?= e(isset($types[$w['type']]) ? $types[$w['type']] : $w['type']) ?></strong></td>
<td><?= e($w['title'] ?: '—') ?></td><td><?= $w['sort_order'] ?></td>
<td><?= $w['active'] ? '<span class="badge badge-active">On</span>' : '<span class="badge badge-draft">Off</span>' ?></td>
<td class="acts">
<a href="<?= adm('widgets?edit=' . $w['id']) ?>">Edit</a>
<a href="<?= adm('widgets?toggle=' . $w['id'] . '&t=' . $csrf) ?>"><?= $w['active'] ? 'Disable' : 'Enable' ?></a>
<a href="<?= adm('widgets?delete=' . $w['id'] . '&t=' . $csrf) ?>" onclick="return confirm('Delete?')" class="del">Delete</a>
</td></tr>
<?php endforeach; endif; ?>
</tbody></table></div>
<?php endforeach; ?>
</div><div>
<div class="panel"><div class="panel-hd"><?= $editing ? 'Edit' : 'Add' ?> Widget</div><div class="panel-bd">
<form method="post"><?= Auth::csrfField() ?><input type="hidden" name="edit_id" value="<?= $editing['id'] ?? 0 ?>">
<div class="fg"><label>Type</label><select name="type" id="wtype" class="fc" <?= $editing ? 'disabled' : '' ?>>
<?php foreach ($types as $k => $v): ?><option value="<?= $k ?>" <?= ($editing['type'] ?? '') === $k ? 'selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?>
</select><?php if ($editing): ?><input type="hidden" name="type" value="<?= e($editing['type']) ?>"><?php endif; ?></div>
<div class="fg"><label>Title</label><input type="text" name="title" class="fc" value="<?= e($editing['title'] ?? '') ?>" placeholder="Optional"></div>
<div class="fg"><label>Zone</label><select name="zone" class="fc">
<option value="sidebar" <?= ($editing['zone'] ?? 'sidebar') === 'sidebar' ? 'selected' : '' ?>>Sidebar</option>
<option value="footer" <?= ($editing['zone'] ?? '') === 'footer' ? 'selected' : '' ?>>Footer</option></select></div>
<div class="fg"><label>Sort Order</label><input type="number" name="sort_order" class="fc" value="<?= e($editing['sort_order'] ?? 0) ?>"></div>
<div class="check-row"><input type="checkbox" name="active" value="1" id="wa" <?= ($editing['active'] ?? 1) ? 'checked' : '' ?>><label for="wa" style="text-transform:none;letter-spacing:0;font-weight:400">Active</label></div>
<div id="cfg-custom_html" class="wcfg" style="display:none"><div class="fg"><label>HTML Content</label><textarea name="widget_html" class="fc" style="min-height:150px;font-family:var(--mono);font-size:13px"><?= e($editConfig['html'] ?? '') ?></textarea><p class="hint">Any HTML, scripts, embeds.</p></div></div>
<div id="cfg-recent_posts" class="wcfg" style="display:none"><div class="fg"><label>Count</label><input type="number" name="limit" class="fc" value="<?= e($editConfig['limit'] ?? 5) ?>" min="1" max="20"></div></div>
<div id="cfg-recent_comments" class="wcfg" style="display:none"><div class="fg"><label>Count</label><input type="number" name="limit" class="fc" value="<?= e($editConfig['limit'] ?? 5) ?>" min="1" max="20"></div></div>
<button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px"><?= $editing ? 'Update' : 'Add' ?></button>
<?php if ($editing): ?><a href="<?= adm('widgets') ?>" class="btn btn-outline" style="width:100%;margin-top:6px;justify-content:center">Cancel</a><?php endif; ?>
</form></div></div>
</div></div>
<script>
function sc(){document.querySelectorAll('.wcfg').forEach(function(e){e.style.display='none'});var t=document.getElementById('wtype').value;var el=document.getElementById('cfg-'+t);if(el)el.style.display='block'}
document.getElementById('wtype').addEventListener('change',sc);sc();
</script>
<?php adm_footer(); ?>
