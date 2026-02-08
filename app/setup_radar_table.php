<?php
require_once __DIR__ . '/db.php';

echo "Setting up system_settings table...\n";

$pdo = db();

try {
    // Create table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS system_settings (
            `key` VARCHAR(50) PRIMARY KEY,
            `value` TEXT,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    echo "Table 'system_settings' created or already exists.\n";

    // Insert Default Values if not exist
    $defaults = [
        'campus_center_lat' => '11.3003',
        'campus_center_lng' => '124.6856',
        'campus_radius_meters' => '500'
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (`key`, `value`) VALUES (?, ?)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([$key, $val]);
    }
    echo "Default values inserted (if they didn't exist).\n";

} catch (PDOException $e) {
    die("Error: " . $e->getMessage() . "\n");
}

echo "Done.\n";
