<?php
// Helper de conexión PDO para AulaNet
// Colocar los datos de conexión en un archivo seguro o variables de entorno

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'aulanet');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function get_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        aulanet_ensure_question_tags_column($pdo);
        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'Database connection error.';
        exit;
    }
}

function aulanet_role_name_from_id(int $role_id): string
{
    return match ($role_id) {
        1 => 'admin',
        default => 'student',
    };
}

function aulanet_subject_fallbacks(): array
{
    return [
        ['id' => 1, 'name' => 'Data Structures', 'slug' => 'data-structures'],
        ['id' => 2, 'name' => 'Algorithms', 'slug' => 'algorithms'],
        ['id' => 3, 'name' => 'Database', 'slug' => 'database'],
        ['id' => 4, 'name' => 'Web Development', 'slug' => 'web-development'],
        ['id' => 5, 'name' => 'Software Engineering', 'slug' => 'software-engineering'],
        ['id' => 6, 'name' => 'Operating Systems', 'slug' => 'operating-systems'],
        ['id' => 7, 'name' => 'Networks', 'slug' => 'networks'],
        ['id' => 8, 'name' => 'Security', 'slug' => 'security'],
    ];
}

function aulanet_fetch_subjects(): array
{
    $pdo = get_db();
    $stmt = $pdo->query('SELECT id, name, slug FROM subjects ORDER BY name ASC');
    $subjects = $stmt->fetchAll();

    if ($subjects === []) {
        $insert = $pdo->prepare('INSERT INTO subjects (name, slug) VALUES (:name, :slug)');
        foreach (aulanet_subject_fallbacks() as $subject) {
            try {
                $insert->execute([
                    ':name' => $subject['name'],
                    ':slug' => $subject['slug'],
                ]);
            } catch (PDOException) {
                // Ignore duplicate or constraint errors; the follow-up query will return whatever is available.
            }
        }

        $stmt = $pdo->query('SELECT id, name, slug FROM subjects ORDER BY name ASC');
        $subjects = $stmt->fetchAll();
    }

    return $subjects !== [] ? $subjects : aulanet_subject_fallbacks();
}

function aulanet_format_time_ago(?string $datetime): string
{
    if ($datetime === null || $datetime === '') {
        return 'just now';
    }

    try {
        $createdAt = new DateTimeImmutable($datetime);
        $now = new DateTimeImmutable('now');
        $diff = $now->diff($createdAt);

        if ($diff->days === 0) {
            if ($diff->h === 0) {
                return $diff->i <= 1 ? 'just now' : $diff->i . 'm ago';
            }

            return $diff->h . 'h ago';
        }

        return $diff->days . 'd ago';
    } catch (Throwable) {
        return 'just now';
    }
}

function aulanet_ensure_question_tags_column(PDO $pdo): void
{
    static $checked = false;

    if ($checked) {
        return;
    }

    $checked = true;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'tags_json'");
        if ($stmt->fetch() === false) {
            $pdo->exec('ALTER TABLE questions ADD COLUMN tags_json TEXT DEFAULT NULL AFTER body');
        }
    } catch (PDOException) {
        // If the migration cannot run, the app keeps working with the legacy single-subject flow.
    }
}

function aulanet_decode_question_tags(?string $tagsJson, ?string $fallbackTag = null): array
{
    if ($tagsJson !== null && $tagsJson !== '') {
        $decoded = json_decode($tagsJson, true);
        if (is_array($decoded)) {
            $tags = array_values(array_filter(array_map(static function ($tag): string {
                if (is_string($tag)) {
                    return trim($tag);
                }

                return '';
            }, $decoded)));

            if ($tags !== []) {
                return $tags;
            }
        }
    }

    return $fallbackTag !== null && $fallbackTag !== '' ? [$fallbackTag] : [];
}

function aulanet_fetch_recent_questions(int $limit = 10): array
{
    $pdo = get_db();
    $limit = max(1, $limit);
    $sql = "
        SELECT
            q.id,
            q.title,
            q.body,
            q.created_at,
            q.updated_at,
            COALESCE(u.display_name, 'Anonymous') AS author_name,
            COALESCE(u.id, 0) AS author_id,
            COALESCE(s.id, 0) AS subject_id,
            COALESCE(s.name, 'General') AS subject_name,
            q.tags_json,
            COALESCE(qv.views, 0) AS views_count,
            (
                SELECT COUNT(*)
                FROM answers a
                WHERE a.question_id = q.id
                  AND a.is_active = 1
                  AND a.deleted_at IS NULL
            ) AS answers_count
        FROM questions q
        LEFT JOIN users u ON u.id = q.user_id
        LEFT JOIN subjects s ON s.id = q.subject_id
        LEFT JOIN question_views qv ON qv.question_id = q.id
        WHERE q.is_active = 1
          AND q.deleted_at IS NULL
        ORDER BY q.created_at DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->query($sql);
    $questions = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        $subjectName = (string) ($row['subject_name'] ?? 'General');
        $tags = aulanet_decode_question_tags($row['tags_json'] ?? null, $subjectName);

        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'description' => (string) $row['body'],
            'subject_id' => (int) ($row['subject_id'] ?? 0),
            'subject' => $subjectName,
            'tags' => $tags,
            'author' => (string) ($row['author_name'] ?? 'Anonymous'),
            'created_at' => (string) $row['created_at'],
            'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
            'answers' => (int) ($row['answers_count'] ?? 0),
            'views' => (int) ($row['views_count'] ?? 0),
        ];
    }, $questions);
}

function aulanet_fetch_question_by_id(int $question_id): ?array
{
    $pdo = get_db();
    $sql = "
        SELECT
            q.id,
            q.title,
            q.body,
            q.created_at,
            q.updated_at,
            COALESCE(u.display_name, 'Anonymous') AS author_name,
            COALESCE(u.id, 0) AS author_id,
            COALESCE(s.id, 0) AS subject_id,
            COALESCE(s.name, 'General') AS subject_name,
            q.tags_json,
            COALESCE(qv.views, 0) AS views_count,
            (
                SELECT COUNT(*)
                FROM answers a
                WHERE a.question_id = q.id
                  AND a.is_active = 1
                  AND a.deleted_at IS NULL
            ) AS answers_count
        FROM questions q
        LEFT JOIN users u ON u.id = q.user_id
        LEFT JOIN subjects s ON s.id = q.subject_id
        LEFT JOIN question_views qv ON qv.question_id = q.id
        WHERE q.id = :id
          AND q.is_active = 1
          AND q.deleted_at IS NULL
        LIMIT 1
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $question_id]);
    $row = $stmt->fetch();

    if ($row === false) {
        return null;
    }

    $subjectName = (string) ($row['subject_name'] ?? 'General');
    $tags = aulanet_decode_question_tags($row['tags_json'] ?? null, $subjectName);

    return [
        'id' => (int) $row['id'],
        'title' => (string) $row['title'],
        'description' => (string) $row['body'],
        'subject_id' => (int) ($row['subject_id'] ?? 0),
        'subject' => $subjectName,
        'tags' => $tags,
        'author' => (string) ($row['author_name'] ?? 'Anonymous'),
        'created_at' => (string) $row['created_at'],
        'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
        'answers' => (int) ($row['answers_count'] ?? 0),
        'views' => (int) ($row['views_count'] ?? 0),
    ];
}

function aulanet_fetch_question_answers(int $question_id, ?int $viewer_user_id = null): array
{
    $pdo = get_db();
    $sql = "
        SELECT
            a.id,
            a.user_id,
            a.body,
            a.created_at,
            COALESCE(u.display_name, 'Anonymous') AS author_name,
            a.is_accepted,
            COALESCE((
                SELECT SUM(av.value)
                FROM answer_votes av
                WHERE av.answer_id = a.id
            ), 0) AS vote_score,
            COALESCE((
                SELECT av.value
                FROM answer_votes av
                WHERE av.answer_id = a.id
                  AND av.user_id = :viewer_user_id
                LIMIT 1
            ), 0) AS viewer_vote
        FROM answers a
        LEFT JOIN users u ON u.id = a.user_id
        WHERE a.question_id = :id
          AND a.is_active = 1
          AND a.deleted_at IS NULL
        ORDER BY a.is_accepted DESC, a.created_at ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':id' => $question_id,
        ':viewer_user_id' => $viewer_user_id,
    ]);
    $answers = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'author_id' => (int) ($row['user_id'] ?? 0),
            'body' => (string) $row['body'],
            'author' => (string) ($row['author_name'] ?? 'Anonymous'),
            'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
            'accepted' => (bool) ($row['is_accepted'] ?? 0),
            'score' => (int) ($row['vote_score'] ?? 0),
            'viewer_vote' => (int) ($row['viewer_vote'] ?? 0),
        ];
    }, $answers);
}

function aulanet_create_answer(int $question_id, int $user_id, string $body): ?int
{
    $pdo = get_db();
    $body = trim($body);

    if ($body === '') {
        return null;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO answers (question_id, user_id, body) VALUES (:question_id, :user_id, :body)');
        $stmt->execute([
            ':question_id' => $question_id,
            ':user_id' => $user_id,
            ':body' => $body,
        ]);

        return (int) $pdo->lastInsertId();
    } catch (PDOException) {
        return null;
    }
}

function aulanet_vote_answer(int $answer_id, int $user_id, int $value): bool
{
    if (!in_array($value, [1, -1], true)) {
        return false;
    }

    $pdo = get_db();

    $answerStmt = $pdo->prepare(
        'SELECT a.id, a.user_id, a.is_active, a.deleted_at, q.is_active AS question_active, q.deleted_at AS question_deleted
         FROM answers a
         INNER JOIN questions q ON q.id = a.question_id
         WHERE a.id = :answer_id
         LIMIT 1'
    );
    $answerStmt->execute([':answer_id' => $answer_id]);
    $answer = $answerStmt->fetch();

    if ($answer === false) {
        return false;
    }

    if ((int) ($answer['is_active'] ?? 0) !== 1 || $answer['deleted_at'] !== null || (int) ($answer['question_active'] ?? 0) !== 1 || $answer['question_deleted'] !== null) {
        return false;
    }

    if ((int) ($answer['user_id'] ?? 0) === $user_id) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        $voteStmt = $pdo->prepare(
            'SELECT id, value
             FROM answer_votes
             WHERE answer_id = :answer_id AND user_id = :user_id
             LIMIT 1
             FOR UPDATE'
        );
        $voteStmt->execute([
            ':answer_id' => $answer_id,
            ':user_id' => $user_id,
        ]);
        $currentVote = $voteStmt->fetch();

        if ($currentVote !== false) {
            if ((int) $currentVote['value'] === $value) {
                $deleteVote = $pdo->prepare('DELETE FROM answer_votes WHERE id = :id');
                $deleteVote->execute([':id' => (int) $currentVote['id']]);
            } else {
                $updateVote = $pdo->prepare('UPDATE answer_votes SET value = :value WHERE id = :id');
                $updateVote->execute([
                    ':value' => $value,
                    ':id' => (int) $currentVote['id'],
                ]);
            }
        } else {
            $insertVote = $pdo->prepare('INSERT INTO answer_votes (answer_id, user_id, value) VALUES (:answer_id, :user_id, :value)');
            $insertVote->execute([
                ':answer_id' => $answer_id,
                ':user_id' => $user_id,
                ':value' => $value,
            ]);
        }

        $pdo->commit();
        return true;
    } catch (PDOException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function aulanet_report_answer(int $reporter_id, int $answer_id, ?string $reason = null): bool
{
    $cleanReason = $reason !== null ? trim($reason) : '';

    return aulanet_create_moderation_log(
        $reporter_id,
        'answer',
        $answer_id,
        'edit',
        $cleanReason !== '' ? $cleanReason : 'Answer reported by user.'
    );
}

function aulanet_fetch_user_overview(int $user_id): ?array
{
    $user = aulanet_fetch_user_by_id($user_id);
    if ($user === null) {
        return null;
    }

    $pdo = get_db();

    $questionStmt = $pdo->prepare('SELECT COUNT(*) FROM questions WHERE user_id = :id AND is_active = 1 AND deleted_at IS NULL');
    $questionStmt->execute([':id' => $user_id]);

    $answerStmt = $pdo->prepare('SELECT COUNT(*) FROM answers WHERE user_id = :id AND is_active = 1 AND deleted_at IS NULL');
    $answerStmt->execute([':id' => $user_id]);

    $viewStmt = $pdo->prepare('SELECT COALESCE(SUM(qv.views), 0) FROM questions q LEFT JOIN question_views qv ON qv.question_id = q.id WHERE q.user_id = :id AND q.is_active = 1 AND q.deleted_at IS NULL');
    $viewStmt->execute([':id' => $user_id]);

    return [
        ...$user,
        'questions_count' => (int) $questionStmt->fetchColumn(),
        'answers_count' => (int) $answerStmt->fetchColumn(),
        'views_count' => (int) $viewStmt->fetchColumn(),
    ];
}

function aulanet_fetch_user_questions(int $user_id): array
{
    $pdo = get_db();
    $sql = "
        SELECT
            q.id,
            q.title,
            q.body,
            q.created_at,
            COALESCE(s.name, 'General') AS subject_name,
            q.tags_json,
            COALESCE(qv.views, 0) AS views_count,
            (
                SELECT COUNT(*)
                FROM answers a
                WHERE a.question_id = q.id
                  AND a.is_active = 1
                  AND a.deleted_at IS NULL
            ) AS answers_count
        FROM questions q
        LEFT JOIN subjects s ON s.id = q.subject_id
        LEFT JOIN question_views qv ON qv.question_id = q.id
        WHERE q.user_id = :id
          AND q.is_active = 1
          AND q.deleted_at IS NULL
        ORDER BY q.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $user_id]);
    $questions = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        $subjectName = (string) ($row['subject_name'] ?? 'General');
        $tags = aulanet_decode_question_tags($row['tags_json'] ?? null, $subjectName);

        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'description' => (string) $row['body'],
            'subject' => $subjectName,
            'tags' => $tags,
            'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
            'answers' => (int) ($row['answers_count'] ?? 0),
            'views' => (int) ($row['views_count'] ?? 0),
        ];
    }, $questions);
}

function aulanet_fetch_user_answers(int $user_id): array
{
    $pdo = get_db();
    $sql = "
        SELECT
            a.id,
            a.body,
            a.created_at,
            q.id AS question_id,
            q.title AS question_title,
            COALESCE(s.name, 'General') AS subject_name
        FROM answers a
        INNER JOIN questions q ON q.id = a.question_id
        LEFT JOIN subjects s ON s.id = q.subject_id
        WHERE a.user_id = :id
          AND a.is_active = 1
          AND a.deleted_at IS NULL
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $user_id]);
    $answers = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'body' => (string) $row['body'],
            'question_id' => (int) $row['question_id'],
            'question_title' => (string) $row['question_title'],
            'subject' => (string) ($row['subject_name'] ?? 'General'),
            'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
        ];
    }, $answers);
}

function aulanet_admin_stats(): array
{
    $pdo = get_db();

    return [
        'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
        'active_students' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE role_id = 2 AND status = "active"')->fetchColumn(),
        'blocked_users' => (int) $pdo->query('SELECT COUNT(*) FROM users WHERE status = "blocked"')->fetchColumn(),
        'questions' => (int) $pdo->query('SELECT COUNT(*) FROM questions WHERE is_active = 1 AND deleted_at IS NULL')->fetchColumn(),
        'answers' => (int) $pdo->query('SELECT COUNT(*) FROM answers WHERE is_active = 1 AND deleted_at IS NULL')->fetchColumn(),
        'reports' => (int) $pdo->query('SELECT COUNT(*) FROM moderation_logs WHERE action = "edit"')->fetchColumn(),
    ];
}

function aulanet_admin_users(int $limit = 50): array
{
    $pdo = get_db();
    $limit = max(1, $limit);
    $sql = "
        SELECT
            u.id,
            u.display_name,
            u.email,
            u.role_id,
            u.status,
            u.created_at,
            COALESCE(qstats.questions_count, 0) AS questions_count,
            COALESCE(astats.answers_count, 0) AS answers_count
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS questions_count
            FROM questions
            WHERE is_active = 1 AND deleted_at IS NULL
            GROUP BY user_id
        ) qstats ON qstats.user_id = u.id
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS answers_count
            FROM answers
            WHERE is_active = 1 AND deleted_at IS NULL
            GROUP BY user_id
        ) astats ON astats.user_id = u.id
        ORDER BY u.created_at DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'name' => (string) $row['display_name'],
            'email' => (string) $row['email'],
            'role_id' => (int) $row['role_id'],
            'role' => aulanet_role_name_from_id((int) $row['role_id']),
            'status' => (string) $row['status'],
            'questions_count' => (int) ($row['questions_count'] ?? 0),
            'answers_count' => (int) ($row['answers_count'] ?? 0),
            'joined' => aulanet_format_time_ago($row['created_at'] ?? null),
        ];
    }, $users);
}

function aulanet_recent_reports(int $limit = 5): array
{
    $pdo = get_db();
    $limit = max(1, $limit);
    $sql = "
        SELECT
            m.id,
            m.target_type,
            m.target_id,
            m.action,
            m.reason,
            m.created_at,
            COALESCE(u.display_name, 'System') AS admin_name
        FROM moderation_logs m
        LEFT JOIN users u ON u.id = m.admin_id
        ORDER BY m.created_at DESC
        LIMIT {$limit}
    ";

    $stmt = $pdo->query($sql);
    $reports = $stmt->fetchAll();

    return array_map(static function (array $row): array {
        return [
            'id' => (int) $row['id'],
            'type' => (string) $row['target_type'],
            'target_id' => (int) $row['target_id'],
            'action' => (string) $row['action'],
            'reason' => (string) ($row['reason'] ?? ''),
            'admin_name' => (string) $row['admin_name'],
            'timeAgo' => aulanet_format_time_ago($row['created_at'] ?? null),
        ];
    }, $reports);
}

function aulanet_create_moderation_log(int $admin_id, string $target_type, int $target_id, string $action, ?string $reason = null): bool
{
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO moderation_logs (admin_id, target_type, target_id, action, reason) VALUES (:admin_id, :target_type, :target_id, :action, :reason)'
    );

    return $stmt->execute([
        ':admin_id' => $admin_id,
        ':target_type' => $target_type,
        ':target_id' => $target_id,
        ':action' => $action,
        ':reason' => $reason,
    ]);
}

function aulanet_resolve_pending_report_log(int $admin_id, string $target_type, int $target_id, string $resolvedAction, ?string $reason = null): bool
{
    $pdo = get_db();
    $resolvedAction = strtolower(trim($resolvedAction));

    if (!in_array($resolvedAction, ['delete', 'block', 'unblock'], true)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare(
            'SELECT id
             FROM moderation_logs
             WHERE target_type = :target_type
               AND target_id = :target_id
               AND action = "edit"
             ORDER BY created_at DESC, id DESC
             LIMIT 1
             FOR UPDATE'
        );
        $stmt->execute([
            ':target_type' => $target_type,
            ':target_id' => $target_id,
        ]);

        $pendingReport = $stmt->fetch();
        if ($pendingReport === false) {
            return false;
        }

        $update = $pdo->prepare(
            'UPDATE moderation_logs
             SET admin_id = :admin_id,
                 action = :action,
                 reason = :reason
             WHERE id = :id'
        );
        $update->execute([
            ':admin_id' => $admin_id,
            ':action' => $resolvedAction,
            ':reason' => $reason,
            ':id' => (int) $pendingReport['id'],
        ]);

        return true;
    } catch (PDOException) {
        return false;
    }
}

function aulanet_report_question(int $reporter_id, int $question_id, ?string $reason = null): bool
{
    $cleanReason = $reason !== null ? trim($reason) : '';

    return aulanet_create_moderation_log(
        $reporter_id,
        'question',
        $question_id,
        'edit',
        $cleanReason !== '' ? $cleanReason : 'Question reported by user.'
    );
}

function aulanet_apply_moderation_action(int $admin_id, string $moderation_action, string $target_type, int $target_id, ?string $reason = null): bool
{
    $targetType = strtolower(trim($target_type));
    $action = strtolower(trim($moderation_action));
    $reason = $reason !== null ? trim($reason) : null;

    if (!in_array($targetType, ['question', 'answer', 'user'], true)) {
        return false;
    }

    if (!in_array($action, ['remove', 'dismiss'], true)) {
        return false;
    }

    $pdo = get_db();

    try {
        $pdo->beginTransaction();

        if ($action === 'dismiss') {
            $logReason = $reason !== null && $reason !== '' ? $reason : 'Report dismissed by admin.';
            if (!aulanet_resolve_pending_report_log($admin_id, $targetType, $target_id, 'unblock', $logReason)) {
                $pdo->rollBack();
                return false;
            }
            $pdo->commit();
            return true;
        }

        switch ($targetType) {
            case 'question':
                $stmt = $pdo->prepare('UPDATE questions SET is_active = 0, deleted_at = NOW() WHERE id = :id');
                $stmt->execute([':id' => $target_id]);
                if (!aulanet_resolve_pending_report_log($admin_id, 'question', $target_id, 'delete', $reason ?? 'Question removed by admin.')) {
                    $pdo->rollBack();
                    return false;
                }
                break;
            case 'answer':
                $stmt = $pdo->prepare('UPDATE answers SET is_active = 0, deleted_at = NOW() WHERE id = :id');
                $stmt->execute([':id' => $target_id]);
                if (!aulanet_resolve_pending_report_log($admin_id, 'answer', $target_id, 'delete', $reason ?? 'Answer removed by admin.')) {
                    $pdo->rollBack();
                    return false;
                }
                break;
            case 'user':
                $stmt = $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = :id');
                $stmt->execute([':id' => $target_id]);
                aulanet_create_moderation_log($admin_id, 'user', $target_id, 'block', $reason ?? 'User blocked by admin.');
                break;
        }

        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function aulanet_apply_user_action(int $admin_id, int $target_user_id, string $action, ?string $reason = null): bool
{
    $action = strtolower(trim($action));
    $reason = $reason !== null ? trim($reason) : null;

    if (!in_array($action, ['block', 'unblock', 'delete'], true)) {
        return false;
    }

    if ($admin_id === $target_user_id) {
        return false;
    }

    $pdo = get_db();

    try {
        $pdo->beginTransaction();

        $targetStmt = $pdo->prepare('SELECT id, role_id, status FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
        $targetStmt->execute([':id' => $target_user_id]);
        $targetUser = $targetStmt->fetch();

        if ($targetUser === false) {
            $pdo->rollBack();
            return false;
        }

        if ((int) ($targetUser['role_id'] ?? 0) === 1) {
            $pdo->rollBack();
            return false;
        }

        if ($action === 'block') {
            $updateStmt = $pdo->prepare('UPDATE users SET status = "blocked" WHERE id = :id');
            $updateStmt->execute([':id' => $target_user_id]);
            aulanet_create_moderation_log($admin_id, 'user', $target_user_id, 'block', $reason !== null && $reason !== '' ? $reason : 'User blocked by admin.');
        } elseif ($action === 'unblock') {
            $updateStmt = $pdo->prepare('UPDATE users SET status = "active" WHERE id = :id');
            $updateStmt->execute([':id' => $target_user_id]);
            aulanet_create_moderation_log($admin_id, 'user', $target_user_id, 'unblock', $reason !== null && $reason !== '' ? $reason : 'User restored by admin.');
        } else {
            $updateStmt = $pdo->prepare('UPDATE users SET status = "deleted" WHERE id = :id');
            $updateStmt->execute([':id' => $target_user_id]);
            aulanet_create_moderation_log($admin_id, 'user', $target_user_id, 'delete', $reason !== null && $reason !== '' ? $reason : 'User deleted by admin.');
        }

        $pdo->commit();
        return true;
    } catch (PDOException) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return false;
    }
}

function aulanet_create_question(int $user_id, string $title, string $body, ?int $subject_id = null, array $tags = []): ?int
{
    $pdo = get_db();
    $title = trim($title);
    $body = trim($body);
    $tags = array_values(array_filter(array_map(static function ($tag): string {
        return is_string($tag) ? trim($tag) : '';
    }, $tags)));

    if ($title === '' || $body === '') {
        return null;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('INSERT INTO questions (user_id, subject_id, title, body, tags_json) VALUES (:user_id, :subject_id, :title, :body, :tags_json)');
        $stmt->execute([
            ':user_id' => $user_id,
            ':subject_id' => $subject_id,
            ':title' => $title,
            ':body' => $body,
            ':tags_json' => $tags !== [] ? json_encode($tags, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
        ]);

        $questionId = (int) $pdo->lastInsertId();

        $viewsStmt = $pdo->prepare('INSERT INTO question_views (question_id, views) VALUES (:question_id, 0)');
        $viewsStmt->execute([':question_id' => $questionId]);

        $pdo->commit();

        return $questionId;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return null;
    }
}

function aulanet_update_question(int $question_id, string $title, string $body): bool
{
    $pdo = get_db();
    $title = trim($title);
    $body = trim($body);

    if ($title === '' || $body === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE questions
         SET title = :title, body = :body, updated_at = NOW()
         WHERE id = :id AND is_active = 1 AND deleted_at IS NULL'
    );

    $stmt->execute([
        ':title' => $title,
        ':body' => $body,
        ':id' => $question_id,
    ]);

    return $stmt->rowCount() > 0;
}

function aulanet_update_answer(int $answer_id, string $body): bool
{
    $pdo = get_db();
    $body = trim($body);

    if ($body === '') {
        return false;
    }

    $stmt = $pdo->prepare(
        'UPDATE answers
         SET body = :body, updated_at = NOW()
         WHERE id = :id AND is_active = 1 AND deleted_at IS NULL'
    );

    $stmt->execute([
        ':body' => $body,
        ':id' => $answer_id,
    ]);

    return $stmt->rowCount() > 0;
}

function aulanet_increment_question_views(int $question_id): void
{
    $pdo = get_db();
    $stmt = $pdo->prepare('INSERT INTO question_views (question_id, views) VALUES (:question_id, 1) ON DUPLICATE KEY UPDATE views = views + 1');
    $stmt->execute([':question_id' => $question_id]);
}

function aulanet_fetch_user_by_id(int $user_id): ?array
{
    $pdo = get_db();
    $sql = "SELECT id, email, display_name, role_id, status, created_at FROM users WHERE id = :id LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $user_id]);

    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        return null;
    }

    return [
        'id' => (int) $user['id'],
        'email' => $user['email'],
        'name' => $user['display_name'],
        'role_id' => (int) $user['role_id'],
        'role' => aulanet_role_name_from_id((int) $user['role_id']),
        'created_at' => $user['created_at'] ?? null,
    ];
}

// Crea un usuario (hash de contraseña internamente)
function create_user(string $email, string $display_name, string $password, int $role_id = 2): ?int {
    $pdo = get_db();
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (email, display_name, password_hash, role_id) VALUES (:email, :display_name, :password_hash, :role_id)";
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([
            ':email' => $email,
            ':display_name' => $display_name,
            ':password_hash' => $hash,
            ':role_id' => $role_id,
        ]);
        return (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        return null;
    }
}

// Autenticar usuario por email/password. Devuelve fila de usuario o null
function authenticate_user(string $email, string $password): ?array {
    $pdo = get_db();
    $sql = "SELECT id, email, display_name, password_hash, role_id, status FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
    if (!$user) return null;
    if ($user['status'] !== 'active') return null;
    if (password_verify($password, $user['password_hash'])) {
        // actualizar last_login
        $upd = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        return [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['display_name'],
            'role_id' => (int) $user['role_id'],
            'role' => aulanet_role_name_from_id((int) $user['role_id']),
        ];
    }
    return null;
}

?>
