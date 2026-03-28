<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin', 'editor');
$t = DB::prefix(); $id = (int)($_GET['id'] ?? 0); $page = null;
if ($id) { $page = DB::row("SELECT * FROM {$t}pages WHERE id=?", [$id]); if (!$page) { flash('error', 'Not found.'); header('Location: ' . adm('pages')); exit; } }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $title = trim($_POST['title'] ?? ''); $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? ''; $status = $_POST['status'] ?? 'published';
    $content_format = $_POST['content_format'] ?? 'html';
    $nav = isset($_POST['show_in_nav']) ? 1 : 0; $order = (int)($_POST['sort_order'] ?? 0);
    if (!$title) $error = 'Title required.';
    else {
        $slug = $slug ? slugify($slug) : slugify($title);
        $data = ['title' => $title, 'slug' => $slug, 'content' => $content, 'content_format' => $content_format, 'status' => $status, 'show_in_nav' => $nav, 'sort_order' => $order];
        if ($id) { DB::update($t.'pages', $data, 'id=?', [$id]); flash('success', 'Updated.'); }
        else { $id = DB::insert($t.'pages', $data); flash('success', 'Created.'); }
        header('Location: ' . adm('page-edit?id=' . $id)); exit;
    }
}
adm_header($id ? 'Edit Page' : 'New Page');
?>
<form method="post"><?= Auth::csrfField() ?>
<div class="action-bar"><h2><?= $id ? 'Edit Page' : 'New Page' ?></h2><a href="<?= adm('pages') ?>" class="btn btn-outline btn-sm">← Pages</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="adm-grid"><div>
<div class="panel"><div class="panel-bd">
    <div class="fg"><label>Title</label><input type="text" name="title" class="fc" value="<?= e($page['title'] ?? '') ?>" required></div>
    <div class="fg"><label>Slug</label><input type="text" name="slug" class="fc" value="<?= e($page['slug'] ?? '') ?>" placeholder="auto-generated"></div>
    <div class="fg"><label>Content</label>
        <?php $fmt = $page['content_format'] ?? 'html'; ?>
        <div id="editor-html" style="<?= $fmt === 'markdown' ? 'display:none' : '' ?>">
            <div data-c3-editor="content-html" data-placeholder="Page content..."><?= $fmt === 'html' ? ($page['content'] ?? '') : '' ?></div>
            <input type="hidden" name="content" id="content-html" value="<?= $fmt === 'html' ? e($page['content'] ?? '') : '' ?>">
        </div>
        <div id="editor-md" style="<?= $fmt === 'html' ? 'display:none' : '' ?>">
            <textarea name="content_md" id="content-md" class="fc" style="min-height:300px;font-family:var(--mono);font-size:14px;line-height:1.6;tab-size:2" placeholder="Write in Markdown..."><?= $fmt === 'markdown' ? e($page['content'] ?? '') : '' ?></textarea>
            <p class="hint" style="margin-top:4px">Supports Markdown and raw HTML.</p>
        </div>
    </div>
</div></div></div>
<div>
<div class="panel"><div class="panel-hd">Publish</div><div class="panel-bd">
    <div class="fg"><label>Format</label><select name="content_format" id="fmt-sel" class="fc"><option value="html" <?= $fmt === 'html' ? 'selected' : '' ?>>Visual / HTML</option><option value="markdown" <?= $fmt === 'markdown' ? 'selected' : '' ?>>Markdown</option></select></div>
    <div class="fg"><label>Status</label><select name="status" class="fc"><option value="published" <?= ($page['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>Published</option><option value="draft" <?= ($page['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option></select></div>
    <div class="fg"><label>Sort Order</label><input type="number" name="sort_order" class="fc" value="<?= e($page['sort_order'] ?? 0) ?>"></div>
    <div class="check-row"><input type="checkbox" name="show_in_nav" value="1" id="nav" <?= ($page['show_in_nav'] ?? 1) ? 'checked' : '' ?>><label for="nav" style="text-transform:none;letter-spacing:0;font-weight:400">Show in navigation</label></div>
    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px"><?= $id ? 'Update' : 'Save' ?></button>
</div></div></div></div></form>
<script src="<?= url('assets/js/editor.js') ?>"></script>
<script>
var fmtSel=document.getElementById('fmt-sel'),edH=document.getElementById('editor-html'),edM=document.getElementById('editor-md');
fmtSel.addEventListener('change',function(){if(this.value==='markdown'){edH.style.display='none';edM.style.display=''}else{edH.style.display='';edM.style.display='none'}});
document.querySelector('form').addEventListener('submit',function(){if(fmtSel.value==='markdown'){var i=document.querySelector('input[name="content"]');if(i)i.value=document.getElementById('content-md').value;else{var h=document.createElement('input');h.type='hidden';h.name='content';h.value=document.getElementById('content-md').value;this.appendChild(h)}}});
</script>
<?php adm_footer(); ?>
