<?php
// app/actions/admin_teacher_update.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Profile fields
    $employee_no = $_POST['employee_no'] ?? null;
    $department = $_POST['department'] ?? null;
    $subjects = $_POST['subjects'] ?? null;
    $office_text = $_POST['office_text'] ?? null;

    if (!$id || empty($name) || empty($email)) {
        die("Missing required fields.");
    }

    $pdo = db();

    // Update user table
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?");
        $stmt->execute([$name, $email, $hashed_password, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $id]);
    }
    
    // Parse subjects into JSON array
    $subjects_json = null;
    if (!empty($subjects) && is_array($subjects)) {
        $subjects_json = json_encode($subjects);
    } elseif (!empty($subjects) && is_string($subjects)) {
        // Fallback for legacy comma-separated
        $subjects_array = array_map('trim', explode(',', $subjects));
        $subjects_json = json_encode($subjects_array);
    }
    
    // Update or insert teacher profile
    $stmt = $pdo->prepare("SELECT teacher_user_id FROM teacher_profiles WHERE teacher_user_id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->fetch()) {
        // Update existing profile
        $stmt = $pdo->prepare("
            UPDATE teacher_profiles 
            SET employee_no = ?, department = ?, subjects_json = ?, office_text = ? 
            WHERE teacher_user_id = ?
        ");
        $stmt->execute([$employee_no, $department, $subjects_json, $office_text, $id]);
    } else {
        // Insert new profile
        $stmt = $pdo->prepare("
            INSERT INTO teacher_profiles (teacher_user_id, employee_no, department, subjects_json, office_text) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $employee_no, $department, $subjects_json, $office_text]);
    }

    audit_log('update_teacher', 'user', $id, ['name' => $name, 'email' => $email, 'password_changed' => !empty($password)]);

    header("Location: /?page=admin_teachers");
    exit;
}
