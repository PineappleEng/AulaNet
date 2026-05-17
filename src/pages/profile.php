<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Profile requires an authenticated user
aulanet_require_login();

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

aulanet_render_template(__DIR__ . '/profile.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    '/question/1.html' => './question.php?id=1',
    '/question/2.html' => './question.php?id=2',
    '/question/3.html' => './question.php?id=3',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);