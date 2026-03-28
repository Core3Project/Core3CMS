<?php

defined('C3_ROOT') || exit;
class AuthController {
    public function register(): void {
        if (Setting::get('registration_enabled', '0') !== '1') {
            Theme::render('message', ['pageTitle' => 'Registration Disabled', 'message' => 'Registration is currently disabled.']);
            return;
        }
        if (Auth::check()) { header('Location: ' . Router::url()); exit; }

        $error = $success = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Module hook (Turnstile etc)
            $hookErr = null;
            Modules::hook('register_validate', $hookErr);
            if ($hookErr) { $error = $hookErr; }
            else {
                $u = trim($_POST['username'] ?? ''); $e = trim($_POST['email'] ?? '');
                $p = $_POST['password'] ?? ''; $p2 = $_POST['password2'] ?? '';
                if ( ! $u || !$e || !$p) $error = 'All fields required.';
                elseif ( ! filter_var($e, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
                elseif (strlen($p) < 6) $error = 'Password min 6 characters.';
                elseif ($p !== $p2) $error = "Passwords don't match.";
                else {
                    $result = Auth::register($u, $e, $p, trim($_POST['display_name'] ?? ''), Setting::get('default_role', 'subscriber'));
                    if ($result === true) $success = 'Account created! <a href="' . Router::url('admin/login') . '">Log in</a>';
                    else $error = $result;
                }
            }
        }
        Theme::render('register', compact('error', 'success'));
    }
}
