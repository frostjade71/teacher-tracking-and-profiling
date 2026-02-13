<?php
// app/actions/migrate_timetables.php

require_login();
require_role('admin');

$pdo = db();

echo "Starting migration...<br>";

// 1. Get all teachers
$stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'teacher'");
$teachers = $stmt->fetchAll(PDO::FETCH_ASSOC);

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$startHour = 7;
$endHour = 19;

$count = 0;

foreach ($teachers as $teacher) {
    $teacherId = $teacher['id'];
    echo "Processing {$teacher['name']} (ID: $teacherId)...<br>";
    
    foreach ($days as $day) {
        for ($h = $startHour; $h < $endHour; $h++) {
            $startTime = sprintf("%02d:00:00", $h);
            $endTime = sprintf("%02d:00:00", $h + 1);
            
            // Check if slot exists
            $check = $pdo->prepare("SELECT id FROM teacher_timetables WHERE teacher_user_id = ? AND day = ? AND start_time = ?");
            $check->execute([$teacherId, $day, $startTime]);
            
            if (!$check->fetch()) {
                // Insert empty slot
                $insert = $pdo->prepare("INSERT INTO teacher_timetables (teacher_user_id, day, start_time, end_time, subject_text, room_text) VALUES (?, ?, ?, ?, '', '')");
                $insert->execute([$teacherId, $day, $startTime, $endTime]);
                $count++;
            }
        }
    }
}

echo "Migration complete. Added $count missing slots.";
