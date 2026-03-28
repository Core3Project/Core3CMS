<?php
require __DIR__ . '/../layout.php';
Auth::guard('admin');
$tab = $_GET['tab'] ?? 'general';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && Auth::checkCsrf($_POST['_csrf'] ?? '')) {
    foreach ($_POST['s'] ?? [] as $k => $v) Setting::set($k, trim($v));
    foreach (['comments_enabled','comments_moderation','registration_enabled'] as $cb) {
        if (!isset($_POST['s'][$cb])) Setting::set($cb, '0');
    }
    if (isset($_POST['test_email'])) {
        $to = trim($_POST['test_to'] ?? '');
        if ($to && filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $ok = Mailer::send($to, 'Test - ' . Setting::get('site_name'), '<p>Test email from Core 3 CMS.</p>');
            flash($ok ? 'success' : 'error', $ok ? 'Test sent!' : 'Failed. Check settings.');
        }
        header('Location: ' . adm('settings?tab=email')); exit;
    }
    flash('success', 'Saved.'); header('Location: ' . adm('settings?tab=' . $tab)); exit;
}
adm_header('Settings');
?>
<h2 style="margin-bottom:16px">Settings</h2>
<div class="tabs">
<?php foreach (['general' => 'General', 'comments' => 'Comments', 'users' => 'Users', 'email' => 'Email', 'seo' => 'SEO'] as $k => $v): ?>
<a href="<?= adm('settings?tab=' . $k) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= $v ?></a>
<?php endforeach; ?>
</div>
<div class="adm-full">
<form method="post"><?= Auth::csrfField() ?>

<?php if ($tab === 'general'): ?>
<div class="panel"><div class="panel-bd">
<div class="fg"><label>Site Name</label><input type="text" name="s[site_name]" class="fc" value="<?= e(Setting::get('site_name')) ?>"></div>
<div class="fg"><label>Tagline</label><input type="text" name="s[site_tagline]" class="fc" value="<?= e(Setting::get('site_tagline')) ?>"></div>
<div class="fg"><label>Description</label><textarea name="s[site_description]" class="fc" style="min-height:60px"><?= e(Setting::get('site_description')) ?></textarea></div>
<div class="fg"><label>Posts Per Page</label><input type="number" name="s[posts_per_page]" class="fc" value="<?= e(Setting::get('posts_per_page', '10')) ?>" min="1"></div>
<div class="fg"><label>Timezone</label><input type="text" name="s[timezone]" class="fc" value="<?= e(Setting::get('timezone', 'UTC')) ?>"></div>
<div class="fg"><label>Date Format</label><input type="text" name="s[date_format]" class="fc" value="<?= e(Setting::get('date_format', 'M d, Y')) ?>"></div>
</div></div>

<?php elseif ($tab === 'comments'): ?>
<div class="panel"><div class="panel-bd">
<div class="check-row"><input type="checkbox" name="s[comments_enabled]" value="1" id="ce" <?= Setting::get('comments_enabled', '1') === '1' ? 'checked' : '' ?>><label for="ce" style="text-transform:none;letter-spacing:0;font-weight:400">Enable comments</label></div>
<div class="check-row"><input type="checkbox" name="s[comments_moderation]" value="1" id="cm" <?= Setting::get('comments_moderation', '1') === '1' ? 'checked' : '' ?>><label for="cm" style="text-transform:none;letter-spacing:0;font-weight:400">Require approval</label></div>
</div></div>

<?php elseif ($tab === 'users'): ?>
<div class="panel"><div class="panel-bd">
<div class="check-row"><input type="checkbox" name="s[registration_enabled]" value="1" id="re" <?= Setting::get('registration_enabled', '0') === '1' ? 'checked' : '' ?>><label for="re" style="text-transform:none;letter-spacing:0;font-weight:400">Allow public registration</label></div>
<div class="fg" style="margin-top:12px"><label>Default Role</label><select name="s[default_role]" class="fc"><?php foreach (['subscriber','author','editor'] as $r): ?><option value="<?= $r ?>" <?= Setting::get('default_role', 'subscriber') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select></div>
</div></div>

<?php elseif ($tab === 'email'): ?>
<div class="panel"><div class="panel-bd">
<div class="fg"><label>Method</label><select name="s[mail_method]" class="fc" id="mm"><option value="phpmail" <?= Setting::get('mail_method', 'phpmail') === 'phpmail' ? 'selected' : '' ?>>PHP mail()</option><option value="smtp" <?= Setting::get('mail_method') === 'smtp' ? 'selected' : '' ?>>SMTP</option></select></div>
<div class="fg"><label>From Email</label><input type="email" name="s[mail_from]" class="fc" value="<?= e(Setting::get('mail_from')) ?>"></div>
</div></div>
<div class="panel" id="smtp"><div class="panel-hd">SMTP</div><div class="panel-bd">
<div class="fg"><label>Host</label><input type="text" name="s[smtp_host]" class="fc" value="<?= e(Setting::get('smtp_host')) ?>"></div>
<div class="fg"><label>Port</label><input type="number" name="s[smtp_port]" class="fc" value="<?= e(Setting::get('smtp_port', '587')) ?>"></div>
<div class="fg"><label>Username</label><input type="text" name="s[smtp_user]" class="fc" value="<?= e(Setting::get('smtp_user')) ?>"></div>
<div class="fg"><label>Password</label><input type="password" name="s[smtp_pass]" class="fc" value="<?= e(Setting::get('smtp_pass')) ?>"></div>
<div class="fg"><label>Encryption</label><select name="s[smtp_encryption]" class="fc"><option value="tls" <?= Setting::get('smtp_encryption', 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option><option value="ssl" <?= Setting::get('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option><option value="none" <?= Setting::get('smtp_encryption') === 'none' ? 'selected' : '' ?>>None</option></select></div>
</div></div>
<div class="panel"><div class="panel-hd">Test Email</div><div class="panel-bd">
<div style="display:flex;gap:8px"><input type="email" name="test_to" class="fc" placeholder="test@example.com" style="margin:0"><button type="submit" name="test_email" value="1" class="btn btn-outline" style="white-space:nowrap">Send Test</button></div>
</div></div>
<script>var m=document.getElementById('mm'),s=document.getElementById('smtp');function u(){s.style.display=m.value==='smtp'?'':'none'}m.onchange=u;u();</script>

<?php elseif ($tab === 'seo'): ?>
<div class="panel"><div class="panel-bd">
<div class="fg"><label>Meta Description</label><textarea name="s[meta_description]" class="fc" style="min-height:60px"><?= e(Setting::get('meta_description')) ?></textarea></div>
<div class="fg"><label>Meta Keywords</label><input type="text" name="s[meta_keywords]" class="fc" value="<?= e(Setting::get('meta_keywords')) ?>"></div>
</div></div>
<?php endif; ?>

<button type="submit" class="btn btn-primary">Save Settings</button>
</form></div>
<?php adm_footer(); ?>
