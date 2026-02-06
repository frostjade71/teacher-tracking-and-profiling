<?php
// public/setup_notes_table.php
require __DIR__ . '/../app/config.php';
require __DIR__ . '/../app/db.php';

try {
    $pdo = db();
    $sql = "
    CREATE TABLE IF NOT EXISTS teacher_notes (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        teacher_user_id BIGINT UNSIGNED NOT NULL,
        note TEXT NULL,
        expires_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notes_teacher (teacher_user_id),
        CONSTRAINT fk_notes_teacher FOREIGN KEY (teacher_user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    
    $pdo->exec($sql);
    echo "Table 'teacher_notes' created or already exists.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
