<?php
/**
 * Authentication controller for the public site.
 *
 * Handles user registration when enabled in settings.
 *
 * @package Core3
 */
class AuthController
{
    /**
     * Display and process the registration form.
     */
    public function register()
    {
        if (Setting::get('registration_enabled', '0') !== '1') {
            http_response_code(404);
            Theme::render('404', ['pageTitle' => 'Not Found']);
            return;
        }

        $error   = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim(isset($_POST['username']) ? $_POST['username'] : '');
            $email    = trim(isset($_POST['email']) ? $_POST['email'] : '');
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            $role     = Setting::get('default_role', 'subscriber');

            if (!$username || !$email || !$password) {
                $error = 'All fields are required.';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters.';
            } else {
                $result = Auth::register($username, $email, $password, '', $role);

                if ($result === true) {
                    $success = 'Account created! <a href="' . Router::url('admin/login') . '">Log in</a>';
                } else {
                    $error = $result;
                }
            }
        }

        Theme::render('register', [
            'pageTitle'   => 'Register',
            'regError'    => $error,
            'regSuccess'  => $success,
        ]);
    }
}
