<?php
require __DIR__ . '/../layout.php';
if (!Auth::canWrite()) die('No permission.');
$t = DB::prefix(); $id = (int)($_GET['id'] ?? 0); $post = null;
if ($id) {
    $post = DB::row("SELECT * FROM {$t}posts WHERE id=?", [$id]);
    if (!$post) { flash('error', 'Not found.'); header('Location: ' . adm('posts')); exit; }
    if (!Auth::canEdit() && $post['author_id'] !== Auth::id()) die('No permission.');
}
$cats = DB::rows("SELECT * FROM {$t}categories ORDER BY name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    $content = $_POST['content'] ?? '';
    $exc = trim($_POST['excerpt'] ?? '');
    $status = $_POST['status'] ?? 'draft';
    $cat_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : null;
    $allow_comments = isset($_POST['allow_comments']) ? 1 : 0;

    $content_format = $_POST['content_format'] ?? 'html';

    if (!$title) { $error = 'Title required.'; }
    else {
        $slug = $slug ? slugify($slug) : slugify($title);
        if (DB::row("SELECT id FROM {$t}posts WHERE slug=? AND id!=?", [$slug, $id])) $slug .= '-' . time();

        $img = $post['featured_image'] ?? null;
        if (!empty($_FILES['featured_image']['name'])) {
            $up = uploadImage($_FILES['featured_image']);
            if (isset($up['error'])) $error = $up['error']; else $img = $up['file'];
        }
        if (isset($_POST['remove_image'])) $img = null;

        if (!$error) {
            if (!$exc) $exc = excerpt($content_format === 'markdown' ? Markdown::parse($content) : $content);
            $pub = ($status === 'published') ? ($post['published_at'] ?? date('Y-m-d H:i:s')) : null;
            $data = ['title' => $title, 'slug' => $slug, 'content' => $content, 'excerpt' => $exc, 'content_format' => $content_format, 'featured_image' => $img, 'status' => $status, 'category_id' => $cat_id, 'allow_comments' => $allow_comments, 'published_at' => $pub];
            if ($id) { DB::update($t.'posts', $data, 'id=?', [$id]); flash('success', 'Updated.'); }
            else { $data['author_id'] = Auth::id(); $id = DB::insert($t.'posts', $data); flash('success', 'Created.'); }
            header('Location: ' . adm('post-edit?id=' . $id)); exit;
        }
    }
}

adm_header($id ? 'Edit Post' : 'New Post');
?>
<form method="post" enctype="multipart/form-data">
<?= Auth::csrfField() ?>
<div class="action-bar"><h2><?= $id ? 'Edit Post' : 'New Post' ?></h2><a href="<?= adm('posts') ?>" class="btn btn-outline btn-sm">← Posts</a></div>
<?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
<div class="adm-grid">
<div>
<div class="panel"><div class="panel-bd">
    <div class="fg"><label>Title</label><input type="text" name="title" class="fc" value="<?= e($post['title'] ?? '') ?>" required placeholder="Post title"></div>
    <div class="fg"><label>Slug</label><input type="text" name="slug" class="fc" value="<?= e($post['slug'] ?? '') ?>" placeholder="auto-generated"></div>
    <div class="fg"><label>Content</label>
        <?php $fmt = $post['content_format'] ?? 'html'; ?>
        <div id="editor-html" style="<?= $fmt === 'markdown' ? 'display:none' : '' ?>">
            <div data-c3-editor="content-html" data-placeholder="Write your post..."><?= $fmt === 'html' ? ($post['content'] ?? '') : '' ?></div>
            <input type="hidden" name="content" id="content-html" value="<?= $fmt === 'html' ? e($post['content'] ?? '') : '' ?>">
        </div>
        <div id="editor-md" style="<?= $fmt === 'html' ? 'display:none' : '' ?>">
            <textarea name="content_md" id="content-md" class="fc" style="min-height:350px;font-family:var(--mono);font-size:14px;line-height:1.6;tab-size:2" placeholder="Write in Markdown..."><?= $fmt === 'markdown' ? e($post['content'] ?? '') : '' ?></textarea>
            <p class="hint" style="margin-top:4px">Supports **bold**, *italic*, [links](url), ![images](url), ## headings, lists, > quotes, `code`, and raw HTML.</p>
        </div>
    </div>
    <div class="fg"><label>Excerpt</label><textarea name="excerpt" class="fc" style="min-height:70px" placeholder="Auto-generated if blank"><?= e($post['excerpt'] ?? '') ?></textarea></div>
</div></div>
</div>
<div>
<div class="panel"><div class="panel-hd">Publish</div><div class="panel-bd">
    <div class="fg"><label>Format</label><select name="content_format" id="fmt-sel" class="fc"><option value="html" <?= ($post['content_format'] ?? 'html') === 'html' ? 'selected' : '' ?>>Visual / HTML</option><option value="markdown" <?= ($post['content_format'] ?? '') === 'markdown' ? 'selected' : '' ?>>Markdown</option></select></div>
    <div class="fg"><label>Status</label><select name="status" class="fc"><option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option></select></div>
    <div class="fg"><label>Category</label><select name="category_id" class="fc"><option value="">— None —</option>
    <?php foreach ($cats as $c): ?><option value="<?= $c['id'] ?>" <?= ($post['category_id'] ?? '') == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
    <div class="check-row"><input type="checkbox" name="allow_comments" value="1" id="ac" <?= ($post['allow_comments'] ?? 1) ? 'checked' : '' ?>><label for="ac" style="text-transform:none;letter-spacing:0;font-weight:400">Allow comments</label></div>
    <button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px"><?= $id ? 'Update' : 'Publish' ?></button>
</div></div>
<div class="panel"><div class="panel-hd">Featured Image</div><div class="panel-bd">
    <?php if (!empty($post['featured_image'])): ?><img src="<?= url($post['featured_image']) ?>" style="width:100%;border-radius:6px;margin-bottom:8px">
    <div class="check-row"><input type="checkbox" name="remove_image" value="1" id="ri"><label for="ri" style="text-transform:none;letter-spacing:0;font-weight:400">Remove</label></div><?php endif; ?>
    <input type="file" name="featured_image" accept="image/*" class="fc">
</div></div>
<?php if ($id): ?><div class="panel"><div class="panel-hd">Info</div><div class="panel-bd" style="font-size:12px;color:var(--m)">
    <p>Created: <?= formatDate($post['created_at'], 'M d, Y H:i') ?></p>
    <p>Views: <?= $post['views'] ?></p>
    <p>ID: <?= $post['id'] ?></p>
</div></div><?php endif; ?>
</div></div></form>
<script src="<?= url('assets/js/editor.js') ?>"></script>
<script>
// Format toggle
var fmtSel = document.getElementById('fmt-sel');
var edHtml = document.getElementById('editor-html');
var edMd = document.getElementById('editor-md');
fmtSel.addEventListener('change', function() {
    if (this.value === 'markdown') { edHtml.style.display='none'; edMd.style.display=''; }
    else { edHtml.style.display=''; edMd.style.display='none'; }
});
// On submit, set the correct content field
document.querySelector('form').addEventListener('submit', function() {
    if (fmtSel.value === 'markdown') {
        // Use markdown textarea as content
        var inp = document.querySelector('input[name="content"]');
        if (inp) inp.value = document.getElementById('content-md').value;
        else { var h = document.createElement('input'); h.type='hidden'; h.name='content'; h.value=document.getElementById('content-md').value; this.appendChild(h); }
    }
});
</script>
<?php adm_footer(); ?>
