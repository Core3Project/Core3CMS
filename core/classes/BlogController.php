<?php
/**
 * Blog controller.
 *
 * Handles the post index, single post view, and category
 * listing pages on the public site.
 *
 * @package Core3
 */
class BlogController
{
    /**
     * Show the paginated post listing (homepage).
     */
    public function index($page = 1)
    {
        $page    = max(1, (int) $page);
        $perPage = (int) Setting::get('posts_per_page', '10');
        $t       = DB::prefix();

        $total = DB::count($t . 'posts', "status = 'published'");
        $pag   = paginate($total, $perPage, $page);

        $posts = DB::rows(
            "SELECT p.*, u.display_name AS author_name, c.name AS cat_name, c.slug AS cat_slug "
            . "FROM {$t}posts p "
            . "LEFT JOIN {$t}users u ON p.author_id = u.id "
            . "LEFT JOIN {$t}categories c ON p.category_id = c.id "
            . "WHERE p.status = 'published' "
            . "ORDER BY p.published_at DESC "
            . "LIMIT {$pag['per_page']} OFFSET {$pag['offset']}",
            []
        );

        Theme::render('blog/index', [
            'posts'     => $posts,
            'pag'       => $pag,
            'pageTitle' => Setting::get('site_name', 'Core 3 CMS'),
        ]);
    }

    /**
     * Show a single published post.
     */
    public function single($slug)
    {
        $t    = DB::prefix();
        $post = DB::row(
            "SELECT p.*, u.display_name AS author_name, c.name AS cat_name, c.slug AS cat_slug "
            . "FROM {$t}posts p "
            . "LEFT JOIN {$t}users u ON p.author_id = u.id "
            . "LEFT JOIN {$t}categories c ON p.category_id = c.id "
            . "WHERE p.slug = ? AND p.status = 'published'",
            [$slug]
        );

        if (!$post) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        // Increment view count.
        DB::query("UPDATE {$t}posts SET views = views + 1 WHERE id = ?", [$post['id']]);

        $comments = DB::rows(
            "SELECT * FROM {$t}comments WHERE post_id = ? AND status = 'approved' ORDER BY created_at ASC",
            [$post['id']]
        );

        // Handle comment submission.
        $commentMsg = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $post['allow_comments'] && Setting::get('comments_enabled', '1') === '1') {
            $commentMsg = $this->handleComment($post['id']);
        }

        Theme::render('blog/single', [
            'post'       => $post,
            'comments'   => $comments,
            'commentMsg' => $commentMsg,
        ]);
    }

    /**
     * Show posts filtered by category.
     */
    public function category($slug, $page = 1)
    {
        $page = max(1, (int) $page);
        $t    = DB::prefix();

        $cat = DB::row("SELECT * FROM {$t}categories WHERE slug = ?", [$slug]);

        if (!$cat) {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        $perPage = (int) Setting::get('posts_per_page', '10');
        $total   = DB::count($t . 'posts', "category_id = ? AND status = 'published'", [$cat['id']]);
        $pag     = paginate($total, $perPage, $page);

        $posts = DB::rows(
            "SELECT p.*, u.display_name AS author_name, c.name AS cat_name, c.slug AS cat_slug "
            . "FROM {$t}posts p "
            . "LEFT JOIN {$t}users u ON p.author_id = u.id "
            . "LEFT JOIN {$t}categories c ON p.category_id = c.id "
            . "WHERE p.category_id = ? AND p.status = 'published' "
            . "ORDER BY p.published_at DESC "
            . "LIMIT {$pag['per_page']} OFFSET {$pag['offset']}",
            [$cat['id']]
        );

        Theme::render('blog/category', [
            'category'  => $cat,
            'posts'     => $posts,
            'pag'       => $pag,
            'pageTitle' => $cat['name'],
        ]);
    }

    /**
     * Process a comment form submission.
     */
    private function handleComment($postId)
    {
        // Honeypot check.
        if (!empty($_POST['_hp'])) {
            return null;
        }

        $name    = trim(isset($_POST['author_name']) ? $_POST['author_name'] : '');
        $email   = trim(isset($_POST['author_email']) ? $_POST['author_email'] : '');
        $content = trim(isset($_POST['content']) ? $_POST['content'] : '');

        if (!$name || !$email || !$content) {
            return ['type' => 'error', 'msg' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['type' => 'error', 'msg' => 'Please enter a valid email address.'];
        }

        // Let modules validate (e.g. Turnstile).
        $hookError = null;
        Modules::hook('comment_validate', $hookError);

        if ($hookError) {
            return ['type' => 'error', 'msg' => $hookError];
        }

        $status = Setting::get('comments_moderation', '1') === '1' ? 'pending' : 'approved';

        DB::insert(DB::t('comments'), [
            'post_id'      => $postId,
            'author_name'  => $name,
            'author_email' => $email,
            'content'      => $content,
            'status'       => $status,
            'ip_address'   => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
        ]);

        $msg = ($status === 'pending')
            ? 'Your comment is awaiting moderation.'
            : 'Comment posted successfully.';

        return ['type' => 'success', 'msg' => $msg];
    }
}
