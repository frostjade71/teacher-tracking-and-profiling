<?php
// app/actions/admin_admin_update.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!$id || empty($name) || empty($email)) {
        die("Missing required fields.");
    }

    $pdo = db();

    // Update user table
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$name, $email, $hashed_password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $id]);
    }

    audit_log('update_admin', 'user', $id, ['name' => $name, 'email' => $email, 'password_changed' => !empty($password)]);

    header("Location: /?page=admin_admins");
    exit;
}
