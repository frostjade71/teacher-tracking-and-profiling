<?php
// app/actions/admin_timetable_action.php

require_login();
require_role('admin');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$pdo = db();
$action = $_POST['action'] ?? '';

try {
    if ($action === 'create') {
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';

        if (empty($startTime) || empty($endTime)) {
            echo json_encode(['success' => false, 'message' => 'Times are required.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO system_default_timetable_rows (start_time, end_time) VALUES (?, ?)");
        $stmt->execute([$startTime, $endTime]);

        audit_log('CREATE DEFAULT TIMETABLE ROW', 'system', $pdo->lastInsertId(), ['start' => $startTime, 'end' => $endTime]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';

        if (empty($id) || empty($startTime) || empty($endTime)) {
            echo json_encode(['success' => false, 'message' => 'Missing data.']);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE system_default_timetable_rows SET start_time = ?, end_time = ? WHERE id = ?");
        $stmt->execute([$startTime, $endTime, $id]);

        audit_log('UPDATE DEFAULT TIMETABLE ROW', 'system', $id, ['start' => $startTime, 'end' => $endTime]);
        echo json_encode(['success' => true]);

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if (empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Missing ID.']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM system_default_timetable_rows WHERE id = ?");
        $stmt->execute([$id]);

        audit_log('DELETE DEFAULT TIMETABLE ROW', 'system', $id);
        echo json_encode(['success' => true]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
