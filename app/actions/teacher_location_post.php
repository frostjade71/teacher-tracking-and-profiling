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

$lat = $data['lat'];
$lng = $data['lng'];

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

// Check if outside radius
// Check if outside radius
$stmtStatus = $pdo->prepare("SELECT status FROM teacher_status_events WHERE teacher_user_id = ? ORDER BY set_at DESC LIMIT 1");
$stmtStatus->execute([$u['id']]);
$latest = $stmtStatus->fetch();


$newStatus = null;

if ($distance > $radiusMeters) {
    // Check current status first to avoid redundant updates
    if (!$latest || $latest['status'] !== 'OFF_CAMPUS') {
        $stmtUpdate = $pdo->prepare("INSERT INTO teacher_status_events (teacher_user_id, status, set_at) VALUES (?, 'OFF_CAMPUS', NOW())");
        $stmtUpdate->execute([$u['id']]);
        $newStatus = 'OFF_CAMPUS';

        // Also clear the current session (room and subject) since they are off campus
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
    // If current status is ON_CAMPUS_BUT_WRONG or OFFLINE, automatically switch back to AVAILABLE
    // Triggers: OFF_CAMPUS, OFFLINE, or NULL (first time)
    $currentStatus = $latest['status'] ?? 'OFFLINE';
    
    if ($currentStatus === 'OFF_CAMPUS' || $currentStatus === 'OFFLINE') {
        $stmtUpdate = $pdo->prepare("INSERT INTO teacher_status_events (teacher_user_id, status, set_at) VALUES (?, 'AVAILABLE', NOW())");
        $stmtUpdate->execute([$u['id']]);
        $newStatus = 'AVAILABLE';
        
        // Audit log? Maybe too spammy for location updates, but status change is important.
        // Let's keep it simple as before.
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
