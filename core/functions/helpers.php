<?php
/**
 * Template and utility functions.
 *
 * Short, globally-available helpers used by templates, controllers,
 * and admin pages throughout Core 3 CMS.
 *
 * @package Core3
 */

/**
 * Escape a string for safe HTML output.
 */
function e($string)
{
    return htmlspecialchars($string !== null ? $string : '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate a full URL relative to the site root.
 */
function url($path = '')
{
    return Router::url($path);
}

/**
 * Convert a string to a URL-safe slug.
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    return strtolower(trim(preg_replace('~-+~', '-', $text), '-'));
}

/**
 * Strip HTML tags and truncate to a given length.
 */
function excerpt($html, $length = 200)
{
    $text = strip_tags($html);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length) . '…';
}

/**
 * Format a datetime string using the site's configured date format.
 */
function formatDate($datetime, $format = '')
{
    if (!$datetime) {
        return '';
    }
    if (!$format) {
        $format = Setting::get('date_format', 'M d, Y');
    }
    return date($format, strtotime($datetime));
}

/**
 * Display a datetime as a relative time string (e.g. "3h ago").
 */
function timeAgo($datetime)
{
    $diff = time() - strtotime($datetime);

    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';

    return date('M d, Y', strtotime($datetime));
}

/**
 * Calculate pagination metadata.
 */
function paginate($total, $perPage, $currentPage)
{
    $totalPages  = max(1, (int) ceil($total / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));

    return [
        'total'    => $total,
        'per_page' => $perPage,
        'page'     => $currentPage,
        'pages'    => $totalPages,
        'offset'   => ($currentPage - 1) * $perPage,
    ];
}

/**
 * Store a flash message in the session.
 */
function flash($type, $msg)
{
    $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Retrieve and clear the flash message.
 */
function getFlash()
{
    $flash = isset($_SESSION['_flash']) ? $_SESSION['_flash'] : null;
    unset($_SESSION['_flash']);
    return $flash;
}

/**
 * Handle an image upload.
 *
 * Returns an array with 'ok' and 'file' on success,
 * or 'error' on failure.
 */
function uploadImage($file, $dir = 'content/uploads')
{
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];

    if (!in_array($file['type'], $allowed)) {
        return ['error' => 'Invalid file type.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File exceeds 5MB limit.'];
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destPath = C3_ROOT . '/' . $dir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['error' => 'Upload failed. Check directory permissions.'];
    }

    return ['ok' => true, 'file' => $dir . '/' . $filename];
}

/**
 * Render a pagination navigation bar.
 */
function renderPagination($pag, $baseUrl)
{
    if ($pag['pages'] <= 1) {
        return '';
    }

    $html = '<nav class="pagination" aria-label="Pagination">';

    if ($pag['page'] > 1) {
        $prev = $pag['page'] - 1;
        $href = $baseUrl . ($prev > 1 ? '/' . $prev : '');
        $html .= '<a href="' . $href . '" class="pg-link">&larr; Prev</a>';
    }

    for ($i = 1; $i <= $pag['pages']; $i++) {
        $href = $baseUrl . ($i > 1 ? '/' . $i : '');

        if ($i === $pag['page']) {
            $html .= '<span class="pg-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $href . '" class="pg-link">' . $i . '</a>';
        }
    }

    if ($pag['page'] < $pag['pages']) {
        $html .= '<a href="' . $baseUrl . '/' . ($pag['page'] + 1) . '" class="pg-link">Next &rarr;</a>';
    }

    $html .= '</nav>';

    return $html;
}
