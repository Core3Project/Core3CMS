<?php $pageTitle = $cat['name']; Theme::partial('header'); ?>
<div class="container">
<div class="layout">
<main>
    <div class="post-single" style="padding:16px 24px;margin-bottom:20px">
        <h1>Category: <?= e($cat['name']) ?></h1>
        <?php if ($cat['description']): ?><p style="color:var(--m);margin-top:4px"><?= e($cat['description']) ?></p><?php endif; ?>
    </div>
    <?php if (!$posts): ?>
        <div class="post-card"><div class="post-card-body" style="text-align:center;color:var(--m)">No posts in this category.</div></div>
    <?php else: foreach ($posts as $p): ?>
        <article class="post-card">
            <?php if ($p['featured_image']): ?><a href="<?= url('post/' . $p['slug']) ?>"><img class="post-card-img" src="<?= url($p['featured_image']) ?>" alt="<?= e($p['title']) ?>"></a><?php endif; ?>
            <div class="post-card-body">
                <h2><a href="<?= url('post/' . $p['slug']) ?>"><?= e($p['title']) ?></a></h2>
                <div class="meta"><span><?= formatDate($p['published_at']) ?></span><span class="meta-dot"></span><span><?= e($p['author_name']) ?></span><span class="meta-dot"></span><span><?= $p['comment_count'] ?> comments</span></div>
                <p class="post-excerpt"><?= e($p['excerpt'] ?: excerpt($p['content'])) ?></p>
                <a href="<?= url('post/' . $p['slug']) ?>" class="read-more">Read more →</a>
            </div>
        </article>
    <?php endforeach; endif; ?>
    <?= renderPagination($pag, url('category/' . $cat['slug'])) ?>
</main>
<?php Theme::partial('sidebar'); ?>
</div></div>
<?php Theme::partial('footer'); ?>
