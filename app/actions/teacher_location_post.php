<?php
// app/actions/teacher_location_post.php

require_login();
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['lat'], $data['lng'])) {
    http_response_code(400);
    echo "Invalid data";
    exit;
}

$u = current_user();

$pdo = db();
$stmt = $pdo->prepare("
    INSERT INTO teacher_locations (teacher_user_id, lat, lng, accuracy_m, captured_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([
    $u['id'],
    $data['lat'],
    $data['lng'],
    $data['accuracy_m'] ?? null
]);

echo "Location updated successfully at " . date('H:i:s');
exit;
