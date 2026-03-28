<?php

defined('C3_ROOT') || exit;

/**
 * Template and utility helpers
 *
 * Short, globally-available functions used across templates,
 * controllers, and admin pages.
 */

/**
 * Escape a string for safe HTML output
 *
 * @param string|null $string
 *
 * @return string
 */
function e($string)
{
    return htmlspecialchars(
        $string !== null ? $string : '',
        ENT_QUOTES,
        'UTF-8'
    );
}

/**
 * Generate a full URL relative to the site root
 *
 * @param string $path
 *
 * @return string
 */
function url($path = '')
{
    return Router::url($path);
}

/**
 * Convert a string to a URL-safe slug
 *
 * @param string $text
 *
 * @return string
 */
function slugify($text)
{
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);

    return strtolower(trim(preg_replace('~-+~', '-', $text), '-'));
}

/**
 * Strip HTML and truncate to a given length
 *
 * @param string $html
 * @param int    $length
 *
 * @return string
 */
function excerpt($html, $length = 200)
{
    $text = strip_tags($html);

    if (mb_strlen($text) <= $length) {
        return $text;
    }

    return mb_substr($text, 0, $length) . "\xE2\x80\xA6";
}

/**
 * Format a datetime using the configured date format
 *
 * @param string|null $datetime
 * @param string      $format   override the site setting
 *
 * @return string
 */
function formatDate($datetime, $format = '')
{
    if ( ! $datetime) {
        return '';
    }

    if ( ! $format) {
        $format = Setting::get('date_format', 'M d, Y');
    }

    return date($format, strtotime($datetime));
}

/**
 * Express a datetime as a relative time string
 *
 * @param string $datetime
 *
 * @return string
 */
function timeAgo($datetime)
{
    $seconds = time() - strtotime($datetime);

    if ($seconds < 60)     return 'just now';
    if ($seconds < 3600)   return floor($seconds / 60) . 'm ago';
    if ($seconds < 86400)  return floor($seconds / 3600) . 'h ago';
    if ($seconds < 604800) return floor($seconds / 86400) . 'd ago';

    return date('M d, Y', strtotime($datetime));
}

/**
 * Build pagination metadata
 *
 * @param int $total
 * @param int $perPage
 * @param int $currentPage
 *
 * @return array
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
 * Store a flash message in the session
 *
 * @param string $type success, error, info, warning
 * @param string $msg
 *
 * @return void
 */
function flash($type, $msg)
{
    $_SESSION['_flash'] = ['type' => $type, 'msg' => $msg];
}

/**
 * Retrieve and clear the flash message
 *
 * @return array|null
 */
function getFlash()
{
    $flash = isset($_SESSION['_flash']) ? $_SESSION['_flash'] : null;
    unset($_SESSION['_flash']);

    return $flash;
}

/**
 * Handle an image file upload
 *
 * @param array  $file the $_FILES entry
 * @param string $dir  target directory relative to C3_ROOT
 *
 * @return array ['ok' => true, 'file' => path] or ['error' => message]
 */
function uploadImage($file, $dir = 'content/uploads')
{
    $allowed = [
        'image/jpeg', 'image/png', 'image/gif',
        'image/webp', 'image/svg+xml',
    ];

    if ( ! in_array($file['type'], $allowed)) {
        return ['error' => 'Invalid file type.'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['error' => 'File exceeds 5 MB limit.'];
    }

    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $dest     = C3_ROOT . '/' . $dir . '/' . $filename;

    if ( ! move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'Upload failed. Check directory permissions.'];
    }

    return ['ok' => true, 'file' => $dir . '/' . $filename];
}

/**
 * Render a pagination navigation bar
 *
 * @param array  $pag     output from paginate()
 * @param string $baseUrl URL prefix for page links
 *
 * @return string
 */
function renderPagination($pag, $baseUrl)
{
    if ($pag['pages'] <= 1) {
        return '';
    }

    $html = '<nav class="pagination" aria-label="Pagination">';

    // previous link
    if ($pag['page'] > 1) {
        $prev = $pag['page'] - 1;
        $href = $baseUrl . ($prev > 1 ? '/' . $prev : '');
        $html .= '<a href="' . $href . '" class="pg-link">&larr; Prev</a>';
    }

    // numbered links
    for ($i = 1; $i <= $pag['pages']; $i++) {
        $href = $baseUrl . ($i > 1 ? '/' . $i : '');

        if ($i === $pag['page']) {
            $html .= '<span class="pg-link active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . $href . '" class="pg-link">' . $i . '</a>';
        }
    }

    // next link
    if ($pag['page'] < $pag['pages']) {
        $href = $baseUrl . '/' . ($pag['page'] + 1);
        $html .= '<a href="' . $href . '" class="pg-link">Next &rarr;</a>';
    }

    $html .= '</nav>';

    return $html;
}
