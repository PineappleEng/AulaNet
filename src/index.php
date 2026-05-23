<?php

declare(strict_types=1);

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/render-template.php';

$recentQuestions = aulanet_fetch_recent_questions(4);
$recentQuestionsJson = json_encode($recentQuestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$indexScriptVersion = (string) @filemtime(__DIR__ . '/scripts/index.js');

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
    './pages/home.html' => './pages/home.php',
    './pages/login.php' => './pages/login.php',
    './pages/register.php' => './pages/register.php',
    './pages/question.php' => './pages/question.php',
    '<!-- DATA SCRIPT -->' => '<script>window.AulaNetQuestions = ' . $recentQuestionsJson . ';</script>',
    './scripts/index.js' => './scripts/index.js?v=' . $indexScriptVersion,
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);