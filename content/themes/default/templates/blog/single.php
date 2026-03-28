<?php $pageTitle = $post['title']; Theme::partial('header'); ?>
<div class="container">
<div class="layout">
<main>
    <article class="post-single">
        <?php if ($post['featured_image']): ?>
        <img class="featured" src="<?= url($post['featured_image']) ?>" alt="<?= e($post['title']) ?>">
        <?php endif; ?>
        <h1><?= e($post['title']) ?></h1>
        <div class="meta">
            <span><?= formatDate($post['published_at'], 'F d, Y') ?></span>
            <span class="meta-dot"></span>
            <span><?= e($post['author_name']) ?></span>
            <?php if ($post['cat_name']): ?>
            <span class="meta-dot"></span>
            <a href="<?= url('category/' . $post['cat_slug']) ?>"><?= e($post['cat_name']) ?></a>
            <?php endif; ?>
            <span class="meta-dot"></span>
            <span><?= $post['views'] ?> views</span>
        </div>
        <div class="post-content"><?= Markdown::render($post['content'], $post['content_format'] ?? 'html') ?></div>
        <?= Modules::html('post_content_after', $post) ?>
    </article>

    <!-- Comments -->
    <?php
    // Check if a module replaces the comment system (e.g. Disqus)
    $replacedComments = Modules::html('comments_replace', $post);
    if ($replacedComments): ?>
    <div class="comments-section"><?= $replacedComments ?></div>
    <?php else: ?>
    <div class="comments-section">
        <h3><?= count($comments) ?> Comment<?= count($comments) !== 1 ? 's' : '' ?></h3>
        <?php if (!$comments): ?>
            <p style="color:var(--m)">No comments yet. Be the first!</p>
        <?php else: foreach ($comments as $c): ?>
            <div class="comment">
                <div class="comment-head">
                    <span class="comment-author"><?= e($c['author_name']) ?></span>
                    <span class="comment-date"><?= formatDate($c['created_at'], 'M d, Y \a\t H:i') ?></span>
                </div>
                <div class="comment-body"><?= nl2br(e($c['content'])) ?></div>
            </div>
        <?php endforeach; endif; ?>
    </div>

    <?php if (Setting::get('comments_enabled', '1') === '1' && $post['allow_comments']): ?>
    <div class="comments-section">
        <h3>Leave a Comment</h3>
        <?php if (!empty($commentMsg)): ?>
            <div class="alert alert-<?= $commentMsg['type'] ?>"><?= e($commentMsg['msg']) ?></div>
        <?php endif; ?>
        <?= Modules::html('before_comment_form') ?>
        <form method="post" class="comment-form">
            <div style="position:absolute;left:-9999px"><input type="text" name="_hp" tabindex="-1" autocomplete="off"></div>
            <label>Your name</label>
            <input type="text" name="author_name" required placeholder="Your name" value="<?= e($_POST['author_name'] ?? '') ?>">
            <label>Your email (won't be published)</label>
            <input type="email" name="author_email" required placeholder="Your email (won't be published)" value="<?= e($_POST['author_email'] ?? '') ?>">
            <label>Your comment</label>
            <textarea name="content" required placeholder="Your comment"><?= e($_POST['content'] ?? '') ?></textarea>
            <?= Modules::html('comment_form_fields') ?>
            <button type="submit" class="btn btn-primary">Post Comment</button>
        </form>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <p style="margin-top:16px"><a href="<?= url() ?>">← Back to Home</a></p>
</main>
<?php Theme::partial('sidebar'); ?>
</div>
</div>
<?php Theme::partial('footer'); ?>
