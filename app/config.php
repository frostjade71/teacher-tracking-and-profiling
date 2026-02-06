<?php
// app/config.php

define('APP_NAME', 'Teacher Tracking System');
define('BASE_URL', 'http://localhost:8080');

// Database Config (match docker-compose env)
define('DB_HOST', getenv('DB_HOST') ?: 'mysql');
define('DB_NAME', getenv('DB_NAME') ?: 'ttrack');
define('DB_USER', getenv('DB_USER') ?: 'ttrack_user');
define('DB_PASS', getenv('DB_PASS') ?: 'ttrack_pass');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Turn off in production

// Timezone
date_default_timezone_set('Asia/Manila'); // Philippines Timezone
