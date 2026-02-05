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
    if (!empty($subjects)) {
        $subjects_array = array_map('trim', explode(',', $subjects));
        $subjects_json = json_encode($subjects_array);
    }
    
    // Insert teacher profile
    $stmt = $pdo->prepare("
        INSERT INTO teacher_profiles (teacher_user_id, employee_no, department, subjects_json, office_text) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$new_user_id, $employee_no, $department, $subjects_json, $office_text]);

    audit_log('create_teacher', 'user', $new_user_id, ['name' => $name, 'email' => $email]);

    header("Location: /?page=admin_teachers");
    exit;
}
