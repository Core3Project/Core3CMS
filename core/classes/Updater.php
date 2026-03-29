<?php

defined('C3_ROOT') || exit;

/**
 * Update checker
 *
 * Queries the GitHub Releases API for newer versions of Core 3.
 * Results are cached in the settings table to avoid hitting the
 * API on every page load.
 */
class Updater
{
    /**
     * GitHub repository to check for releases
     *
     * @var string
     */
    private static $repo = 'Core3Project/Core3CMS';

    /**
     * Hours between API checks
     *
     * @var int
     */
    private static $interval = 12;

    /**
     * Check for updates and return release info if a newer
     * version is available, or null if up to date
     *
     * @return array|null
     */
    public static function check()
    {
        $cached  = Setting::get('update_check_result', '');
        $checked = (int) Setting::get('update_check_time', '0');

        if (time() - $checked > self::$interval * 3600 || ! $cached) {
            $release = self::fetchLatest();
            Setting::set('update_check_result', json_encode($release));
            Setting::set('update_check_time', (string) time());
        } else {
            $release = json_decode($cached, true);
        }

        if ( ! $release || empty($release['tag'])) {
            return null;
        }

        $latest = ltrim($release['tag'], 'v');

        if (version_compare($latest, C3_VERSION, '<=')) {
            return null;
        }

        return $release;
    }

    /**
     * Check whether the current version is the latest
     *
     * @return bool
     */
    public static function isUpToDate()
    {
        return self::check() === null;
    }

    /**
     * Query the GitHub Releases API
     *
     * @return array|null
     */
    private static function fetchLatest()
    {
        $url = 'https://api.github.com/repos/' . self::$repo . '/releases/latest';

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "User-Agent: Core3CMS/" . C3_VERSION . "\r\n",
                'timeout' => 5,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);

        if ( ! $response) {
            return null;
        }

        $data = json_decode($response, true);

        if ( ! $data || empty($data['tag_name'])) {
            return null;
        }

        return [
            'tag'      => $data['tag_name'],
            'url'      => isset($data['html_url']) ? $data['html_url'] : '',
            'date'     => isset($data['published_at']) ? $data['published_at'] : '',
            'body'     => isset($data['body']) ? mb_substr($data['body'], 0, 500) : '',
            'zip_url'  => isset($data['zipball_url']) ? $data['zipball_url'] : '',
        ];
    }
}
