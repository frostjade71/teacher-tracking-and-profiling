<?php
// app/settings.php

function get_setting($key, $default = null) {
    static $cache = [];
    
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $pdo = db();
    $stmt = $pdo->prepare("SELECT value FROM system_settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    
    $value = $row ? $row['value'] : $default;
    $cache[$key] = $value;
    
    return $value;
}

function set_setting($key, $value) {
    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (`key`, `value`) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");
    return $stmt->execute([$key, $value]);
}
