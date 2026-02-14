<?php
// app/actions/teacher_subjects_update.php

require_login();
require_role('teacher');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$u = current_user();
$pdo = db();

$subjectsJson = $_POST['subjects'] ?? '[]';
$subjects = json_decode($subjectsJson, true);

if (!is_array($subjects)) {
    echo json_encode(['success' => false, 'message' => 'Invalid subjects data']);
    exit;
}

try {
    // Check if profile exists
    $stmt = $pdo->prepare("SELECT teacher_user_id FROM teacher_profiles WHERE teacher_user_id = ?");
    $stmt->execute([$u['id']]);
    $profile = $stmt->fetch();

    if ($profile) {
        $stmt = $pdo->prepare("UPDATE teacher_profiles SET subjects_json = ?, updated_at = NOW() WHERE teacher_user_id = ?");
        $stmt->execute([$subjectsJson, $u['id']]);
    } else {
        // Create profile if not exists (should rarely happen for existing teachers but good safety)
        $stmt = $pdo->prepare("INSERT INTO teacher_profiles (teacher_user_id, subjects_json, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
        $stmt->execute([$u['id'], $subjectsJson]);
    }

    audit_log('SAVE TEACHER SUBJECT', 'user', $u['id'], ['count' => count($subjects)]);
    echo json_encode(['success' => true, 'message' => 'Subjects updated successfully']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
