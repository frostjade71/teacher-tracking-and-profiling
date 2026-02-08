<?php
// app/pages/teacher_subjects_api.php

// Start output buffering to catch any unwanted output
ob_start();

require_login();
require_role('teacher');

// Clear buffer before sending JSON
ob_clean();
header('Content-Type: application/json');

$u = current_user();
$pdo = db();

// Fetch teacher profile for current subjects
$stmt = $pdo->prepare("SELECT subjects_json FROM teacher_profiles WHERE teacher_user_id = ?");
$stmt->execute([$u['id']]);
$profile = $stmt->fetch();

$subjects = [];

if ($profile && !empty($profile['subjects_json'])) {
    $subjects = json_decode($profile['subjects_json'], true) ?? [];
}

echo json_encode([
    'success' => true,
    'subjects' => $subjects
]);
