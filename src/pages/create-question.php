<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Only authenticated students (or admins) may create questions
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

aulanet_render_template(__DIR__ . '/create-question.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);