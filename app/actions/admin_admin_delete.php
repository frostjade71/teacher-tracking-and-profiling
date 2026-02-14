<?php
// app/actions/admin_admin_delete.php

require_login();
require_role('admin');

$current_user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        die("Invalid ID.");
    }
    
    // Prevent self-deletion
    if ($id == $current_user['id']) {
        die("You cannot delete your own account.");
    }

    $pdo = db();
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
    $stmt->execute([$id]);

    audit_log('DELETE ADMIN', 'user', $id, []);

    redirect('admin_admins');
}
