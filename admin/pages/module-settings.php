<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
$t = DB::prefix();

$slug = preg_replace('/[^a-z0-9_-]/', '', $_GET['module'] ?? '');
if (!$slug) { header('Location: ' . adm('modules')); exit; }

$jsonPath = C3_ROOT . '/content/modules/' . $slug . '/module.json';
if (!file_exists($jsonPath)) { flash('error', 'Module not found.'); header('Location: ' . adm('modules')); exit; }

$info = json_decode(file_get_contents($jsonPath), true);
$fields = isset($info['settings']) ? $info['settings'] : [];

// Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    foreach ($fields as $field) {
        $key = $field['key'];
        $val = trim($_POST['settings'][$key] ?? '');
        Setting::set($key, $val);
    }
    flash('success', 'Settings saved.');
    header('Location: ' . adm('module-settings?module=' . $slug));
    exit;
}

adm_header(($info['name'] ?? ucfirst($slug)) . ' Settings');
?>
<div class="action-bar">
    <h2><?= e($info['name'] ?? ucfirst($slug)) ?> — Settings</h2>
    <a href="<?= adm('modules') ?>" class="btn btn-outline btn-sm">← Modules</a>
</div>

<?php if (!$fields): ?>
<div class="panel"><div class="panel-bd" style="text-align:center;color:var(--m);padding:32px">
    <p>This module has no configurable settings.</p>
</div></div>
<?php else: ?>
<div class="adm-full">
<form method="post"><?= Auth::csrfField() ?>
<div class="panel">
    <div class="panel-hd">Configuration</div>
    <div class="panel-bd">
        <?php foreach ($fields as $field):
            $key = $field['key'];
            $type = isset($field['type']) ? $field['type'] : 'text';
            $label = isset($field['label']) ? $field['label'] : $key;
            $hint = isset($field['hint']) ? $field['hint'] : '';
            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
            $currentVal = Setting::get($key, isset($field['default']) ? $field['default'] : '');
        ?>
        <div class="fg">
            <label><?= e($label) ?></label>
            <?php if ($type === 'textarea'): ?>
                <textarea name="settings[<?= e($key) ?>]" class="fc" style="min-height:80px" placeholder="<?= e($placeholder) ?>"><?= e($currentVal) ?></textarea>
            <?php elseif ($type === 'select' && isset($field['options'])): ?>
                <select name="settings[<?= e($key) ?>]" class="fc">
                <?php foreach ($field['options'] as $optVal => $optLabel): ?>
                    <option value="<?= e($optVal) ?>" <?= $currentVal === (string)$optVal ? 'selected' : '' ?>><?= e($optLabel) ?></option>
                <?php endforeach; ?>
                </select>
            <?php elseif ($type === 'checkbox'): ?>
                <div class="check-row">
                    <input type="hidden" name="settings[<?= e($key) ?>]" value="0">
                    <input type="checkbox" name="settings[<?= e($key) ?>]" value="1" id="f-<?= e($key) ?>" <?= $currentVal === '1' ? 'checked' : '' ?>>
                    <label for="f-<?= e($key) ?>" style="text-transform:none;letter-spacing:0;font-weight:400"><?= e($hint) ?></label>
                </div>
            <?php else: ?>
                <input type="<?= $type === 'password' ? 'password' : 'text' ?>" name="settings[<?= e($key) ?>]" class="fc" value="<?= e($currentVal) ?>" placeholder="<?= e($placeholder) ?>">
            <?php endif; ?>
            <?php if ($hint && $type !== 'checkbox'): ?>
                <p class="hint"><?= e($hint) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<button type="submit" class="btn btn-primary">Save Settings</button>
</form>
</div>
<?php endif; ?>

<?php adm_footer(); ?>
