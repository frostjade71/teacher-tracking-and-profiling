<?php
// app/actions/admin_settings_save.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$hours = isset($_POST['hours']) ? (int)$_POST['hours'] : 0;
$minutes = isset($_POST['minutes']) ? (int)$_POST['minutes'] : 0;
$seconds = isset($_POST['seconds']) ? (int)$_POST['seconds'] : 0;

$totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

if ($totalSeconds < 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input. Total time must be at least 1 second.']);
    exit;
}

require_once __DIR__ . '/../settings.php';

// Save total seconds
if (set_setting('location_expiration_seconds', $totalSeconds)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save setting.']);
}
