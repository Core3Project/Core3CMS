<?php $pageTitle = 'Contact'; Theme::partial('header'); ?>
<div class="container"><div class="layout-full" style="max-width:560px">
<div class="post-single">
    <h1>Contact Us</h1>
    <?php if (!empty($error)): ?><div class="alert alert-error" style="margin-top:12px"><?= e($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="alert alert-success" style="margin-top:12px"><?= e($success) ?></div>
    <?php else: ?>
    <form method="post" class="comment-form" style="margin-top:16px">
        <div style="position:absolute;left:-9999px"><input type="text" name="_hp" tabindex="-1" autocomplete="off"></div>
        <label>Name</label><input type="text" name="name" required value="<?= e($_POST['name'] ?? '') ?>" placeholder="Your name">
        <label>Email</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="Your email">
        <label>Message</label><textarea name="message" required placeholder="Your message"><?= e($_POST['message'] ?? '') ?></textarea>
        <?= Modules::html('comment_form_fields') ?>
        <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
    <?php endif; ?>
</div>
</div></div>
<?php Theme::partial('footer'); ?>
