<?php
// app/actions/admin_student_delete.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;

    if (empty($id)) {
        die("Invalid ID");
    }

    $pdo = db();

    // Verify user exists and is a student
    $stmt = $pdo->prepare("SELECT id, name FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$id]);
    $student = $stmt->fetch();

    if (!$student) {
        die("Student not found.");
    }

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);

    audit_log('delete_student', 'user', $id, ['name' => $student['name']]);

    header("Location: /?page=admin_students");
    exit;
}
