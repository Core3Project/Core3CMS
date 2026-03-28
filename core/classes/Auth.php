<?php

defined('C3_ROOT') || exit;

/**
 * Authentication
 *
 * Handles login, registration, password resets, session
 * management, CSRF tokens, and role-based access control.
 */
class Auth
{
    /**
     * Attempt to log in with the given credentials
     *
     * @param string $login    username or email
     * @param string $password plaintext password
     *
     * @return bool
     */
    public static function login($login, $password)
    {
        $table = DB::t('users');

        $user = DB::row(
            "SELECT * FROM {$table} WHERE (username = ? OR email = ?) AND status = 'active'",
            [$login, $login]
        );

        if ( !  $user || ! password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['uid']  = $user['id'];
        $_SESSION['role'] = $user['role'];

        DB::update($table, ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        return true;
    }

    /**
     * Register a new user account
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $displayName
     * @param string $role
     *
     * @return true|string true on success, error message on failure
     */
    public static function register($username, $email, $password, $displayName = '', $role = 'subscriber')
    {
        $table = DB::t('users');

        $exists = DB::row(
            "SELECT id FROM {$table} WHERE username = ? OR email = ?",
            [$username, $email]
        );

        if ($exists) {
            return 'Username or email already taken.';
        }

        DB::insert($table, [
            'username'     => $username,
            'email'        => $email,
            'password'     => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'display_name' => $displayName ?: $username,
            'role'         => $role,
            'status'       => 'active',
        ]);

        return true;
    }

    /**
     * Destroy the current session
     *
     * @return void
     */
    public static function logout()
    {
        session_destroy();
    }

    /**
     * Check whether a user is logged in
     *
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['uid']);
    }

    /**
     * Return the current user ID
     *
     * @return int|null
     */
    public static function id()
    {
        return isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
    }

    /**
     * Return the current user role
     *
     * @return string
     */
    public static function role()
    {
        return isset($_SESSION['role']) ? $_SESSION['role'] : '';
    }

    /**
     * Fetch the full user record for the logged-in user
     *
     * @return array|null
     */
    public static function user()
    {
        if ( !  self::check()) {
            return null;
        }

        return DB::row(
            "SELECT * FROM " . DB::t('users') . " WHERE id = ?",
            [self::id()]
        );
    }

    /**
     * Require the visitor to be logged in
     *
     * Pass one or more role names to restrict access further.
     * Redirects to the login page or shows an access-denied
     * message as appropriate.
     *
     * @return void
     */
    public static function guard()
    {
        $roles = func_get_args();

        if ( !  self::check()) {
            $dir = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $dir . '/login');
            exit;
        }

        if ($roles && ! in_array(self::role(), $roles)) {
            $dir = dirname($_SERVER['SCRIPT_NAME']);
            http_response_code(403);
            die(
                '<h2>Access Denied</h2>'
                . '<p>You do not have permission to view this page.</p>'
                . '<p><a href="' . $dir . '/">Back to dashboard</a></p>'
            );
        }
    }

    /**
     * @return bool
     */
    public static function isAdmin()
    {
        return self::role() === 'admin';
    }

    /**
     * @return bool
     */
    public static function canEdit()
    {
        return in_array(self::role(), ['admin', 'editor']);
    }

    /**
     * @return bool
     */
    public static function canWrite()
    {
        return in_array(self::role(), ['admin', 'editor', 'author']);
    }

    // ----- CSRF -----

    /**
     * Get or generate a CSRF token for the session
     *
     * @return string
     */
    public static function csrf()
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    /**
     * Verify a submitted CSRF token
     *
     * @param string $token
     *
     * @return bool
     */
    public static function checkCsrf($token)
    {
        return isset($_SESSION['_csrf'])
            && hash_equals($_SESSION['_csrf'], $token);
    }

    /**
     * Render a hidden CSRF form field
     *
     * @return string
     */
    public static function csrfField()
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrf() . '">';
    }

    // ----- Password reset -----

    /**
     * Generate a password-reset token for an email address
     *
     * @param string $email
     *
     * @return array|null array with 'user' and 'token', or null
     */
    public static function createResetToken($email)
    {
        $table = DB::t('users');

        $user = DB::row(
            "SELECT * FROM {$table} WHERE email = ? AND status = 'active'",
            [$email]
        );

        if ( !  $user) {
            return null;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        DB::update(
            $table,
            ['reset_token' => $token, 'reset_expires' => $expires],
            'id = ?',
            [$user['id']]
        );

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Consume a reset token and set a new password
     *
     * @param string $token
     * @param string $password plaintext
     *
     * @return bool
     */
    public static function resetPassword($token, $password)
    {
        $table = DB::t('users');

        $user = DB::row(
            "SELECT * FROM {$table} WHERE reset_token = ? AND reset_expires > NOW()",
            [$token]
        );

        if ( !  $user) {
            return false;
        }

        DB::update($table, [
            'password'      => password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]),
            'reset_token'   => null,
            'reset_expires' => null,
        ], 'id = ?', [$user['id']]);

        return true;
    }
}
