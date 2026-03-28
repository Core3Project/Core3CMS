<?php
// Register /contact route
Modules::on('routes', function(&$routes) {
    $routes['/contact'] = ['controller' => 'ContactModule', 'action' => 'show'];
});

// Add to nav
Modules::on('head', function() {
    // CSS for contact form is handled by the theme's comment-form styles
    return '';
});

class ContactModule {
    public function show(): void {
        $error = $success = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['_hp'])) { $success = 'Thanks!'; }
            else {
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $hookErr = null;
                Modules::hook('comment_validate', $hookErr); // Reuse Turnstile hook
                if ($hookErr) $error = $hookErr;
                elseif (!$name || !$email || !$message) $error = 'All fields required.';
                elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $error = 'Invalid email.';
                else {
                    $to = Setting::get('contact_recipient', Setting::get('mail_from', ''));
                    $siteName = Setting::get('site_name', 'Core 3 CMS');
                    $body = "<p><strong>From:</strong> " . e($name) . " (" . e($email) . ")</p><p>" . nl2br(e($message)) . "</p>";
                    if ($to) Mailer::send($to, "Contact Form - {$siteName}", $body);
                    $success = Setting::get('contact_success_message', 'Message sent! We\'ll get back to you soon.');
                    $_POST = [];
                }
            }
        }
        Theme::render('contact', compact('error', 'success'));
    }
}
