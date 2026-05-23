<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/render-template.php';

// prepare components
$nav = (function () {
    ob_start();
    include __DIR__ . '/../includes/components/nav.php';
    return ob_get_clean();
})();

$footer = (function () {
    ob_start();
    include __DIR__ . '/../includes/components/footer.php';
    return ob_get_clean();
})();

// If POST request, authenticate against the database and set session variables.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = (string) filter_input(INPUT_POST, 'password');

    if ($email === false || $email === null) {
        header('Location: ./login.php');
        exit;
    }

    $user = authenticate_user((string) $email, $password);
    if ($user === null) {
        header('Location: ./login.php?error=1');
        exit;
    }

    aulanet_login_user($user);

    header('Location: ./home.php');
    exit;
}

aulanet_render_template(__DIR__ . '/login.html', [
    '../index.html' => '../index.php',
    './login.html' => './login.php',
    './register.html' => './register.php',
    './home.html' => './home.php',
    '<!-- AUTH ERROR -->' => isset($_GET['error']) ? '<div class="auth-error">Invalid email or password.</div>' : '',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);