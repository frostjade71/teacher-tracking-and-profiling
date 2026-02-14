<?php
// app/actions/admin_save_radar.php

require_login();
require_role('admin');
require_once __DIR__ . '/../settings.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$lat = $_POST['lat'] ?? null;
$lng = $_POST['lng'] ?? null;
$rad = $_POST['radius'] ?? null;

if (!$lat || !$lng || !$rad) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

try {
    set_setting('campus_center_lat', $lat);
    set_setting('campus_center_lng', $lng);
    set_setting('campus_radius_meters', $rad);
    
    audit_log('SAVE RADAR CONFIG', 'system', null, ['lat' => $lat, 'lng' => $lng, 'radius' => $rad]);
    echo json_encode(['success' => true, 'message' => 'Campus Radar settings saved successfully.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error saving settings: ' . $e->getMessage()]);
}
exit;
