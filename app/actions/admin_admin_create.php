<?php
// app/actions/admin_admin_create.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        die("Missing required fields.");
    }

    $pdo = db();

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Email already exists.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
    $stmt->execute([$name, $email, $hashed_password]);
    
    $new_user_id = $pdo->lastInsertId();

    audit_log('create_admin', 'user', $new_user_id, ['name' => $name, 'email' => $email]);

    redirect('admin_admins');
}
