<?php
// app/actions/teacher_note_post.php

// Start output buffering
ob_start();

require_login();
require_role('teacher');

// Clear buffer before logic
ob_clean();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$note = trim($_POST['note'] ?? '');
$expiryOption = $_POST['expiry_option'] ?? 'MANUAL'; // '5', '10', '15', '30', '60', 'MANUAL' (Until I change it)

if ($note === '') {
    // If empty note, maybe we want to clear it?
    // For now, let's allow saving empty note as "clearing" the status, 
    // but the UI intent seems to be "Add a note". 
    // If user sends empty note, we insert it anyway as an empty note which effectively hides it.
}

$expiresAt = null;
if ($expiryOption !== 'MANUAL') {
    $minutes = (int)$expiryOption;
    if ($minutes > 0) {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$minutes minutes"));
    }
}

$pdo = db();
$u = current_user();

try {
    $stmt = $pdo->prepare("
        INSERT INTO teacher_notes (teacher_user_id, note, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$u['id'], $note, $expiresAt]);

    echo json_encode([
        'success' => true, 
        'message' => 'Note updated successfully',
        'note' => $note,
        'expires_at' => $expiresAt ? date('c', strtotime($expiresAt)) : null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
