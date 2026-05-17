<?php

declare(strict_types=1);

require __DIR__ . '/includes/render-template.php';

aulanet_render_template(__DIR__ . '/index.html', [
    './index.html' => './index.php',
    './pages/login.html' => './pages/login.php',
    './pages/register.html' => './pages/register.php',
    './pages/question.html' => './pages/question.php',
]);