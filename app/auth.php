<?php
// app/auth.php

function auth_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user(): ?array {
    auth_start();
    return $_SESSION['user'] ?? null;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        header("Location: /?page=login");
        exit;
    }
    return $u;
}

function login_user(array $user): void {
    auth_start();
    session_regenerate_id(true); // Security: prevent session fixation
    $_SESSION['user'] = [
        'id' => $user['id'],
        'role' => $user['role'],
        'name' => $user['name'],
        'email' => $user['email']
    ];
}

function logout_user(): void {
    auth_start();
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
