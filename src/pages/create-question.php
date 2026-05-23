<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Only authenticated students (or admins) may create questions
aulanet_require_login();

$subjects = aulanet_fetch_subjects();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim((string) filter_input(INPUT_POST, 'title'));
    $body = trim((string) filter_input(INPUT_POST, 'body'));
    $subjectId = filter_input(INPUT_POST, 'subject_id', FILTER_VALIDATE_INT);
    $tagsJson = (string) filter_input(INPUT_POST, 'tags_json');
    $tags = json_decode($tagsJson, true);

    if (!is_array($tags)) {
        $tags = [];
    }

    $tags = array_values(array_filter(array_map(static function ($tag): string {
        return is_string($tag) ? trim($tag) : '';
    }, $tags)));

    if ($title === '' || $body === '' || $subjectId === false || $subjectId === null || $tags === []) {
        header('Location: ./create-question.php?error=1');
        exit;
    }

    $questionId = aulanet_create_question((int) aulanet_get_user()['id'], $title, $body, (int) $subjectId, $tags);
    if ($questionId === null) {
        header('Location: ./create-question.php?error=1');
        exit;
    }

    header('Location: ./question.php?id=' . $questionId);
    exit;
}

$subjectButtons = '';
foreach ($subjects as $subject) {
    $subjectButtons .= sprintf(
        '<button type="button" class="tag-btn subject-btn" data-subject-id="%d" data-subject-name="%s">%s</button>',
        (int) $subject['id'],
        htmlspecialchars((string) $subject['name'], ENT_QUOTES, 'UTF-8'),
        htmlspecialchars((string) $subject['name'], ENT_QUOTES, 'UTF-8')
    );
}

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

aulanet_render_template(__DIR__ . '/create-question.html', [
    './home.html' => './home.php',
    './create-question.html' => './create-question.php',
    './profile.html' => './profile.php',
    '../index.html' => '../index.php',
    './home.php' => './home.php',
    './profile.php' => './profile.php',
    './question.php' => './question.php',
    '<!-- SUBJECT_BUTTONS -->' => $subjectButtons,
    '<!-- AUTH ERROR -->' => isset($_GET['error']) ? '<div class="auth-error">Could not post the question. Check the form and try again.</div>' : '',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
]);