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
    
    // --- Update Location Session Timestamp ---
    // If they change status, it counts as activity.
    // We update last_seen_at. We do NOT clear lat/lng because they might still be there.
    $stmtSession = $pdo->prepare("
        INSERT INTO location_sessions (teacher_user_id, last_seen_at)
        VALUES (?, NOW())
        ON DUPLICATE KEY UPDATE
            last_seen_at = VALUES(last_seen_at)
    ");
    $stmtSession->execute([$u['id']]);


    echo json_encode([
        'success' => true, 
        'message' => 'Status updated successfully!',
        'status' => $status
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
