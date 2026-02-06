<?php
// app/actions/teacher_status_post.php

require_login();
require_role('teacher');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$status = $_POST['status'] ?? null;
$note = substr($_POST['note'] ?? '', 0, 255);
$u = current_user();

$validStatuses = ['AVAILABLE', 'IN_CLASS', 'BUSY', 'OFFLINE', 'OFF_CAMPUS'];

if (!$status || !in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO teacher_status_events (teacher_user_id, status, set_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$u['id'], $status]);

    audit_log('STATUS_UPDATE', 'teacher_status_events', $pdo->lastInsertId(), ['status' => $status]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully!',
        'status' => $status
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
