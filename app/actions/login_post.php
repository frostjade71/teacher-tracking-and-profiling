<?php
// app/actions/login_post.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /?page=login");
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    header("Location: /?page=login&error=Missing credentials");
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password_hash'])) {
    login_user($user);
    audit_log('LOGIN_SUCCESS', 'user', $user['id']);

    if ($user['role'] === 'student') header("Location: /?page=student_dashboard");
    elseif ($user['role'] === 'teacher') header("Location: /?page=teacher_dashboard");
    elseif ($user['role'] === 'admin') header("Location: /?page=admin_dashboard");
    exit;
} else {
    // Log failed attempt (careful not to log password)
    audit_log('LOGIN_FAILED', null, null, ['email' => $email]);
    header("Location: /?page=login&error=Invalid credentials");
    exit;
}
