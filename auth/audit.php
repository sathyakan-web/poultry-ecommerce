<?php
// ============================================================
// Audit logging helper for SafeFlock Solution
// Records security-relevant events (register, login, otp, etc.)
// ============================================================

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

function sfs_audit(string $eventType, ?array $meta = []): void {
  try {
    $pdo = db();
    $stmt = $pdo->prepare(
      'INSERT INTO audit_logs (event_type, user_id, phone, ip_address, user_agent, meta)
       VALUES (:event, :uid, :phone, :ip, :ua, :meta)'
    );
    $stmt->execute([
      ':event' => $eventType,
      ':uid'   => isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
      ':phone' => $_SESSION['phone'] ?? null,
      ':ip'    => $_SERVER['REMOTE_ADDR'] ?? null,
      ':ua'    => $_SERVER['HTTP_USER_AGENT'] ?? null,
      ':meta'  => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
  } catch (Throwable $e) {
    error_log('[audit] failed: ' . $e->getMessage());
  }
}