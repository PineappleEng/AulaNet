<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Profile requires an authenticated user
aulanet_require_login();

$currentUser = aulanet_get_user();
$profile = $currentUser !== null && isset($currentUser['id']) ? aulanet_fetch_user_overview((int) $currentUser['id']) : null;

if ($profile === null) {
    header('Location: ./login.php');
    exit;
}

$userQuestions = aulanet_fetch_user_questions((int) $profile['id']);
$userAnswers = aulanet_fetch_user_answers((int) $profile['id']);

$questionMarkup = '';
foreach ($userQuestions as $question) {
    $questionMarkup .= '<div class="question-card">';
    $questionMarkup .= '<div class="question-content">';
    $questionMarkup .= '<div class="question-stats">';
    $questionMarkup .= '<div class="stat-item-card"><i data-lucide="message-square"></i><span class="stat-item-value">' . (int) $question['answers'] . '</span><span class="stat-item-label">answers</span></div>';
    $questionMarkup .= '<div class="stat-item-card"><i data-lucide="eye"></i><span class="stat-item-value">' . (int) $question['views'] . '</span><span class="stat-item-label">views</span></div>';
    $questionMarkup .= '</div>';
    $questionMarkup .= '<div class="question-main">';
    $questionMarkup .= '<a href="./question.php?id=' . (int) $question['id'] . '" class="question-title">' . htmlspecialchars((string) $question['title'], ENT_QUOTES, 'UTF-8') . '</a>';
    $questionMarkup .= '<p class="question-description">' . htmlspecialchars((string) $question['description'], ENT_QUOTES, 'UTF-8') . '</p>';
    $questionMarkup .= '<div class="question-tags"><span class="tag">' . htmlspecialchars((string) $question['subject'], ENT_QUOTES, 'UTF-8') . '</span></div>';
    $questionMarkup .= '<div class="question-meta"><div class="meta-left"><span>asked by <span class="author">' . htmlspecialchars((string) $profile['name'], ENT_QUOTES, 'UTF-8') . '</span></span><span>' . htmlspecialchars((string) $question['timeAgo'], ENT_QUOTES, 'UTF-8') . '</span></div><div class="meta-right"><i data-lucide="eye"></i><span>' . (int) $question['views'] . ' views</span></div></div>';
    $questionMarkup .= '</div></div></div>';
}

if ($questionMarkup === '') {
    $questionMarkup = '<div class="empty-state"><i data-lucide="help-circle"></i><p>No questions posted yet</p></div>';
}

$answerMarkup = '';
foreach ($userAnswers as $answer) {
    // Render answers using the same visual card as questions for consistency
    $answerMarkup .= '<div class="question-card">';
    $answerMarkup .= '<div class="question-content">';
    // omit the left stat column for answers so the main content takes full width
    $answerMarkup .= '<div class="question-main">';
    $answerMarkup .= '<a href="./question.php?id=' . (int) $answer['question_id'] . '" class="question-title">' . htmlspecialchars((string) $answer['question_title'], ENT_QUOTES, 'UTF-8') . '</a>';
    $answerMarkup .= '<p class="question-description">In response — ' . htmlspecialchars((string) $answer['body'], ENT_QUOTES, 'UTF-8') . '</p>';
    $answerMarkup .= '<div class="question-tags"><span class="tag">' . htmlspecialchars((string) $answer['subject'], ENT_QUOTES, 'UTF-8') . '</span></div>';
    $answerMarkup .= '<div class="question-meta"><div class="meta-left"><span>answered by <span class="author">' . htmlspecialchars((string) $profile['name'], ENT_QUOTES, 'UTF-8') . '</span></span><span>' . htmlspecialchars((string) $answer['timeAgo'], ENT_QUOTES, 'UTF-8') . '</span></div><div class="meta-right"><a href="./question.php?id=' . (int) $answer['question_id'] . '" class="profile-answer-link">Open thread</a></div></div>';
    $answerMarkup .= '</div></div></div>';
}

if ($answerMarkup === '') {
    $answerMarkup = '<div class="empty-state"><i data-lucide="message-square"></i><p>No answers posted yet</p></div>';
}

$joined = isset($profile['created_at']) ? 'Joined ' . aulanet_format_time_ago((string) $profile['created_at']) : 'Joined recently';
$activityPoints = (int) $profile['questions_count'] + (int) $profile['answers_count'] + (int) $profile['views_count'];

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

$aulanetProfileName = htmlspecialchars((string) $profile['name'], ENT_QUOTES, 'UTF-8');
$aulanetProfileEmail = htmlspecialchars((string) $profile['email'], ENT_QUOTES, 'UTF-8');
$aulanetProfileRole = 'Role: ' . htmlspecialchars((string) $profile['role'], ENT_QUOTES, 'UTF-8');
$aulanetProfileJoined = htmlspecialchars($joined, ENT_QUOTES, 'UTF-8');
$aulanetProfileActivity = (string) $activityPoints . ' activity points';

$replacements = [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    './home.php' => './home.php',
    './create-question.php' => './create-question.php',
    './question.php' => './question.php',
    '<!-- PROFILE_NAME -->' => $aulanetProfileName,
    '<!-- PROFILE_EMAIL -->' => $aulanetProfileEmail,
    '<!-- PROFILE_ROLE -->' => $aulanetProfileRole,
    '<!-- PROFILE_JOINED -->' => $aulanetProfileJoined,
    '<!-- PROFILE_ACTIVITY -->' => $aulanetProfileActivity,
    '<!-- PROFILE_QUESTIONS_COUNT -->' => (string) (int) $profile['questions_count'],
    '<!-- PROFILE_ANSWERS_COUNT -->' => (string) (int) $profile['answers_count'],
    '<!-- PROFILE_VIEWS_COUNT -->' => (string) (int) $profile['views_count'],
    '<!-- PROFILE_QUESTIONS -->' => $questionMarkup,
    '<!-- PROFILE_ANSWERS -->' => $answerMarkup,
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
];

aulanet_render_template(__DIR__ . '/profile.html', $replacements);
