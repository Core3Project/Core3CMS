<?php

defined('C3_ROOT') || exit;

/**
 * Blog controller
 *
 * Handles the homepage, post listing, single post view,
 * and category pages on the public site.
 */
class BlogController
{
    /**
     * Show the homepage
     *
     * Checks the 'front_page' setting to determine whether to
     * display the latest posts or a static page.
     *
     * @param int $page pagination offset
     *
     * @return void
     */
    public function index($page = 1)
    {
        $page = max(1, (int) $page);

        // static front page — only on page 1 (pagination still shows posts)
        if ($page === 1 && Setting::get('front_page', 'posts') === 'page') {
            $pageId = (int) Setting::get('front_page_id', '0');

            if ($pageId) {
                $staticPage = DB::row(
                    "SELECT * FROM " . DB::t('pages') . " WHERE id = ? AND status = 'published'",
                    [$pageId]
                );

                if ($staticPage) {
                    Theme::render('page', [
                        'page'      => $staticPage,
                        'pageTitle' => $staticPage['title'],
                    ]);
                    return;
                }
            }
        }

        $this->showPosts($page);
    }

    /**
     * Display the paginated post listing
     *
     * @param int $page
     *
     * @return void
     */
    private function showPosts($page)
    {
        $t = DB::t('posts'); $tu = DB::t('users'); $tc = DB::t('categories'); $tcm = DB::t('comments');
        $pp = (int) Setting::get('posts_per_page', '10');
        $total = DB::count($t, "status='published'");
        $pag = paginate($total, $pp, $page);
        $posts = DB::rows("SELECT p.*, u.display_name as author_name, c.name as cat_name, c.slug as cat_slug,
            (SELECT COUNT(*) FROM $tcm WHERE post_id=p.id AND status='approved') as comment_count
            FROM $t p LEFT JOIN $tu u ON p.author_id=u.id LEFT JOIN $tc c ON p.category_id=c.id
            WHERE p.status='published' ORDER BY p.published_at DESC LIMIT {$pag['per_page']} OFFSET {$pag['offset']}");
        Theme::render('blog/index', compact('posts', 'pag'));
    }

    /**
     * Show a single published post
     *
     * @param string $slug
     *
     * @return void
     */
    public function single($slug)
    {
        $t = DB::t('posts'); $tu = DB::t('users'); $tc = DB::t('categories');
        $post = DB::row("SELECT p.*, u.display_name as author_name, u.bio as author_bio, c.name as cat_name, c.slug as cat_slug
            FROM $t p LEFT JOIN $tu u ON p.author_id=u.id LEFT JOIN $tc c ON p.category_id=c.id
            WHERE p.slug=? AND p.status='published'", [$slug]);
        if ( ! $post) { http_response_code(404); Theme::render('404', ['pageTitle' => 'Not Found']); return; }

        DB::query("UPDATE $t SET views=views+1 WHERE id=?", [$post['id']]);

        // Handle comment submission
        $commentMsg = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && Setting::get('comments_enabled', '1') === '1' && $post['allow_comments']) {
            $commentMsg = $this->handleComment($post['id']);
        }

        $comments = DB::rows("SELECT * FROM " . DB::t('comments') . " WHERE post_id=? AND status='approved' ORDER BY created_at ASC", [$post['id']]);
        Theme::render('blog/single', compact('post', 'comments', 'commentMsg'));
    }

    private function handleComment($postId) {
        // Honeypot check
        if ( ! empty($_POST['_hp'])) return ['type' => 'success', 'msg' => 'Thanks!'];

        // Module hook for validation (e.g. Turnstile)
        $hookErr = null;
        Modules::hook('comment_validate', $hookErr);
        if ($hookErr) return ['type' => 'error', 'msg' => $hookErr];

        $name = trim($_POST['author_name'] ?? '');
        $email = trim($_POST['author_email'] ?? '');
        $content = trim($_POST['content'] ?? '');

        if ( ! $name || !$email || !$content) return ['type' => 'error', 'msg' => 'All fields required.'];
        if ( ! filter_var($email, FILTER_VALIDATE_EMAIL)) return ['type' => 'error', 'msg' => 'Invalid email.'];

        $status = Setting::get('comments_moderation', '1') === '1' ? 'pending' : 'approved';
        DB::insert(DB::t('comments'), [
            'post_id' => $postId, 'author_name' => $name, 'author_email' => $email,
            'content' => $content, 'status' => $status,
            'user_id' => Auth::id(), 'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        Modules::hook('comment_created', $postId);
        $_POST = [];
        return ['type' => 'success', 'msg' => $status === 'pending' ? 'Comment awaiting moderation.' : 'Comment posted!'];
    }

    public function category($slug, $page = 1) {
        $cat = DB::row("SELECT * FROM " . DB::t('categories') . " WHERE slug=?", [$slug]);
        if ( ! $cat) { http_response_code(404); Theme::render('404', ['pageTitle' => 'Not Found']); return; }

        $t = DB::t('posts'); $tu = DB::t('users'); $tcm = DB::t('comments');
        $pp = (int) Setting::get('posts_per_page', '10');
        $total = DB::count($t, "status='published' AND category_id=?", [$cat['id']]);
        $pag = paginate($total, $pp, $page);
        $posts = DB::rows("SELECT p.*, u.display_name as author_name,
            (SELECT COUNT(*) FROM $tcm WHERE post_id=p.id AND status='approved') as comment_count
            FROM $t p LEFT JOIN $tu u ON p.author_id=u.id
            WHERE p.status='published' AND p.category_id=? ORDER BY p.published_at DESC
            LIMIT {$pag['per_page']} OFFSET {$pag['offset']}", [$cat['id']]);
        Theme::render('blog/category', compact('cat', 'posts', 'pag'));
    }
}
