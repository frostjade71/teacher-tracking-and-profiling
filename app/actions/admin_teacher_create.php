<?php
// app/actions/admin_teacher_create.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Profile fields
    $employee_no = $_POST['employee_no'] ?? null;
    $department = $_POST['department'] ?? null;
    $subjects = $_POST['subjects'] ?? null;
    $office_text = $_POST['office_text'] ?? null;

    if (empty($name) || empty($email) || empty($password)) {
        die("Missing required fields.");
    }

    $pdo = db();

    // Check if email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        die("Email already exists.");
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, 'teacher')");
    $stmt->execute([$name, $email, $hashed_password]);
    
    $new_user_id = $pdo->lastInsertId();
    
    // Parse subjects into JSON array
    $subjects_json = null;
    if (!empty($subjects) && is_array($subjects)) {
        $subjects_json = json_encode($subjects);
    } elseif (!empty($subjects) && is_string($subjects)) {
         // Fallback for legacy comma-separated
         $subjects_array = array_map('trim', explode(',', $subjects));
         $subjects_json = json_encode($subjects_array);
    }
    
    // Insert teacher profile
    $stmt = $pdo->prepare("
        INSERT INTO teacher_profiles (teacher_user_id, employee_no, department, subjects_json, office_text) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$new_user_id, $employee_no, $department, $subjects_json, $office_text]);

    // Seed empty timetable slots (Dynamic from system defaults)
    $stmt = $pdo->query("SELECT start_time, end_time FROM system_default_timetable_rows ORDER BY start_time ASC");
    $defaultRows = $stmt->fetchAll();

    if (empty($defaultRows)) {
        // Fallback to basic 7AM-6PM if no defaults configured
        for ($h = 7; $h < 19; $h++) {
            $defaultRows[] = ['start_time' => sprintf("%02d:00:00", $h), 'end_time' => sprintf("%02d:00:00", $h + 1)];
        }
    }

    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $sql = "INSERT INTO teacher_timetables (teacher_user_id, day, start_time, end_time, subject_text, room_text) VALUES (?, ?, ?, ?, '', '')";
    $insertSlot = $pdo->prepare($sql);

    foreach ($days as $day) {
        foreach ($defaultRows as $row) {
            $insertSlot->execute([$new_user_id, $day, $row['start_time'], $row['end_time']]);
        }
    }

    audit_log('CREATE TEACHER', 'user', $new_user_id, ['name' => $name, 'email' => $email]);

    redirect('admin_teachers');
}
