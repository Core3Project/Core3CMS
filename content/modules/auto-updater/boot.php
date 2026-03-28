<?php
/**
 * Auto Updater Module
 *
 * Checks the GitHub Releases API for newer versions and shows
 * an update notice on the admin dashboard. Caches the check
 * result for 12 hours to avoid hitting the API on every page load.
 */

Modules::on('admin_dashboard_before', function () {
    if (!Auth::isAdmin()) {
        return '';
    }

    $repo    = Setting::get('update_repo', 'vexxusarts/core3cms');
    $cached  = Setting::get('update_check_result', '');
    $checked = (int) Setting::get('update_check_time', '0');

    // Re-check every 12 hours.
    if (time() - $checked > 43200 || !$cached) {
        $release = self_checkGitHub($repo);
        Setting::set('update_check_result', json_encode($release));
        Setting::set('update_check_time', (string) time());
    } else {
        $release = json_decode($cached, true);
    }

    if (!$release || empty($release['tag'])) {
        return '';
    }

    $latest  = ltrim($release['tag'], 'v');
    $current = C3_VERSION;

    if (version_compare($latest, $current, '<=')) {
        return '';
    }

    $url  = isset($release['url']) ? $release['url'] : '#';
    $date = isset($release['date']) ? formatDate($release['date']) : '';

    return '<div class="alert alert-info" style="margin-bottom:16px">'
        . '<strong>Core 3 CMS ' . e($latest) . '</strong> is available! '
        . 'You are running ' . e($current) . '. '
        . ($date ? 'Released ' . e($date) . '. ' : '')
        . '<a href="' . e($url) . '" target="_blank" rel="noopener">View release on GitHub &rarr;</a>'
        . '</div>';
});

/**
 * Query the GitHub Releases API for the latest release.
 */
function self_checkGitHub($repo)
{
    $apiUrl = 'https://api.github.com/repos/' . $repo . '/releases/latest';

    $context = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: Core3CMS/" . C3_VERSION . "\r\n",
            'timeout' => 5,
        ],
    ]);

    $response = @file_get_contents($apiUrl, false, $context);

    if (!$response) {
        return null;
    }

    $data = json_decode($response, true);

    if (!$data || empty($data['tag_name'])) {
        return null;
    }

    return [
        'tag'  => $data['tag_name'],
        'url'  => isset($data['html_url']) ? $data['html_url'] : '',
        'date' => isset($data['published_at']) ? $data['published_at'] : '',
        'body' => isset($data['body']) ? mb_substr($data['body'], 0, 500) : '',
    ];
}
