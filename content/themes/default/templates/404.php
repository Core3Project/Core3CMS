<?php $pageTitle = 'Not Found'; Theme::partial('header'); ?>
<div class="container"><div class="layout-full">
<div class="post-single" style="text-align:center;padding:60px 32px">
    <h1 style="font-size:48px;margin-bottom:12px">404</h1>
    <p style="color:var(--m);font-size:18px;margin-bottom:20px">The page you're looking for doesn't exist.</p>
    <a href="<?= url() ?>" class="btn btn-primary">← Back to Home</a>
</div>
</div></div>
<?php Theme::partial('footer'); ?>
