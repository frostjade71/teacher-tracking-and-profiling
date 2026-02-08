<?php
// app/actions/log_map_view_post.php

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require_login();
$u = current_user();

// Log the view action
// We use a specific action name so we can query it later for analytics
audit_log('VIEW_MAP', 'map', null, null);

echo json_encode(['success' => true]);
