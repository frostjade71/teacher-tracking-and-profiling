<?php
// app/audit.php

function audit_log(string $action, ?string $entity_type = null, ?int $entity_id = null, ?array $metadata = null): void {
    $u = current_user();
    $actor_id = $u['id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $pdo = db();
    $stmt = $pdo->prepare("
        INSERT INTO audit_logs (actor_user_id, action, entity_type, entity_id, ip, user_agent, metadata_json)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $actor_id,
        $action,
        $entity_type,
        $entity_id,
        $ip,
        substr($ua, 0, 255),
        $metadata ? json_encode($metadata) : null
    ]);
}
