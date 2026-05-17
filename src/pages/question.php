<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';

$questions = [
    1 => [
        'title' => 'How to implement binary search trees in Java?',
        'description' => 'I\'m working on my Data Structures assignment and need help understanding how to implement a balanced binary search tree. Can someone explain the rotation operations?',
        'tags' => ['Data Structures', 'Java', 'Algorithms'],
        'author' => 'Ana Silva',
        'timeAgo' => '5d ago',
        'votes' => 15,
        'answers' => 4,
        'views' => 234,
    ],
    2 => [
        'title' => 'Best practices for database normalization?',
        'description' => 'What are the main principles to follow when normalizing a database schema? I\'m struggling with 3NF and BCNF.',
        'tags' => ['Database', 'SQL', 'Design'],
        'author' => 'Carlos Mendes',
        'timeAgo' => '6d ago',
        'votes' => 23,
        'answers' => 7,
        'views' => 456,
    ],
    3 => [
        'title' => 'Understanding RESTful API design principles',
        'description' => 'I\'m building my first REST API for a course project. What are the key principles I should follow for proper resource naming and HTTP method usage?',
        'tags' => ['Web Development', 'API', 'REST'],
        'author' => 'Joao Santos',
        'timeAgo' => '7d ago',
        'votes' => 18,
        'answers' => 5,
        'views' => 312,
    ],
    4 => [
        'title' => 'Time complexity of recursive algorithms',
        'description' => 'Can someone help me understand how to calculate the time complexity of recursive algorithms using the Master Theorem?',
        'tags' => ['Algorithms', 'Complexity', 'Theory'],
        'author' => 'Ana Silva',
        'timeAgo' => '8d ago',
        'votes' => 12,
        'answers' => 3,
        'views' => 189,
    ],
    5 => [
        'title' => 'React hooks vs class components',
        'description' => 'What are the advantages of using hooks over class components in React? When should I prefer one over the other?',
        'tags' => ['React', 'JavaScript', 'Web Development'],
        'author' => 'Carlos Mendes',
        'timeAgo' => '9d ago',
        'votes' => 31,
        'answers' => 9,
        'views' => 678,
    ],
];

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

$questionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$question = $questions[$questionId] ?? $questions[1];

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Question - AulaNet</title>
    <link rel="stylesheet" href="../styles/home.css">
    <link rel="stylesheet" href="../styles/common.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .question-detail-layout {
            display: grid;
            gap: 1.5rem;
            max-width: 960px;
            margin: 0 auto;
        }

        .question-detail-card,
        .answer-card {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
        }

        .question-detail-header {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .question-detail-title {
            font-size: 1.75rem;
            font-weight: 600;
            color: #111827;
            line-height: 1.2;
        }

        .question-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .question-detail-description {
            color: #374151;
            line-height: 1.7;
            margin-bottom: 1rem;
        }

        .answer-placeholder {
            color: #4b5563;
            line-height: 1.6;
        }

        .answers-heading {
            font-size: 1.25rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.75rem;
        }
    </style>
</head>

<body>
    <?php echo $nav; ?>

    <div class="main-container">
        <div class="question-detail-layout">
            <article class="question-detail-card">
                <div class="question-detail-header">
                    <div>
                        <h1 class="question-detail-title"><?php echo htmlspecialchars($question['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
                        <div class="question-detail-meta">
                            <span>asked by <strong><?php echo htmlspecialchars($question['author'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                            <span><?php echo htmlspecialchars($question['timeAgo'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <span><?php echo (int) $question['views']; ?> views</span>
                        </div>
                    </div>
                    <div class="question-count"><?php echo (int) $question['votes']; ?> votes</div>
                </div>

                <p class="question-detail-description"><?php echo htmlspecialchars($question['description'], ENT_QUOTES, 'UTF-8'); ?></p>

                <div class="question-tags">
                    <?php foreach ($question['tags'] as $tag) : ?>
                        <span class="tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>
            </article>

            <section class="answer-card">
                <h2 class="answers-heading">Answers</h2>
                <p class="answer-placeholder">This PHP route is now wired and ready for a real question detail backend.</p>
            </section>
        </div>
    </div>

    <?php echo $footer; ?>

</body>

</html>