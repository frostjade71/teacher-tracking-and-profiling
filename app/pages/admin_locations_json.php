<?php
// app/pages/admin_locations_json.php

// Start output buffering
ob_start();

require_login();
require_role('admin');
require_once __DIR__ . '/../settings.php';

// Clear buffer before header
ob_clean();
header('Content-Type: application/json');

$pdo = db();

$expirationSeconds = (int)get_setting('location_expiration_seconds', 10800);

// Get latest location for each teacher
// Using IN subquery for MySQL 5.7+ compatibility (simplest generic approach)
// Ideally: Window functions ROW_NUMBER() for MySQL 8.0

require_once __DIR__ . '/../actions/auto_offline_helper.php';

// Trigger auto-offline check
check_and_process_expirations();

// Get latest location for each teacher from location_sessions
$sql = "
SELECT 
    u.id, u.name, 
    ls.lat, ls.lng, ls.accuracy_m, ls.last_seen_at as captured_at,
    (SELECT status FROM teacher_status_events WHERE teacher_user_id = u.id ORDER BY set_at DESC LIMIT 1) as status,
    tp.department, tp.current_room
FROM users u
JOIN location_sessions ls ON ls.teacher_user_id = u.id
LEFT JOIN teacher_profiles tp ON u.id = tp.teacher_user_id
WHERE u.role = 'teacher'
AND ls.last_seen_at >= DATE_SUB(NOW(), INTERVAL $expirationSeconds SECOND)
AND (SELECT status FROM teacher_status_events WHERE teacher_user_id = u.id ORDER BY set_at DESC LIMIT 1) != 'OFFLINE'
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode($data);
exit;
