<?php
// app/config.php

define('APP_NAME', 'Teacher Tracking System');
// Auto-detect Base URL for local development vs production subdirectory
if (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    define('BASE_URL', 'http://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', 'https://wh1494404.ispot.cc/Facultylink');
}

/**
 * Get full URL for a page or asset
 */
function url($path = '') {
    // Determine if we are in a local development environment where 'public' is the root
    $isLocal = (isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'localhost') !== false);
    
    // If it's a page, format it as index.php?page=...
    if (strpos($path, '?page=') !== false || $path === '') {
        return BASE_URL . '/index.php' . $path;
    }
    
    // If it's an asset (assets/ or images/)
    if (strpos($path, 'assets/') === 0 || strpos($path, 'images/') === 0) {
        // In local Docker, Nginx root is already 'public/', so we don't append it
        if ($isLocal) {
            return BASE_URL . '/' . $path;
        }
        // In production, we assume the subdirectory hosting requires pointing to 'public/'
        return BASE_URL . '/public/' . $path;
    }
    
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Redirect to a specific page
 */
function redirect($page) {
    header("Location: " . url("?page=$page"));
    exit;
}

// Database Config (match docker-compose env)
// Database Config
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'frostjad_facultylinkv1');
define('DB_USER', getenv('DB_USER') ?: 'frostjad_facultylinkv1');
define('DB_PASS', getenv('DB_PASS') ?: 'hpBv7GgtVFMdVfT2ZG5K');

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1); // Turn off in production

// Timezone
date_default_timezone_set('Asia/Manila'); // Philippines Timezone
