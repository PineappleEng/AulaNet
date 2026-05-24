<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/session.php';

$questionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($questionId <= 0) {
    http_response_code(404);
    echo 'Question not found.';
    exit;
}

$question = aulanet_fetch_question_by_id($questionId);
if ($question === null) {
    http_response_code(404);
    echo 'Question not found.';
    exit;
}

$currentUser = aulanet_get_user();
$answers = aulanet_fetch_question_answers($questionId, $currentUser !== null ? (int) $currentUser['id'] : null);
$answerIds = array_column($answers, 'id');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) filter_input(INPUT_POST, 'action');

    if ($action === 'edit_question') {
        if (!aulanet_is_logged_in() || (aulanet_user_role() ?? '') !== 'admin') {
            header('Location: ./login.php');
            exit;
        }

        $questionTitleInput = trim((string) filter_input(INPUT_POST, 'edit_title'));
        $questionBodyInput = trim((string) filter_input(INPUT_POST, 'edit_body'));

        if ($questionTitleInput !== '' && $questionBodyInput !== '' && aulanet_update_question($questionId, $questionTitleInput, $questionBodyInput)) {
            header('Location: ./question.php?id=' . $questionId . '&edited=question');
            exit;
        }

        header('Location: ./question.php?id=' . $questionId . '&edit_error=1');
        exit;
    }

    if ($action === 'edit_answer') {
        if (!aulanet_is_logged_in() || (aulanet_user_role() ?? '') !== 'admin') {
            header('Location: ./login.php');
            exit;
        }

        $answerId = filter_input(INPUT_POST, 'answer_id', FILTER_VALIDATE_INT);
        $answerBodyInput = trim((string) filter_input(INPUT_POST, 'edit_body'));

        if ($answerId !== false && $answerId !== null && $answerBodyInput !== '' && aulanet_update_answer((int) $answerId, $answerBodyInput)) {
            header('Location: ./question.php?id=' . $questionId . '&edited=answer');
            exit;
        }

        header('Location: ./question.php?id=' . $questionId . '&edit_error=1');
        exit;
    }

    if ($action === 'vote_answer') {
        if (!aulanet_is_logged_in()) {
            header('Location: ./login.php');
            exit;
        }

        $answerId = filter_input(INPUT_POST, 'answer_id', FILTER_VALIDATE_INT);
        $voteValue = filter_input(INPUT_POST, 'vote_value', FILTER_VALIDATE_INT);
        $currentUser = aulanet_get_user();

        if (
            $answerId !== false &&
            $answerId !== null &&
            $voteValue !== false &&
            $voteValue !== null &&
            $currentUser !== null &&
            aulanet_vote_answer((int) $answerId, (int) $currentUser['id'], (int) $voteValue)
        ) {
            header('Location: ./question.php?id=' . $questionId . '&voted=1');
            exit;
        }

        header('Location: ./question.php?id=' . $questionId . '&vote_error=1');
        exit;
    }

    if ($action === 'answer_post') {
        if (!aulanet_is_logged_in()) {
            header('Location: ./login.php');
            exit;
        }

        $answerBody = trim((string) filter_input(INPUT_POST, 'answer_body'));
        $currentUser = aulanet_get_user();

        if ($currentUser !== null && aulanet_create_answer($questionId, (int) $currentUser['id'], $answerBody) !== null) {
            header('Location: ./question.php?id=' . $questionId . '&answered=1');
            exit;
        }

        header('Location: ./question.php?id=' . $questionId . '&answer_error=1');
        exit;
    }

    if ($action === 'report') {
        if (!aulanet_is_logged_in()) {
            header('Location: ./login.php');
            exit;
        }

        $reportReason = trim((string) filter_input(INPUT_POST, 'report_reason'));
        $reportTargetType = (string) filter_input(INPUT_POST, 'report_target_type');
        $reportTargetId = filter_input(INPUT_POST, 'report_target_id', FILTER_VALIDATE_INT);
        $currentUser = aulanet_get_user();

        if ($currentUser !== null && $reportTargetId !== false && $reportTargetId !== null) {
            $reportSucceeded = false;

            if ($reportTargetType === 'question' && (int) $reportTargetId === $questionId) {
                $reportSucceeded = aulanet_report_question((int) $currentUser['id'], $questionId, $reportReason !== '' ? $reportReason : null);
            } elseif ($reportTargetType === 'answer' && in_array((int) $reportTargetId, $answerIds, true)) {
                $reportSucceeded = aulanet_report_answer((int) $currentUser['id'], (int) $reportTargetId, $reportReason !== '' ? $reportReason : null);
            }

            if ($reportSucceeded) {
                header('Location: ./question.php?id=' . $questionId . '&reported=1');
                exit;
            }
        }

        header('Location: ./question.php?id=' . $questionId . '&error=1');
        exit;
    }

    header('Location: ./question.php?id=' . $questionId);
    exit;
}

aulanet_increment_question_views($questionId);
$question['views'] = (int) $question['views'] + 1;

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

$questionTitle = htmlspecialchars($question['title'], ENT_QUOTES, 'UTF-8');
$questionBody = nl2br(htmlspecialchars($question['description'], ENT_QUOTES, 'UTF-8'));
$authorName = htmlspecialchars($question['author'], ENT_QUOTES, 'UTF-8');
$tags = !empty($question['tags']) && is_array($question['tags']) ? $question['tags'] : [$question['subject']];
$questionMeta = htmlspecialchars($question['timeAgo'], ENT_QUOTES, 'UTF-8');
$answerCount = count($answers);
$reportSent = isset($_GET['reported']) && $_GET['reported'] === '1';
$reportError = isset($_GET['error']) && $_GET['error'] === '1';
$answerSent = isset($_GET['answered']) && $_GET['answered'] === '1';
$answerError = isset($_GET['answer_error']) && $_GET['answer_error'] === '1';
$voteSent = isset($_GET['voted']) && $_GET['voted'] === '1';
$voteError = isset($_GET['vote_error']) && $_GET['vote_error'] === '1';
$editSent = isset($_GET['edited']);
$editError = isset($_GET['edit_error']) && $_GET['edit_error'] === '1';
$canReport = $currentUser !== null;
$canAnswer = $currentUser !== null;
$canVote = $currentUser !== null;
$isAdmin = $currentUser !== null && ($currentUser['role'] ?? '') === 'admin';

$answerMarkup = '';
foreach ($answers as $answer) {
    $acceptedBadge = $answer['accepted'] ? '<span class="accepted-badge">Accepted</span>' : '';
    $answerTitle = htmlspecialchars('Answer by ' . $answer['author'], ENT_QUOTES, 'UTF-8');
    $voteScore = (int) $answer['score'];
    $viewerVote = (int) $answer['viewer_vote'];
    $isOwnAnswer = $currentUser !== null && (int) $currentUser['id'] === (int) $answer['author_id'];
    $voteControls = '<div class="answer-vote-box"><div class="answer-vote-score">' . $voteScore . '</div>';

    if ($canVote && !$isOwnAnswer) {
        $voteControls .= '<form method="post" action="./question.php?id=' . $questionId . '" class="answer-vote-form">';
        $voteControls .= '<input type="hidden" name="action" value="vote_answer">';
        $voteControls .= '<input type="hidden" name="answer_id" value="' . (int) $answer['id'] . '">';
        $voteControls .= '<input type="hidden" name="vote_value" value="1">';
        $voteControls .= '<button type="submit" class="answer-vote-button ' . ($viewerVote === 1 ? 'active up' : 'up') . '" aria-label="Upvote answer"><i data-lucide="chevron-up"></i></button>';
        $voteControls .= '</form>';
        $voteControls .= '<form method="post" action="./question.php?id=' . $questionId . '" class="answer-vote-form">';
        $voteControls .= '<input type="hidden" name="action" value="vote_answer">';
        $voteControls .= '<input type="hidden" name="answer_id" value="' . (int) $answer['id'] . '">';
        $voteControls .= '<input type="hidden" name="vote_value" value="-1">';
        $voteControls .= '<button type="submit" class="answer-vote-button ' . ($viewerVote === -1 ? 'active down' : 'down') . '" aria-label="Downvote answer"><i data-lucide="chevron-down"></i></button>';
        $voteControls .= '</form>';
    } else {
        $voteControls .= '<div class="answer-vote-hint">' . ($isOwnAnswer ? 'Your answer' : 'Sign in to vote') . '</div>';
    }

    $voteControls .= '</div>';
    $answerMarkup .= '<article class="answer-card">';
    $answerMarkup .= '<div class="answer-header">';
    $answerMarkup .= '<div class="answer-header-main"><strong>' . htmlspecialchars($answer['author'], ENT_QUOTES, 'UTF-8') . '</strong><span>' . htmlspecialchars($answer['timeAgo'], ENT_QUOTES, 'UTF-8') . '</span>' . $acceptedBadge . '</div>';
    $answerMarkup .= '<div class="answer-header-actions">' . $voteControls . '<button type="button" class="question-menu-button answer-report-button" aria-label="Report answer" data-report-target-type="answer" data-report-target-id="' . (int) $answer['id'] . '" data-report-title="' . $answerTitle . '"><i data-lucide="flag"></i></button></div>';
    $answerMarkup .= '</div>';
    $answerMarkup .= '<p class="answer-body">' . nl2br(htmlspecialchars($answer['body'], ENT_QUOTES, 'UTF-8')) . '</p>';
    if ($isAdmin) {
        $answerMarkup .= '<details class="admin-edit-panel"><summary>Edit answer</summary><form method="post" action="./question.php?id=' . $questionId . '" class="admin-edit-form"><input type="hidden" name="action" value="edit_answer"><input type="hidden" name="answer_id" value="' . (int) $answer['id'] . '"><label class="admin-edit-label" for="editAnswerBody' . (int) $answer['id'] . '">Answer body</label><textarea id="editAnswerBody' . (int) $answer['id'] . '" name="edit_body" class="admin-edit-textarea" required>' . htmlspecialchars((string) $answer['body'], ENT_QUOTES, 'UTF-8') . '</textarea><div class="admin-edit-actions"><button type="submit" class="admin-edit-button">Save changes</button></div></form></details>';
    }
    $answerMarkup .= '</article>';
}

if ($answerMarkup === '') {
    $answerMarkup = '<div class="answer-card"><p class="answer-placeholder">No answers have been posted yet.</p></div>';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question - AulaNet</title>
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/common.css">
    <link rel="stylesheet" href="../styles/question.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <?php echo $nav; ?>

    <div class="main-container">
        <div class="question-detail-layout">
            <article class="question-detail-card">
                <?php if ($reportSent) : ?>
                    <div class="question-alert success">Thanks. The question was reported and sent to moderation.</div>
                <?php elseif ($reportError) : ?>
                    <div class="question-alert error">The report could not be submitted. Please try again.</div>
                <?php elseif ($answerSent) : ?>
                    <div class="question-alert success">Your answer was posted successfully.</div>
                <?php elseif ($answerError) : ?>
                    <div class="question-alert error">The answer could not be posted. Please try again.</div>
                <?php elseif ($voteSent) : ?>
                    <div class="question-alert info">Your vote was saved.</div>
                <?php elseif ($voteError) : ?>
                    <div class="question-alert error">The vote could not be saved. Please try again.</div>
                <?php elseif ($editSent) : ?>
                    <div class="question-alert neutral">The publication was updated successfully.</div>
                <?php elseif ($editError) : ?>
                    <div class="question-alert error">The publication could not be updated. Please try again.</div>
                <?php endif; ?>

                <div class="question-detail-header">
                    <div>
                        <h1 class="question-detail-title"><?php echo $questionTitle; ?></h1>
                        <div class="question-detail-meta">
                            <span>asked by <strong><?php echo $authorName; ?></strong></span>
                            <span><?php echo $questionMeta; ?></span>
                            <span><?php echo (int) $question['views']; ?> views</span>
                        </div>
                    </div>
                    <div class="question-header-actions">
                        <div class="question-count"><?php echo $answerCount; ?> answers</div>
                        <button type="button" class="question-menu-button" id="questionMenuButton" aria-label="Report question" data-report-target-type="question" data-report-target-id="<?php echo (int) $question['id']; ?>" data-report-title="<?php echo $questionTitle; ?>">
                            <i data-lucide="flag"></i>
                        </button>
                    </div>
                </div>

                <p class="question-detail-description"><?php echo $questionBody; ?></p>

                <div class="question-tags">
                    <?php foreach ($tags as $tag) : ?>
                        <span class="tag"><?php echo htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($isAdmin) : ?>
                    <details class="admin-edit-panel mt-1">
                        <summary>Edit question</summary>
                        <form method="post" action="./question.php?id=<?php echo (int) $question['id']; ?>" class="admin-edit-form">
                            <input type="hidden" name="action" value="edit_question">
                            <label class="admin-edit-label" for="editQuestionTitle">Question title</label>
                            <input id="editQuestionTitle" name="edit_title" class="admin-edit-textarea min-height-auto" value="<?php echo htmlspecialchars($question['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                            <label class="admin-edit-label" for="editQuestionBody">Question body</label>
                            <textarea id="editQuestionBody" name="edit_body" class="admin-edit-textarea" required><?php echo htmlspecialchars($question['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            <div class="admin-edit-actions">
                                <button type="submit" class="admin-edit-button">Save changes</button>
                            </div>
                        </form>
                    </details>
                <?php endif; ?>

            </article>

                    <section class="answer-compose">
                <div class="answer-compose-header">
                    <h2 class="answer-compose-title">Write an answer</h2>
                    <?php if (!$canAnswer) : ?>
                        <span class="question-alert error alert-inline">Sign in to answer.</span>
                    <?php endif; ?>
                </div>

                <?php if ($canAnswer) : ?>
                    <form method="post" action="./question.php?id=<?php echo (int) $question['id']; ?>">
                        <input type="hidden" name="action" value="answer_post">
                        <textarea name="answer_body" class="answer-compose-textarea" placeholder="Write your answer here" required></textarea>
                        <div class="answer-compose-actions">
                            <button type="submit" class="answer-compose-button">Post answer</button>
                        </div>
                    </form>
                <?php else : ?>
                    <p class="answer-placeholder">You need to sign in before posting an answer.</p>
                    <a class="answer-compose-button signin-inline no-decoration" href="./login.php">Sign in to answer</a>
                <?php endif; ?>
            </section>

            <div class="report-modal hidden" id="questionReportModal">
                <div class="report-modal-backdrop" data-report-close></div>
                <div class="report-modal-panel" role="dialog" aria-modal="true" aria-labelledby="reportModalTitle">
                    <div class="report-modal-header">
                        <div>
                            <p class="report-modal-kicker" id="reportModalKicker">Report question</p>
                            <h3 id="reportModalTitle" class="report-modal-title">Send a moderation report</h3>
                        </div>
                        <button type="button" class="report-modal-close" data-report-close aria-label="Close report dialog">×</button>
                    </div>

                    <?php if ($canReport) : ?>
                        <form method="post" action="./question.php?id=<?php echo (int) $question['id']; ?>" class="report-modal-form" id="reportModalForm">
                            <input type="hidden" name="action" value="report">
                            <input type="hidden" name="report_target_type" id="reportTargetType" value="question">
                            <input type="hidden" name="report_target_id" id="reportTargetId" value="<?php echo (int) $question['id']; ?>">
                            <p class="report-modal-question" id="reportQuestionTitle"><?php echo $questionTitle; ?></p>
                            <label class="report-modal-label" for="reportReasonTextarea">Reason</label>
                            <textarea id="reportReasonTextarea" name="report_reason" class="report-modal-textarea" placeholder="Tell us what is wrong with this question" required></textarea>
                            <div class="report-modal-actions">
                                <button type="button" class="report-modal-secondary" data-report-close>Cancel</button>
                                <button type="submit" class="report-modal-primary"><i data-lucide="flag"></i>Submit report</button>
                            </div>
                        </form>
                    <?php else : ?>
                        <p class="report-modal-question">Sign in to report content for moderation.</p>
                        <div class="report-modal-actions">
                            <button type="button" class="report-modal-secondary" data-report-close>Close</button>
                            <a class="report-modal-primary no-decoration" href="./login.php">Sign in</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <section class="answer-card">
                <h2 class="answers-heading">Answers</h2>
                <div class="answer-list">
                    <?php echo $answerMarkup; ?>
                </div>
            </section>
        </div>
    </div>

    <?php echo $footer; ?>

    <script>
        lucide.createIcons();

        const menuButton = document.getElementById('questionMenuButton');
        const reportModal = document.getElementById('questionReportModal');
        const reportModalKicker = document.getElementById('reportModalKicker');
        const reportQuestionTitle = document.getElementById('reportQuestionTitle');
        const reportTargetType = document.getElementById('reportTargetType');
        const reportTargetId = document.getElementById('reportTargetId');
        const reportReasonTextarea = document.getElementById('reportReasonTextarea');
        const defaultQuestionReportId = <?php echo (int) $question['id']; ?>;
        const defaultQuestionReportTitle = <?php echo json_encode($question['title'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

        function openReportModal(targetType = 'question', targetId = defaultQuestionReportId, title = defaultQuestionReportTitle) {
            if (!reportModal) {
                return;
            }

            if (reportModalKicker) {
                reportModalKicker.textContent = targetType === 'answer' ? 'Report answer' : 'Report question';
            }

            if (reportQuestionTitle) {
                reportQuestionTitle.textContent = title;
            }

            if (reportTargetType) {
                reportTargetType.value = targetType;
            }

            if (reportTargetId) {
                reportTargetId.value = String(targetId);
            }

            reportModal.classList.remove('hidden');
            if (reportReasonTextarea) {
                reportReasonTextarea.value = '';
                reportReasonTextarea.focus();
            }
        }

        function closeReportModal() {
            if (reportModal) {
                reportModal.classList.add('hidden');
            }
        }

        if (menuButton) {
            menuButton.addEventListener('click', () => {
                openReportModal('question', menuButton.dataset.reportTargetId || defaultQuestionReportId, menuButton.dataset.reportTitle || defaultQuestionReportTitle);
            });
        }

        document.querySelectorAll('.answer-report-button').forEach((button) => {
            button.addEventListener('click', () => {
                openReportModal(
                    button.dataset.reportTargetType || 'answer',
                    button.dataset.reportTargetId || '',
                    button.dataset.reportTitle || 'Answer'
                );
            });
        });

        document.addEventListener('click', (event) => {
            const target = event.target;
            if (!(target instanceof Element)) {
                return;
            }

            if (target.closest('[data-report-close]')) {
                closeReportModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeReportModal();
            }
        });
    </script>

</body>

</html>
