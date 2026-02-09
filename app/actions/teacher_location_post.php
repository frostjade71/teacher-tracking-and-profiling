<?php
// app/actions/teacher_location_post.php


// Start output buffering
ob_start();

require_login();
require_role('teacher');
require_once __DIR__ . '/../settings.php';


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

// --- Geofencing Logic (Server-Side) ---
// Fetch Campus Radar Settings
$campusLat = (float)get_setting('campus_center_lat', '11.3003');
$campusLng = (float)get_setting('campus_center_lng', '124.6856');
$radiusMeters = (float)get_setting('campus_radius_meters', '500');

$lat = (float)$data['lat'];
$lng = (float)$data['lng'];
$skipRoomClear = isset($data['skip_room_clear']) && $data['skip_room_clear'] === true;

// Calculate Haversine Distance
$earthRadius = 6371000; // meters
$latFrom = deg2rad($lat);
$lonFrom = deg2rad($lng);
$latTo = deg2rad($campusLat);
$lonTo = deg2rad($campusLng);

$latDelta = $latTo - $latFrom;
$lonDelta = $lonTo - $lonFrom;

$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
$distance = $angle * $earthRadius;

// Check current status
$stmtStatus = $pdo->prepare("SELECT status FROM teacher_status_events WHERE teacher_user_id = ? ORDER BY set_at DESC LIMIT 1");
$stmtStatus->execute([$u['id']]);
$latest = $stmtStatus->fetch();
$currentStatus = $latest['status'] ?? 'OFFLINE';

$newStatus = null;

// Always clear room if NOT skipping AND NOT already in class
if (!$skipRoomClear && $currentStatus !== 'IN_CLASS') {
    $stmtClearSession = $pdo->prepare("
        UPDATE teacher_profiles 
        SET current_room = NULL, 
            current_subject = NULL, 
            session_updated_at = NOW(),
            updated_at = NOW()
        WHERE teacher_user_id = ?
    ");
    $stmtClearSession->execute([$u['id']]);
}

if ($distance > $radiusMeters) {
    // Check current status first to avoid redundant updates
    if ($currentStatus !== 'OFF_CAMPUS') {
        $stmtUpdate = $pdo->prepare("INSERT INTO teacher_status_events (teacher_user_id, status, set_at) VALUES (?, 'OFF_CAMPUS', NOW())");
        $stmtUpdate->execute([$u['id']]);
        $newStatus = 'OFF_CAMPUS';

        // Redundantly clear session if they are off campus (even if they tried to skip)
        $stmtClearSession = $pdo->prepare("
            UPDATE teacher_profiles 
            SET current_room = NULL, 
                current_subject = NULL, 
                session_updated_at = NOW(),
                updated_at = NOW()
            WHERE teacher_user_id = ?
        ");
        $stmtClearSession->execute([$u['id']]);
    }
} else {
    // Inside Campus
    if ($currentStatus === 'OFF_CAMPUS' || $currentStatus === 'OFFLINE') {
        $stmtUpdate = $pdo->prepare("INSERT INTO teacher_status_events (teacher_user_id, status, set_at) VALUES (?, 'AVAILABLE', NOW())");
        $stmtUpdate->execute([$u['id']]);
        $newStatus = 'AVAILABLE';
    }
}


// --- Update Location Session (Best Implementation) ---
// Upsert into location_sessions
$stmtSession = $pdo->prepare("
    INSERT INTO location_sessions (teacher_user_id, lat, lng, accuracy_m, session_type, last_seen_at)
    VALUES (?, ?, ?, ?, 'GPS', NOW())
    ON DUPLICATE KEY UPDATE
        lat = VALUES(lat),
        lng = VALUES(lng),
        accuracy_m = VALUES(accuracy_m),
        session_type = VALUES(session_type),
        last_seen_at = VALUES(last_seen_at)
");
$stmtSession->execute([
    $u['id'],
    $data['lat'],
    $data['lng'],
    $data['accuracy_m'] ?? null
]);


ob_clean();
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Location updated successfully',
    'new_status' => $newStatus
]);
exit;
