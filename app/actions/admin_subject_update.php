<?php
// app/actions/admin_subject_update.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $code = trim($_POST['code'] ?? '');

    if (empty($id) || empty($name)) {
        header("Location: /?page=admin_subjects&error=Invalid data");
        exit;
    }

    $pdo = db();

    // Check if name taken by another subject
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    if ($stmt->fetch()) {
        header("Location: /?page=admin_subjects&error=Subject name already exists");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE subjects SET name = ?, code = ? WHERE id = ?");
    $stmt->execute([$name, $code, $id]);

    header("Location: /?page=admin_subjects&success=Subject updated");
    exit;
}
