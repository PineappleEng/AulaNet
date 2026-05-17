<?php

declare(strict_types=1);

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

aulanet_render_template(__DIR__ . '/home.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    './question.html' => './question.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);