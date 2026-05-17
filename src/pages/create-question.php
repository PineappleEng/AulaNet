<?php

declare(strict_types=1);

require __DIR__ . '/../includes/render-template.php';

aulanet_render_template(__DIR__ . '/create-question.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
]);