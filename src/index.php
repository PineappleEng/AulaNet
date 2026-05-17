<?php

declare(strict_types=1);

require __DIR__ . '/includes/render-template.php';

// Render dynamic components
$nav = (function () {
    ob_start();
    include __DIR__ . '/includes/components/nav.php';
    return ob_get_clean();
})();

$footer = (function () {
    ob_start();
    include __DIR__ . '/includes/components/footer.php';
    return ob_get_clean();
})();

aulanet_render_template(__DIR__ . '/index.html', [
    './index.html' => './index.php',
    './pages/login.html' => './pages/login.php',
    './pages/register.html' => './pages/register.php',
    './pages/question.html' => './pages/question.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);