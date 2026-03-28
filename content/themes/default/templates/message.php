<?php Theme::partial('header'); ?>
<div class="container"><div class="layout-full">
<div class="post-single" style="text-align:center;padding:40px">
    <h1><?= e($pageTitle ?? 'Notice') ?></h1>
    <p style="color:var(--m);margin-top:12px"><?= $message ?? '' ?></p>
    <p style="margin-top:20px"><a href="<?= url() ?>" class="btn btn-outline">← Home</a></p>
</div>
</div></div>
<?php Theme::partial('footer'); ?>
