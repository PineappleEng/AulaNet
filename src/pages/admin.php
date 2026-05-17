<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Only administrators can access this page
aulanet_require_login();
aulanet_require_role('admin');

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

aulanet_render_template(__DIR__ . '/admin.html', [
    '/home.html' => './home.php',
    '/admin.html' => './admin.php',
    '/profile.html' => './profile.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);