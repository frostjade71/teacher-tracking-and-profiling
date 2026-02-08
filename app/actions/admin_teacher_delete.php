<?php
// app/actions/admin_teacher_delete.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        die("Invalid ID.");
    }

    $pdo = db();
    
    // Optional: Check if user exists first or handle foreign key constraints logic if needed
    // For now, simple delete

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'teacher'");
    $stmt->execute([$id]);

    audit_log('delete_teacher', 'user', $id, []);

    redirect('admin_teachers');
}
