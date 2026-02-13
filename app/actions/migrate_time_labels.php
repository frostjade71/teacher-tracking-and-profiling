<?php
// app/actions/migrate_time_labels.php

require_login();
require_role('admin');

$pdo = db();

echo "Starting migration for time labels...<br>";

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM teacher_profiles LIKE 'time_labels_json'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE teacher_profiles ADD COLUMN time_labels_json TEXT DEFAULT NULL");
        echo "Added 'time_labels_json' column to 'teacher_profiles'.<br>";
    } else {
        echo "Column 'time_labels_json' already exists.<br>";
    }

    echo "Migration complete.";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
