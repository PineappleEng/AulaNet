<?php

declare(strict_types=1);

require __DIR__ . '/../includes/render-template.php';

aulanet_render_template(__DIR__ . '/profile.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    '/question/1.html' => './question.php?id=1',
    '/question/2.html' => './question.php?id=2',
    '/question/3.html' => './question.php?id=3',
]);