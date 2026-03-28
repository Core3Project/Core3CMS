<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin', 'editor');
$csrf = Auth::csrf();
$uploadDir = C3_ROOT . '/content/uploads';
$maxMb = (int) Setting::get('media_max_size', '5');

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['media_file']['name']) && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    $file = $_FILES['media_file'];
    if ($file['size'] > $maxMb * 1024 * 1024) {
        flash('error', 'File too large. Max ' . $maxMb . 'MB.');
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp','svg','mp4','mp3','pdf','zip','doc','docx','txt','csv'];
        if (!in_array($ext, $allowed)) {
            flash('error', 'File type not allowed.');
        } else {
            $name = preg_replace('/[^a-zA-Z0-9._-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
            $fname = $name . '-' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $fname)) {
                flash('success', 'Uploaded: ' . $fname);
            } else {
                flash('error', 'Upload failed. Check directory permissions.');
            }
        }
    }
    header('Location: ' . adm('media')); exit;
}

// Handle delete
if (isset($_GET['delete'], $_GET['t']) && Auth::checkCsrf($_GET['t'])) {
    $del = basename($_GET['delete']);
    $path = $uploadDir . '/' . $del;
    if ($del && $del !== '.htaccess' && $del !== 'index.php' && file_exists($path)) {
        unlink($path);
        flash('success', 'Deleted: ' . $del);
    }
    header('Location: ' . adm('media')); exit;
}

// Scan files
$files = [];
$ignore = ['.htaccess', 'index.php', '.', '..'];
if (is_dir($uploadDir)) {
    foreach (scandir($uploadDir) as $f) {
        if (in_array($f, $ignore) || is_dir($uploadDir . '/' . $f)) continue;
        $path = $uploadDir . '/' . $f;
        $files[] = [
            'name' => $f,
            'size' => filesize($path),
            'time' => filemtime($path),
            'ext' => strtolower(pathinfo($f, PATHINFO_EXTENSION)),
            'url' => url('content/uploads/' . $f),
        ];
    }
}
// Sort newest first
usort($files, function($a, $b) { return $b['time'] - $a['time']; });

$imageExts = ['jpg','jpeg','png','gif','webp','svg'];
adm_header('Media');
?>
<div class="action-bar"><h1>Media Library</h1></div>

<div class="adm-grid">
<div>
<?php if (!$files): ?>
<div class="panel"><div class="panel-bd" style="text-align:center;padding:40px;color:var(--muted)">
    <p style="font-size:15px;margin-bottom:8px">No files uploaded yet.</p>
    <p>Upload your first file using the form on the right.</p>
</div></div>
<?php else: ?>
<div class="panel"><div class="panel-hd"><?= count($files) ?> file<?= count($files) !== 1 ? 's' : '' ?></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;padding:12px">
<?php foreach ($files as $f): ?>
<div style="border:1px solid var(--border,#c3c4c7);border-radius:var(--radius);overflow:hidden;background:#f6f7f7;font-size:11px">
    <?php if (in_array($f['ext'], $imageExts)): ?>
    <div style="height:100px;background:url('<?= e($f['url']) ?>') center/cover no-repeat;cursor:pointer" onclick="prompt('Copy URL:','<?= e($f['url']) ?>')"></div>
    <?php else: ?>
    <div style="height:100px;display:flex;align-items:center;justify-content:center;font-size:24px;color:var(--muted);cursor:pointer" onclick="prompt('Copy URL:','<?= e($f['url']) ?>')">.<?= e($f['ext']) ?></div>
    <?php endif; ?>
    <div style="padding:6px 8px">
        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-weight:600" title="<?= e($f['name']) ?>"><?= e($f['name']) ?></div>
        <div style="color:var(--muted);display:flex;justify-content:space-between;align-items:center;margin-top:2px">
            <span><?= round($f['size'] / 1024) ?>KB</span>
            <a href="<?= adm('media?delete=' . urlencode($f['name']) . '&t=' . $csrf) ?>" onclick="return confirm('Delete this file?')" style="color:var(--red,#d63638)">Delete</a>
        </div>
    </div>
</div>
<?php endforeach; ?>
</div></div>
<?php endif; ?>
</div>
<div>
<div class="panel"><div class="panel-hd">Upload File</div><div class="panel-bd">
<form method="post" enctype="multipart/form-data"><?= Auth::csrfField() ?>
<p style="font-size:12px;color:var(--muted);margin-bottom:10px">Max size: <?= $maxMb ?>MB. Images, documents, and media.</p>
<input type="file" name="media_file" class="fc" style="padding:6px;height:auto" required>
<button type="submit" class="btn btn-primary" style="width:100%;margin-top:8px">Upload</button>
</form></div></div>

<div class="panel"><div class="panel-hd">Usage</div><div class="panel-bd" style="font-size:12px;color:var(--muted);line-height:1.8">
Click any image to copy its URL.<br>
Paste the URL into the post editor to embed it.
</div></div>
</div>
</div>
<?php adm_footer(); ?>
