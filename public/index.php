<?php
// public/index.php

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
    'admin_monitor' => __DIR__ . '/../app/pages/admin_monitor.php',

    // Generic Pages
    'profile' => __DIR__ . '/../app/pages/profile.php',

    // POST actions
    'login_post' => __DIR__ . '/../app/actions/login_post.php',
    'logout_post' => __DIR__ . '/../app/actions/logout_post.php',
    'teacher_status_post' => __DIR__ . '/../app/actions/teacher_status_post.php',
    'teacher_location_post' => __DIR__ . '/../app/actions/teacher_location_post.php',

    // Admin API
    'admin_locations_json' => __DIR__ . '/../app/pages/admin_locations_json.php',
    'public_locations_json' => __DIR__ . '/../app/pages/public_locations_json.php',

    // Admin Pages
    'admin_teachers' => __DIR__ . '/../app/pages/admin_teachers.php',
    'admin_audit' => __DIR__ . '/../app/pages/admin_audit.php',

    // Admin Actions
    'admin_teacher_create' => __DIR__ . '/../app/actions/admin_teacher_create.php',
    'admin_teacher_update' => __DIR__ . '/../app/actions/admin_teacher_update.php',
    'admin_teacher_delete' => __DIR__ . '/../app/actions/admin_teacher_delete.php',
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
