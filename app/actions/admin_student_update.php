<?php
// app/actions/admin_student_update.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($id) || empty($name) || empty($email)) {
        die("Missing required fields.");
    }

    $pdo = db();

    // Check if user exists and is a student
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        die("Student not found.");
    }

    // Check if email taken by another user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) {
        die("Email already exists.");
    }

    // Update basic info
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $email, $id]);

    // Update password if provided
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $stmt->execute([$hashed, $id]);
    }

    audit_log('update_student', 'user', $id, ['name' => $name, 'email' => $email]);

    header("Location: /?page=admin_students");
    exit;
}
