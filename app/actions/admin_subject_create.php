<?php
// app/actions/admin_subject_create.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if (empty($name)) {
        header("Location: " . url("?page=admin_subjects&error=Name is required"));
        exit;
    }

    $pdo = db();
    
    // Check if exists (name must be unique)
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        header("Location: " . url("?page=admin_subjects&error=Subject already exists"));
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
    $stmt->execute([$name, $code]);

    $new_id = $pdo->lastInsertId();
    audit_log('ADD SUBJECT', 'subject', $new_id, ['name' => $name, 'code' => $code]);

    header("Location: " . url("?page=admin_subjects&success=Subject created"));
    exit;
}
