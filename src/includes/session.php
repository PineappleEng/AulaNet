<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function aulanet_login_user(array $user): void
{
    // $user should contain at least: id, email, name, role
    $_SESSION['user'] = $user;
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
    return $_SESSION['user'] ?? null;
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
