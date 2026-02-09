<?php
// app/actions/admin_reset_locations.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit;
}

$pdo = db();

try {
    // 1. Delete all rows from teacher_locations (History)
    $pdo->exec("DELETE FROM teacher_locations");

    // 2. Delete all rows from location_sessions (Real-time pins)
    $pdo->exec("DELETE FROM location_sessions");

    // 3. Set all currently non-offline teachers to OFFLINE
    // This ensures they don't appear in status lists as 'Available' etc.
    // We select users with role 'teacher' and insert an OFFLINE event.
    $stmtOffline = $pdo->prepare("
        INSERT INTO teacher_status_events (teacher_user_id, status, set_at)
        SELECT id, 'OFFLINE', NOW() FROM users WHERE role = 'teacher'
    ");
    $stmtOffline->execute();

    // 4. Clear current_room and current_subject in teacher_profiles
    $pdo->exec("
        UPDATE teacher_profiles 
        SET current_room = NULL, 
            current_subject = NULL, 
            session_updated_at = NOW(),
            updated_at = NOW()
    ");

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'All teacher locations and sessions have been reset.']);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to reset locations: ' . $e->getMessage()]);
}
