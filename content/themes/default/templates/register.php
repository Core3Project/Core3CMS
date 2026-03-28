<?php $pageTitle = 'Register'; Theme::partial('header'); ?>
<div class="container"><div class="layout-full" style="max-width:440px">
<div class="post-single">
    <h1>Create Account</h1>
    <?php if (!empty($error)): ?><div class="alert alert-error" style="margin-top:12px"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" style="margin-top:12px"><?= $success ?></div>
    <?php else: ?>
    <?= Modules::html('before_register_form') ?>
    <form method="post" class="comment-form" style="margin-top:16px">
        <label>Username</label><input type="text" name="username" required value="<?= e($_POST['username'] ?? '') ?>">
        <label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
        <label>Display Name</label><input type="text" name="display_name" value="<?= e($_POST['display_name'] ?? '') ?>">
        <label>Password</label><input type="password" name="password" required>
        <label>Confirm Password</label><input type="password" name="password2" required>
        <?= Modules::html('register_form_fields') ?>
        <button type="submit" class="btn btn-primary" style="width:100%">Register</button>
    </form>
    <p style="margin-top:12px;font-size:13px;color:var(--m)">Already have an account? <a href="<?= url('admin/login') ?>">Log in</a></p>
    <?php endif; ?>
</div>
</div></div>
<?php Theme::partial('footer'); ?>
