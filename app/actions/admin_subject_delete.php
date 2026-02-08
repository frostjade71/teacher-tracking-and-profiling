<?php
// app/actions/admin_subject_delete.php

require_login();
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin_subjects');
}

$id = $_POST['id'] ?? null;

if (!$id) {
    echo "Processing Error: Missing ID";
    exit;
}

$pdo = db();
$stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
$stmt->execute([$id]);

redirect('admin_subjects');
