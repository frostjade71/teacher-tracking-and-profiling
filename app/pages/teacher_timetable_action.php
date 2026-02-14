<?php
// app/pages/teacher_timetable_action.php

require_login();
require_role('teacher');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$u = current_user();
$pdo = db();
$action = $_POST['action'] ?? '';

// Helper to send JSON
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

try {
    if ($action === 'save') {
        // Validation
        $day = $_POST['day'] ?? '';
        $time = $_POST['time'] ?? ''; // Format "HH:MM" start time
        $subject = trim($_POST['subject'] ?? '');
        $room = trim($_POST['room'] ?? '');
        $course = trim($_POST['course'] ?? '');

        if (!$day || !$time) {
            jsonResponse(false, 'Day and Time are required.');
        }

        // Validate Day
        $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        if (!in_array($day, $validDays)) {
            jsonResponse(false, 'Invalid day selected.');
        }

        $startTime = date('H:i:00', strtotime($time)); 
        // We don't necessarily need end_time for update if slots utilize fixed grid references

        // Dense Storage: Row always exists, so we just UPDATE
        $update = $pdo->prepare("UPDATE teacher_timetables SET subject_text = ?, room_text = ?, course_text = ? WHERE teacher_user_id = ? AND day = ? AND start_time = ?");
        $update->execute([$subject, $room, $course, $u['id'], $day, $startTime]);

        // Fetch the updated/inserted entry to return to frontend
        $stmt = $pdo->prepare("SELECT * FROM teacher_timetables WHERE teacher_user_id = ? AND day = ? AND start_time = ?");
        $stmt->execute([$u['id'], $day, $startTime]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);

        jsonResponse(true, 'Schedule updated successfully.', ['entry' => $entry]);

    } elseif ($action === 'delete') {
        // ID might be passed but we can rely on Day/Time which is safer for the grid logic
        $day = $_POST['day'] ?? '';
        $time = $_POST['time'] ?? '';
        
        if ($day && $time) {
             $startTime = date('H:i:00', strtotime($time));
             
             // Dense Storage: Don't DELETE, just clear the text
             $clear = $pdo->prepare("UPDATE teacher_timetables SET subject_text = '', room_text = '', course_text = '' WHERE teacher_user_id = ? AND day = ? AND start_time = ?");
             $clear->execute([$u['id'], $day, $startTime]);
             
             jsonResponse(true, 'Slot cleared.');
        }
        
        jsonResponse(false, 'Invalid delete parameters.');
    } elseif ($action === 'save_time_label') {
        $index = $_POST['index'] ?? null;
        $value = trim($_POST['value'] ?? '');
        
        if ($index === null) {
            jsonResponse(false, 'Index required.');
        }
        
        // maximize index range check (0-11 for 7am-6pm)
        if (!is_numeric($index) || $index < 0 || $index > 11) {
             jsonResponse(false, 'Invalid time slot index.');
        }

        // Get current labels
        $stmt = $pdo->prepare("SELECT subjects_json, time_labels_json FROM teacher_profiles WHERE teacher_user_id = ?");
        $stmt->execute([$u['id']]);
        $profile = $stmt->fetch();
        
        $labels = json_decode($profile['time_labels_json'] ?? '[]', true) ?? [];
        
        // Update specific index
        $labels[intval($index)] = $value;
        
        // Save back
        $update = $pdo->prepare("UPDATE teacher_profiles SET time_labels_json = ? WHERE teacher_user_id = ?");
        $update->execute([json_encode($labels), $u['id']]);
        
        jsonResponse(true, 'Time label updated.');

    } elseif ($action === 'edit_row_time') {
        $oldStart = $_POST['old_start_time'] ?? '';
        $newStart = $_POST['new_start_time'] ?? '';
        $newEnd = $_POST['new_end_time'] ?? '';

        if (!$oldStart || !$newStart || !$newEnd) {
            jsonResponse(false, 'Missing required times.');
        }

        $newStart = date('H:i:00', strtotime($newStart));
        $newEnd = date('H:i:00', strtotime($newEnd));

        // Update all slots for this teacher that have the old start time
        $stmt = $pdo->prepare("UPDATE teacher_timetables SET start_time = ?, end_time = ? WHERE teacher_user_id = ? AND start_time = ?");
        $stmt->execute([$newStart, $newEnd, $u['id'], $oldStart]);

        audit_log('EDIT TIMETABLE', 'user', $u['id'], ['old' => $oldStart, 'new_start' => $newStart, 'new_end' => $newEnd]);
        jsonResponse(true, 'Time range updated.');

    } elseif ($action === 'delete_row') {
        $time = $_POST['time'] ?? null;
        if (!$time) jsonResponse(false, 'Time required.');

        $stmt = $pdo->prepare("DELETE FROM teacher_timetables WHERE teacher_user_id = ? AND start_time = ?");
        $stmt->execute([$u['id'], $time]);

        audit_log('DELETE TIMETABLE ROW', 'user', $u['id'], ['time' => $time]);
        jsonResponse(true, 'Row deleted.');

    } elseif ($action === 'add_row') {
        $startTime = $_POST['start_time'] ?? null;
        $endTime = $_POST['end_time'] ?? null;
        if (!$startTime || !$endTime) jsonResponse(false, 'Start and End times required.');

        // Format to HH:MM:SS
        $startTime = date('H:i:s', strtotime($startTime));
        $endTime = date('H:i:s', strtotime($endTime));

        // Check if already exists
        $check = $pdo->prepare("SELECT id FROM teacher_timetables WHERE teacher_user_id = ? AND start_time = ?");
        $check->execute([$u['id'], $startTime]);
        if ($check->fetch()) {
            jsonResponse(false, 'This time slot already exists.');
        }

        // Insert 5 days
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $insert = $pdo->prepare("INSERT INTO teacher_timetables (teacher_user_id, day, start_time, end_time, subject_text, room_text, course_text) VALUES (?, ?, ?, ?, '', '', '')");
        
        foreach ($days as $day) {
            $insert->execute([$u['id'], $day, $startTime, $endTime]);
        }

        audit_log('ADD TIMETABLE ROW', 'user', $u['id'], ['time' => $startTime]);
        jsonResponse(true, 'Row added.');

    } elseif ($action === 'reset_to_default') {
        // 1. Delete all existing entries for this teacher
        $delete = $pdo->prepare("DELETE FROM teacher_timetables WHERE teacher_user_id = ?");
        $delete->execute([$u['id']]);

        // 2. Fetch system defaults
        $stmt = $pdo->query("SELECT start_time, end_time FROM system_default_timetable_rows ORDER BY start_time ASC");
        $defaults = $stmt->fetchAll();

        if (empty($defaults)) {
            // Ultimate fallback
            for ($h = 7; $h < 19; $h++) {
                $defaults[] = ['start_time' => sprintf("%02d:00:00", $h), 'end_time' => sprintf("%02d:00:00", $h + 1)];
            }
        }

        // 3. Seed new entries
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        $insert = $pdo->prepare("INSERT INTO teacher_timetables (teacher_user_id, day, start_time, end_time, subject_text, room_text, course_text) VALUES (?, ?, ?, ?, '', '', '')");
        
        foreach ($days as $day) {
            foreach ($defaults as $row) {
                $insert->execute([$u['id'], $day, $row['start_time'], $row['end_time']]);
            }
        }

        audit_log('RESET TIMETABLE', 'user', $u['id']);
        jsonResponse(true, 'Timetable reset to defaults.');

    } else {
        jsonResponse(false, 'Unknown action.');
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
}
