<?php Theme::partial('header'); ?>
<div class="container">
<div class="layout">
<main>
<?php if (!$posts): ?>
    <div class="post-card"><div class="post-card-body" style="text-align:center;color:var(--m);padding:40px">No posts yet. Check back soon!</div></div>
<?php else: foreach ($posts as $p): ?>
    <article class="post-card">
        <?php if ($p['featured_image']): ?>
        <a href="<?= url('post/' . $p['slug']) ?>"><img class="post-card-img" src="<?= url($p['featured_image']) ?>" alt="<?= e($p['title']) ?>"></a>
        <?php endif; ?>
        <div class="post-card-body">
            <h2><a href="<?= url('post/' . $p['slug']) ?>"><?= e($p['title']) ?></a></h2>
            <div class="meta">
                <span><?= formatDate($p['published_at']) ?></span>
                <span class="meta-dot"></span>
                <span><?= e($p['author_name']) ?></span>
                <?php if ($p['cat_name']): ?>
                <span class="meta-dot"></span>
                <a href="<?= url('category/' . $p['cat_slug']) ?>"><?= e($p['cat_name']) ?></a>
                <?php endif; ?>
                <span class="meta-dot"></span>
                <span><?= $p['comment_count'] ?> comments</span>
            </div>
            <p class="post-excerpt"><?= e($p['excerpt'] ?: excerpt($p['content'])) ?></p>
            <a href="<?= url('post/' . $p['slug']) ?>" class="read-more">Read more →</a>
        </div>
    </article>
<?php endforeach; endif; ?>
<?= renderPagination($pag, url('pages')) ?>
</main>
<?php Theme::partial('sidebar'); ?>
</div>
</div>
<?php Theme::partial('footer'); ?>
