<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function aulanet_login_user(array $user): void
{
    $_SESSION['user'] = [
        'id' => isset($user['id']) ? (int) $user['id'] : null,
        'email' => $user['email'] ?? null,
        'name' => $user['name'] ?? ($user['display_name'] ?? null),
        'role' => $user['role'] ?? null,
    ];
}

function aulanet_logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function aulanet_get_user(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }

    $user = $_SESSION['user'];

    if (isset($user['id']) && function_exists('aulanet_fetch_user_by_id')) {
        $freshUser = aulanet_fetch_user_by_id((int) $user['id']);

        if ($freshUser === null) {
            aulanet_logout_user();
            return null;
        }

        $_SESSION['user'] = [
            'id' => (int) $freshUser['id'],
            'email' => $freshUser['email'],
            'name' => $freshUser['name'],
            'role' => $freshUser['role'],
        ];

        return $_SESSION['user'];
    }

    return $user;
}

function aulanet_is_logged_in(): bool
{
    return !empty($_SESSION['user']);
}

function aulanet_user_role(): ?string
{
    $u = aulanet_get_user();
    return $u['role'] ?? null;
}

function aulanet_require_login(): void
{
    if (!aulanet_is_logged_in()) {
        header('Location: ./login.php');
        exit;
    }
}

function aulanet_require_role(string $role): void
{
    if (aulanet_user_role() !== $role) {
        http_response_code(403);
        echo 'Forbidden - insufficient role';
        exit;
    }
}
