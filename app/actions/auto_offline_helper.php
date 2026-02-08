<?php
// app/actions/auto_offline_helper.php

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../settings.php';

function check_and_process_expirations() {
    $pdo = db();
    
    // Get Expiration Limit (Default 3 hours = 10800 seconds)
    $expirationSeconds = (int)get_setting('location_expiration_seconds', 10800);
    
    // 1. Find teachers who have EXPIRED sessions (last_seen_at > limit)
    // AND are NOT already OFFLINE
    // We check `location_sessions` for the last activity time.
    
    $sql = "
    SELECT ls.teacher_user_id, ls.last_seen_at
    FROM location_sessions ls
    JOIN teacher_status_events tse ON tse.teacher_user_id = ls.teacher_user_id
    WHERE ls.last_seen_at < DATE_SUB(NOW(), INTERVAL ? SECOND)
    AND tse.set_at = (
        SELECT MAX(set_at) FROM teacher_status_events WHERE teacher_user_id = ls.teacher_user_id
    )
    AND tse.status != 'OFFLINE'
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$expirationSeconds]);
    $expiredTeachers = $stmt->fetchAll();
    
    if ($expiredTeachers) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO teacher_status_events (teacher_user_id, status, set_at) 
            VALUES (?, 'OFFLINE', NOW())
        ");
        
        $stmtClearSession = $pdo->prepare("
            UPDATE teacher_profiles 
            SET current_room = NULL, 
                current_subject = NULL, 
                session_updated_at = NOW(),
                updated_at = NOW()
            WHERE teacher_user_id = ?
        ");

        foreach ($expiredTeachers as $t) {
            // Set Status to OFFLINE
            $stmtInsert->execute([$t['teacher_user_id']]);
            
            // Clear their room/subject as well since they are offline
            $stmtClearSession->execute([$t['teacher_user_id']]);
        }
    }
}
