<?php

declare(strict_types=1);


require __DIR__ . '/../includes/session.php';
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

// Handle registration POST - create a mock student user and log in.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $name = filter_input(INPUT_POST, 'name') ?: (is_string($email) ? strtok($email, '@') : 'student');

    if ($email === false || $email === null) {
        header('Location: ./register.php');
        exit;
    }

    $user = [
        'id' => random_int(1000, 9999),
        'email' => $email,
        'name' => $name,
        'role' => 'student',
    ];

    aulanet_login_user($user);
    header('Location: ./home.php');
    exit;
}

aulanet_render_template(__DIR__ . '/register.html', [
    '../index.html' => '../index.php',
    './login.html' => './login.php',
    './register.html' => './register.php',
    './home.html' => './home.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);