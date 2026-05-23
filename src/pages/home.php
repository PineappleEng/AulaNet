<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/render-template.php';

$questions = aulanet_fetch_recent_questions(50);
$subjects = aulanet_fetch_subjects();
$stats = aulanet_admin_stats();
$dataScript = '<script>window.AulaNetQuestions = ' . json_encode($questions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';window.AulaNetSubjects = ' . json_encode($subjects, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';window.AulaNetStats = ' . json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
$homeScriptVersion = (string) @filemtime(__DIR__ . '/../scripts/home.js');

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
    './question.php' => './question.php',
    '<!-- DATA SCRIPT -->' => $dataScript,
    '../scripts/home.js' => '../scripts/home.js?v=' . $homeScriptVersion,
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);