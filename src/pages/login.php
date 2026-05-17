<?php

declare(strict_types=1);

require __DIR__ . '/../includes/render-template.php';

aulanet_render_template(__DIR__ . '/login.html', [
    '../index.html' => '../index.php',
    './login.html' => './login.php',
    './register.html' => './register.php',
    './home.html' => './home.php',
]);