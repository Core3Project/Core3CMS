<?php
/**
 * Authentication and user management.
 *
 * Handles login, logout, registration, password resets,
 * CSRF tokens, and role-based access control.
 *
 * @package Core3
 */
class Auth
{
    /**
     * Attempt to log in with the given credentials.
     */
    public static function login($login, $password)
    {
        $table = DB::t('users');
        $user  = DB::row(
            "SELECT * FROM {$table} WHERE (username = ? OR email = ?) AND status = 'active'",
            [$login, $login]
        );

        if (!$user || !password_verify($password, $user['password'])) {
            return false;
        }

        $_SESSION['uid']  = $user['id'];
        $_SESSION['role'] = $user['role'];

        DB::update($table, ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);

        return true;
    }

    /**
     * Register a new user account.
     *
     * Returns true on success or an error string on failure.
     */
    public static function register($username, $email, $password, $displayName = '', $role = 'subscriber')
    {
        $table = DB::t('users');

        if (DB::row("SELECT id FROM {$table} WHERE username = ? OR email = ?", [$username, $email])) {
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
     * Destroy the current session.
     */
    public static function logout()
    {
        session_destroy();
    }

    /**
     * Check whether a user is currently logged in.
     */
    public static function check()
    {
        return isset($_SESSION['uid']);
    }

    /**
     * Get the current user's ID or null.
     */
    public static function id()
    {
        return isset($_SESSION['uid']) ? $_SESSION['uid'] : null;
    }

    /**
     * Get the current user's role.
     */
    public static function role()
    {
        return isset($_SESSION['role']) ? $_SESSION['role'] : '';
    }

    /**
     * Fetch the full user row for the logged-in user.
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }
        return DB::row("SELECT * FROM " . DB::t('users') . " WHERE id = ?", [self::id()]);
    }

    /**
     * Require the user to be logged in, optionally with specific roles.
     *
     * Redirects to the login page if unauthenticated. Shows an
     * access-denied message if the user lacks the required role.
     */
    public static function guard()
    {
        $roles = func_get_args();

        if (!self::check()) {
            $adminDir = dirname($_SERVER['SCRIPT_NAME']);
            header('Location: ' . $adminDir . '/login');
            exit;
        }

        if ($roles && !in_array(self::role(), $roles)) {
            $adminDir = dirname($_SERVER['SCRIPT_NAME']);
            http_response_code(403);
            die('<h2>Access Denied</h2><p>You do not have permission to view this page.</p>'
                . '<p><a href="' . $adminDir . '/">Back to dashboard</a></p>');
        }
    }

    public static function isAdmin()
    {
        return self::role() === 'admin';
    }

    public static function canEdit()
    {
        return in_array(self::role(), ['admin', 'editor']);
    }

    public static function canWrite()
    {
        return in_array(self::role(), ['admin', 'editor', 'author']);
    }

    /**
     * Get or generate a CSRF token for the current session.
     */
    public static function csrf()
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /**
     * Validate a submitted CSRF token.
     */
    public static function checkCsrf($token)
    {
        return isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    /**
     * Render a hidden CSRF input field.
     */
    public static function csrfField()
    {
        return '<input type="hidden" name="_csrf" value="' . self::csrf() . '">';
    }

    /**
     * Generate a password-reset token for the given email.
     *
     * Returns an array with the user and token, or null if the email
     * does not match an active account.
     */
    public static function createResetToken($email)
    {
        $table = DB::t('users');
        $user  = DB::row("SELECT * FROM {$table} WHERE email = ? AND status = 'active'", [$email]);

        if (!$user) {
            return null;
        }

        $token   = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        DB::update($table, ['reset_token' => $token, 'reset_expires' => $expires], 'id = ?', [$user['id']]);

        return ['user' => $user, 'token' => $token];
    }

    /**
     * Consume a reset token and set the new password.
     */
    public static function resetPassword($token, $password)
    {
        $table = DB::t('users');
        $user  = DB::row("SELECT * FROM {$table} WHERE reset_token = ? AND reset_expires > NOW()", [$token]);

        if (!$user) {
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
