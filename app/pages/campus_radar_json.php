<?php
// app/pages/campus_radar_json.php

require_login();
require_once __DIR__ . '/../settings.php';

header('Content-Type: application/json');

$lat = get_setting('campus_center_lat', '11.3003');
$lng = get_setting('campus_center_lng', '124.6856');
$rad = get_setting('campus_radius_meters', '500');

echo json_encode([
    'lat' => (float)$lat,
    'lng' => (float)$lng,
    'radius_meters' => (float)$rad
]);
exit;
