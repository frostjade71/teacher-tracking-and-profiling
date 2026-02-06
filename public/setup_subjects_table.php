<?php
// public/setup_subjects_table.php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

try {
    $pdo = db();
    $sql = "
    CREATE TABLE IF NOT EXISTS subjects (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_subjects_name (name)
    ) ENGINE=InnoDB;
    ";
    
    $pdo->exec($sql);
    echo "Table 'subjects' created or already exists.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
