<?php
// app/rbac.php

function has_role(string $role): bool {
    $u = current_user();
    return $u && $u['role'] === $role;
}

function require_role(string $role): void {
    if (!has_role($role)) {
        http_response_code(403);
        echo "403 Forbidden: You do not have the required role ($role).";
        exit;
    }
}
