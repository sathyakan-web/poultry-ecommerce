<?php
// ============================================================
// WebAuthn Status Check
// GET returns whether current user has enrolled passkey
// ============================================================

require_once __DIR__ . '/../auth/check_login.php';
require_login('login.php');
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $userId = (int)$_SESSION['user_id'];

  $stmt = $pdo->prepare('SELECT COUNT(*) FROM webauthn_credentials WHERE user_id = :user_id');
  $stmt->execute([':user_id' => $userId]);
  $count = (int)$stmt->fetchColumn();

  echo json_encode([
    'ok' => true,
    'enrolled' => $count > 0,
    'count' => $count,
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Status check failed']);
}

