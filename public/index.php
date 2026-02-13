<?php
// public/index.php

// Set Timezone to Philippines
date_default_timezone_set('Asia/Manila');

require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';
require __DIR__ . '/../app/auth.php';
require __DIR__ . '/../app/rbac.php';
require __DIR__ . '/../app/audit.php';

$page = $_GET['page'] ?? 'login';

// Whitelist of valid routes
$routes = [
    'login' => __DIR__ . '/../app/pages/login.php',
    'student_dashboard' => __DIR__ . '/../app/pages/student_dashboard.php',
    'student_teacher' => __DIR__ . '/../app/pages/student_teacher.php',
    'teacher_dashboard' => __DIR__ . '/../app/pages/teacher_dashboard.php',
    'admin_dashboard' => __DIR__ . '/../app/pages/admin_dashboard.php',
    'admin_analytics' => __DIR__ . '/../app/pages/admin_analytics.php',
    'admin_monitor' => __DIR__ . '/../app/pages/admin_monitor.php',

    // Logs
    'log_map_view_post' => __DIR__ . '/../app/actions/log_map_view_post.php',

    // Teacher Pages
    'teacher_subjects' => __DIR__ . '/../app/pages/teacher_subjects.php',
    'teacher_subjects_api' => __DIR__ . '/../app/pages/teacher_subjects_api.php',
    'teacher_timetable' => __DIR__ . '/../app/pages/teacher_timetable.php',
    'teacher_timetable_action' => __DIR__ . '/../app/pages/teacher_timetable_action.php',

    // Generic Pages
    'profile' => __DIR__ . '/../app/pages/profile.php',

    // POST actions
    'login_post' => __DIR__ . '/../app/actions/login_post.php',
    'logout_post' => __DIR__ . '/../app/actions/logout_post.php',
    'teacher_status_post' => __DIR__ . '/../app/actions/teacher_status_post.php',
    'teacher_location_post' => __DIR__ . '/../app/actions/teacher_location_post.php',
    'teacher_note_post' => __DIR__ . '/../app/actions/teacher_note_post.php',
    'teacher_subjects_update' => __DIR__ . '/../app/actions/teacher_subjects_update.php',
    'teacher_session_update' => __DIR__ . '/../app/actions/teacher_session_update.php',

    // Admin API
    'admin_locations_json' => __DIR__ . '/../app/pages/admin_locations_json.php',
    'public_locations_json' => __DIR__ . '/../app/pages/public_locations_json.php',
    'campus_radar_json' => __DIR__ . '/../app/pages/campus_radar_json.php',
    'admin_save_radar' => __DIR__ . '/../app/actions/admin_save_radar.php',
    'admin_campus_radar' => __DIR__ . '/../app/pages/admin_campus_radar.php',

    // Admin Pages
    'admin_teachers' => __DIR__ . '/../app/pages/admin_teachers.php',
    'admin_teacher_profile' => __DIR__ . '/../app/pages/admin_teacher_profile.php',
    'admin_audit' => __DIR__ . '/../app/pages/admin_audit.php',
    'admin_timetable' => __DIR__ . '/../app/pages/admin_timetable.php',
    'admin_timetable_action' => __DIR__ . '/../app/actions/admin_timetable_action.php',

    // Admin Students
    'admin_students' => __DIR__ . '/../app/pages/admin_students.php',
    'admin_student_create' => __DIR__ . '/../app/actions/admin_student_create.php',
    'admin_student_update' => __DIR__ . '/../app/actions/admin_student_update.php',
    'admin_student_delete' => __DIR__ . '/../app/actions/admin_student_delete.php',

    // Admin Admins
    'admin_admins' => __DIR__ . '/../app/pages/admin_admins.php',
    'admin_admin_create' => __DIR__ . '/../app/actions/admin_admin_create.php',
    'admin_admin_update' => __DIR__ . '/../app/actions/admin_admin_update.php',
    'admin_admin_delete' => __DIR__ . '/../app/actions/admin_admin_delete.php',

    // Admin Actions
    'admin_teacher_create' => __DIR__ . '/../app/actions/admin_teacher_create.php',
    'admin_teacher_update' => __DIR__ . '/../app/actions/admin_teacher_update.php',
    'admin_teacher_delete' => __DIR__ . '/../app/actions/admin_teacher_delete.php',

    // Admin Subjects
    'admin_subjects' => __DIR__ . '/../app/pages/admin_subjects.php',
    'admin_subject_create' => __DIR__ . '/../app/actions/admin_subject_create.php',
    'admin_subject_update' => __DIR__ . '/../app/actions/admin_subject_update.php',

    'admin_subject_delete' => __DIR__ . '/../app/actions/admin_subject_delete.php',

    // Admin Locations
    'admin_reset_locations' => __DIR__ . '/../app/actions/admin_reset_locations.php',
    'admin_settings_save' => __DIR__ . '/../app/actions/admin_settings_save.php',
    'migrate_timetables' => __DIR__ . '/../app/actions/migrate_timetables.php',
    'migrate_time_labels' => __DIR__ . '/../app/actions/migrate_time_labels.php',
];

if (!array_key_exists($page, $routes)) {
    http_response_code(404);
    echo "<h1>404 Not Found</h1>";
    exit;
}

if (!file_exists($routes[$page])) {
    // If the file hasn't been created yet during development
    echo "<h1>Page under construction</h1><p>File for '$page' not found.</p>";
    exit;
}

require $routes[$page];
