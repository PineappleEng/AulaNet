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

// Handle registration POST - create a database-backed student user and log in.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $name = trim((string) filter_input(INPUT_POST, 'name'));
    $password = (string) filter_input(INPUT_POST, 'password');
    $confirmPassword = (string) filter_input(INPUT_POST, 'confirmPassword');

    if ($email === false || $email === null || $password === '' || $password !== $confirmPassword) {
        header('Location: ./register.php');
        exit;
    }

    if ($name === '') {
        $name = strtok((string) $email, '@');
    }

    $userId = create_user((string) $email, $name, $password, 2);
    if ($userId === null) {
        header('Location: ./register.php?error=1');
        exit;
    }

    $user = aulanet_fetch_user_by_id($userId);
    if ($user === null) {
        header('Location: ./login.php');
        exit;
    }

    aulanet_login_user($user);
    header('Location: ./home.php');
    exit;
}

aulanet_render_template(__DIR__ . '/register.html', [
    '../index.html' => '../index.php',
    './login.html' => './login.php',
    './register.html' => './register.php',
    './home.html' => './home.php',
    '<!-- AUTH ERROR -->' => isset($_GET['error']) ? '<div class="auth-error">Could not create the account. Check the form and try again.</div>' : '',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);