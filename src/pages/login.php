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

// If POST request, attempt a mock login and set session variables.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = filter_input(INPUT_POST, 'password');

    // In-memory/mock auth for now: treat any valid email as student,
    // emails that contain 'admin' become administrators.
    if ($email === false || $email === null) {
        header('Location: ./login.php');
        exit;
    }

    $role = (stripos($email, 'admin') !== false) ? 'admin' : 'student';

    $user = [
        'id' => random_int(1000, 9999),
        'email' => $email,
        'name' => strtok($email, '@'),
        'role' => $role,
    ];

    aulanet_login_user($user);

    header('Location: ./home.php');
    exit;
}

aulanet_render_template(__DIR__ . '/login.html', [
    '../index.html' => '../index.php',
    './login.html' => './login.php',
    './register.html' => './register.php',
    './home.html' => './home.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);