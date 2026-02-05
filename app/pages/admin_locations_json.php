<?php
// app/pages/admin_locations_json.php

require_login();
require_role('admin');

header('Content-Type: application/json');

$pdo = db();

// Get latest location for each teacher
// Using IN subquery for MySQL 5.7+ compatibility (simplest generic approach)
// Ideally: Window functions ROW_NUMBER() for MySQL 8.0

$sql = "
SELECT 
    u.id, u.name, 
    tl.lat, tl.lng, tl.accuracy_m, tl.captured_at,
    (SELECT status FROM teacher_status_events WHERE teacher_user_id = u.id ORDER BY set_at DESC LIMIT 1) as status
FROM users u
JOIN teacher_locations tl ON tl.teacher_user_id = u.id
WHERE tl.id IN (
    SELECT MAX(id) 
    FROM teacher_locations 
    GROUP BY teacher_user_id
)
AND u.role = 'teacher'
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$data = $stmt->fetchAll();

echo json_encode($data);
exit;
