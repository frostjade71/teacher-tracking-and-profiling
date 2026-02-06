<?php
// app/pages/teacher_subjects_api.php

require_login();
require_role('teacher');

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
