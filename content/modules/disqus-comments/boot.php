<?php
// Disqus Comments Module
// Replaces the built-in comment section with Disqus
// Uses the 'comments_replace' hook which the single post template checks

Modules::on('comments_replace', function(&$post) {
    $shortname = Setting::get('disqus_shortname', '');
    if (!$shortname) return '<p style="color:var(--m);padding:20px 0">Disqus is enabled but not configured. Go to Admin → Modules → Disqus Comments → Settings to enter your shortname.</p>';

    $postUrl = Router::url('post/' . $post['slug']);
    $postId = 'core3-post-' . $post['id'];
    $postTitle = htmlspecialchars($post['title'], ENT_QUOTES, 'UTF-8');

    return '
<div id="disqus_thread"></div>
<script>
var disqus_config = function () {
    this.page.url = "' . $postUrl . '";
    this.page.identifier = "' . $postId . '";
    this.page.title = "' . $postTitle . '";
};
(function() {
    var d = document, s = d.createElement("script");
    s.src = "https://' . htmlspecialchars($shortname, ENT_QUOTES, 'UTF-8') . '.disqus.com/embed.js";
    s.setAttribute("data-timestamp", +new Date());
    (d.head || d.body).appendChild(s);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>';
});

// Also add the Disqus comment count script to the footer
Modules::on('footer', function() {
    $shortname = Setting::get('disqus_shortname', '');
    if (!$shortname) return '';
    return '<script id="dsq-count-scr" src="https://' . htmlspecialchars($shortname, ENT_QUOTES, 'UTF-8') . '.disqus.com/count.js" async></script>';
});
