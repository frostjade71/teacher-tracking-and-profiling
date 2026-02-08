<?php
// app/actions/admin_reset_locations.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    exit;
}

$pdo = db();

try {
    // Delete all rows from teacher_locations
    // This will effectively remove all teachers from the map because the map queries JOIN on this table.
    $stmt = $pdo->prepare("DELETE FROM teacher_locations");
    $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'All teacher locations have been reset.']);
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to reset locations: ' . $e->getMessage()]);
}
