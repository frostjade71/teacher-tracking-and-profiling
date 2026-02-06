<?php
// app/actions/admin_subject_create.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if (empty($name)) {
        header("Location: /?page=admin_subjects&error=Name is required");
        exit;
    }

    $pdo = db();
    
    // Check if exists (name must be unique)
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ?");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        header("Location: /?page=admin_subjects&error=Subject already exists");
        exit;
    }

    $stmt = $pdo->prepare("INSERT INTO subjects (name, code) VALUES (?, ?)");
    $stmt->execute([$name, $code]);

    header("Location: /?page=admin_subjects&success=Subject created");
    exit;
}
