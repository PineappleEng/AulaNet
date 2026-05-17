<?php

declare(strict_types=1);

require __DIR__ . '/../includes/render-template.php';

aulanet_render_template(__DIR__ . '/admin.html', [
    '/home.html' => './home.php',
    '/admin.html' => './admin.php',
    '/profile.html' => './profile.php',
]);