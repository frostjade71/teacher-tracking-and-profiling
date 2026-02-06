<?php
// app/actions/teacher_session_update.php
// Updates the current teaching session (room and subject)

require_login();
require_role('teacher');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$u = current_user();
$pdo = db();

$room = $_POST['room'] ?? null;
$subject = $_POST['subject'] ?? null;

try {
    // Check if profile exists
    $stmt = $pdo->prepare("SELECT teacher_user_id FROM teacher_profiles WHERE teacher_user_id = ?");
    $stmt->execute([$u['id']]);
    
    if ($stmt->fetch()) {
        // Update existing profile
        $stmt = $pdo->prepare("
            UPDATE teacher_profiles 
            SET current_room = ?, 
                current_subject = ?, 
                session_updated_at = NOW(),
                updated_at = NOW()
            WHERE teacher_user_id = ?
        ");
        $stmt->execute([$room, $subject, $u['id']]);
    } else {
        // Create profile if it doesn't exist (shouldn't happen, but safety)
        $stmt = $pdo->prepare("
            INSERT INTO teacher_profiles 
            (teacher_user_id, current_room, current_subject, session_updated_at, created_at, updated_at) 
            VALUES (?, ?, ?, NOW(), NOW(), NOW())
        ");
        $stmt->execute([$u['id'], $room, $subject]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Session updated successfully',
        'room' => $room,
        'subject' => $subject
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to update session: ' . $e->getMessage()
    ]);
}
