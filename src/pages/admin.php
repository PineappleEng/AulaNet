<?php

declare(strict_types=1);

require __DIR__ . '/../includes/db.php';
require __DIR__ . '/../includes/session.php';
require __DIR__ . '/../includes/render-template.php';

// Only administrators can access this page
aulanet_require_login();
aulanet_require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetType = (string) filter_input(INPUT_POST, 'target_type');
    $targetId = filter_input(INPUT_POST, 'target_id', FILTER_VALIDATE_INT);
    $userAction = (string) filter_input(INPUT_POST, 'user_action');
    $moderationAction = (string) filter_input(INPUT_POST, 'moderation_action');

    if ($targetType === 'user' && $userAction !== '') {
        if (
            $targetId !== false &&
            $targetId !== null &&
            aulanet_apply_user_action((int) (aulanet_get_user()['id'] ?? 0), (int) $targetId, $userAction)
        ) {
            header('Location: ./admin.php?tab=users&updated=1');
            exit;
        }

        header('Location: ./admin.php?tab=users&error=1');
        exit;
    }

    if (
        $targetId !== false &&
        $targetId !== null &&
        aulanet_apply_moderation_action((int) (aulanet_get_user()['id'] ?? 0), $moderationAction, $targetType, (int) $targetId)
    ) {
        header('Location: ./admin.php?tab=reports&updated=1');
        exit;
    }

    header('Location: ./admin.php?tab=reports&error=1');
    exit;
}

$stats = aulanet_admin_stats();
$users = aulanet_admin_users();
$reports = aulanet_recent_reports();

$userRows = '';
foreach ($users as $user) {
    $avatar = strtoupper(substr((string) $user['name'], 0, 1));
    $userRows .= '<tr>';
    $userRows .= '<td><div class="user-cell"><div class="user-avatar"><span class="user-avatar-text">' . htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8') . '</span></div><div class="user-info"><div class="user-name">' . htmlspecialchars((string) $user['name'], ENT_QUOTES, 'UTF-8') . '</div><div class="user-major">' . htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') . '</div></div></div></td>';
    $userRows .= '<td class="user-email">' . htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') . '</td>';
    $userRows .= '<td><span class="role-badge ' . htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') . '</span></td>';
    $userRows .= '<td>' . ((int) $user['questions_count'] + (int) $user['answers_count']) . '</td>';
    $statusClass = $user['status'] === 'blocked' ? 'blocked' : ($user['status'] === 'deleted' ? 'deleted' : 'active');
    $statusLabel = $user['status'] === 'blocked' ? 'Blocked' : ($user['status'] === 'deleted' ? 'Deleted' : 'Active');
    $statusIcon = $statusClass === 'blocked' ? 'ban' : ($statusClass === 'deleted' ? 'trash-2' : 'check-circle');
    $userRows .= '<td><span class="status-badge ' . $statusClass . '"><i data-lucide="' . $statusIcon . '"></i>' . $statusLabel . '</span></td>';
    if ((int) $user['role_id'] === 1) {
        $userRows .= '<td class="text-right"><span class="user-protected">Protected admin</span></td>';
    } else {
        $userRows .= '<td class="text-right"><form method="post" action="./admin.php" class="user-actions">';
        $userRows .= '<input type="hidden" name="target_type" value="user">';
        $userRows .= '<input type="hidden" name="target_id" value="' . (int) $user['id'] . '">';
        if ($user['status'] === 'blocked' || $user['status'] === 'deleted') {
            $userRows .= '<button type="submit" name="user_action" value="unblock" class="report-btn dismiss"><i data-lucide="check-circle"></i>Restore</button>';
        } else {
            $userRows .= '<button type="submit" name="user_action" value="block" class="report-btn remove"><i data-lucide="ban"></i>Block</button>';
        }
        $userRows .= '</form></td>';
    }
    $userRows .= '</tr>';
}

if ($userRows === '') {
    $userRows = '<tr><td colspan="6">No users found.</td></tr>';
}

$reportCards = '';
foreach ($reports as $report) {
    $isResolved = $report['action'] !== 'edit';
    $reportLabel = $report['action'] === 'edit' ? 'Reported by' : 'Moderated by';
    $reportCards .= '<div class="report-card">';
    $reportCards .= '<div class="report-header">';
    $reportCards .= '<div class="report-info">';
    $reportCards .= '<div class="report-icon-wrapper ' . ($isResolved ? 'resolved' : 'pending') . '"><i data-lucide="' . ($isResolved ? 'check-circle' : 'alert-circle') . '"></i></div>';
    $reportCards .= '<div><div class="report-badges"><span class="content-type-badge ' . htmlspecialchars((string) $report['type'], ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars((string) $report['type'], ENT_QUOTES, 'UTF-8') . '</span><span class="status-type-badge ' . ($isResolved ? 'resolved' : 'pending') . '">' . ($isResolved ? 'resolved' : 'pending') . '</span></div>';
    $reportCards .= '<p class="report-by">' . $reportLabel . ' <span class="report-by-name">' . htmlspecialchars((string) $report['admin_name'], ENT_QUOTES, 'UTF-8') . '</span></p></div></div>';
    if (!$isResolved) {
        $reportCards .= '<form method="post" action="./admin.php" class="report-actions">';
        $reportCards .= '<input type="hidden" name="target_type" value="' . htmlspecialchars((string) $report['type'], ENT_QUOTES, 'UTF-8') . '">';
        $reportCards .= '<input type="hidden" name="target_id" value="' . (int) $report['target_id'] . '">';
        $reportCards .= '<button type="submit" name="moderation_action" value="remove" class="report-btn remove"><i data-lucide="trash-2"></i>Remove</button>';
        $reportCards .= '<button type="submit" name="moderation_action" value="dismiss" class="report-btn dismiss"><i data-lucide="check-circle"></i>Dismiss</button>';
        $reportCards .= '</form>';
    }
    $reportCards .= '</div>';
    $reportCards .= '<div class="report-body">';
    $reportCards .= '<p class="report-reason"><span class="report-reason-label">Action:</span> ' . htmlspecialchars((string) $report['action'], ENT_QUOTES, 'UTF-8') . '</p>';
    $reportCards .= '<p class="report-preview">' . htmlspecialchars((string) $report['reason'], ENT_QUOTES, 'UTF-8') . '</p>';
    $reportCards .= '<p class="report-date">' . htmlspecialchars((string) $report['timeAgo'], ENT_QUOTES, 'UTF-8') . '</p>';
    $reportCards .= '</div></div>';
}

if ($reportCards === '') {
    $reportCards = '<div class="report-card"><p class="report-preview">No moderation logs yet.</p></div>';
}

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

$replacements = [
    '/home.html' => './home.php',
    '/admin.html' => './admin.php',
    '/profile.html' => './profile.php',
    '<!-- NAV -->' => $nav,
    '<!-- FOOTER -->' => $footer,
    '<!-- ADMIN_TOTAL_USERS -->' => (string) $stats['users'],
    '<!-- ADMIN_PENDING_REPORTS -->' => (string) $stats['reports'],
    '<!-- ADMIN_ACTIVE_STUDENTS -->' => (string) $stats['active_students'],
    '<!-- ADMIN_BLOCKED_USERS -->' => (string) $stats['blocked_users'],
    '<!-- ADMIN_USERS -->' => $userRows,
    '<!-- ADMIN_REPORTS -->' => $reportCards,
];

aulanet_render_template(__DIR__ . '/admin.html', $replacements);
