<?php $pageTitle = $page['title']; Theme::partial('header'); ?>
<div class="container"><div class="layout-full">
<article class="post-single">
    <h1><?= e($page['title']) ?></h1>
    <div class="post-content" style="margin-top:16px"><?= Markdown::render($page['content'], $page['content_format'] ?? 'html') ?></div>
</article>
</div></div>
<?php Theme::partial('footer'); ?>
