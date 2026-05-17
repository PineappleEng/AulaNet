<?php

declare(strict_types=1);

// Ensure session helpers are available to templates and pages
require_once __DIR__ . '/session.php';

function aulanet_render_template(string $templatePath, array $replacements = []): void
{
    $markup = file_get_contents($templatePath);

    if ($markup === false) {
        http_response_code(500);
        echo 'Template not found.';
        return;
    }

    foreach ($replacements as $search => $replace) {
        $markup = str_replace($search, $replace, $markup);
    }

    echo $markup;
}