<?php
// app/pages/public_locations_json.php

// Start output buffering
ob_start();

require_login();
// No role requirement - accessible to all authenticated users
require_once __DIR__ . '/../settings.php';

// Clear buffer before header
ob_clean();
header('Content-Type: application/json');

$pdo = db();

$expirationSeconds = (int)get_setting('location_expiration_seconds', 10800);

require_once __DIR__ . '/../actions/auto_offline_helper.php';

// Trigger auto-offline check
check_and_process_expirations();

// Get latest location for each teacher from location_sessions
// Note: We don't need to filter by time here effectively, because check_and_process_expirations
// will have already set them to OFFLINE if they expired.
// However, the prompt says "The pinpoint Location will be removed".
// Converting them to OFFLINE doesn't necessarily remove the row from location_sessions.
// But the map usually filters by "active" or we just filter by time again to be sure.
// actually, let's filter by time AND status != OFFLINE to be safe and clean.

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

// explanation: 
// 1. We join location_sessions.
// 2. We filter by expiration time (to hide old pins).
// 3. We also filter out OFFLINE status, just in case they were set to OFFLINE manually or automatically.
//    (If they are OFFLINE, they shouldn't be on the map usually).

$campusLat = (float)get_setting('campus_center_lat', '11.3003');
$campusLng = (float)get_setting('campus_center_lng', '124.6856');
$campusRadius = (float)get_setting('campus_radius_meters', '500');

$stmt = $pdo->prepare($sql);
$stmt->execute();
$allLocations = $stmt->fetchAll();

$filteredLocations = [];

foreach ($allLocations as $loc) {
    if (!$loc['lat'] || !$loc['lng']) continue;

    $lat = (float)$loc['lat'];
    $lng = (float)$loc['lng'];

    // Haversine Formula
    $earthRadius = 6371000; // meters

    $latFrom = deg2rad($campusLat);
    $lonFrom = deg2rad($campusLng);
    $latTo = deg2rad($lat);
    $lonTo = deg2rad($lng);

    $latDelta = $latTo - $latFrom;
    $lonDelta = $lonTo - $lonFrom;

    $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
    
    $distance = $angle * $earthRadius;

    if ($distance <= $campusRadius) {
        $filteredLocations[] = $loc;
    }
}

echo json_encode($filteredLocations);
exit;
