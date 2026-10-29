<?php
// ============================================================
// Remove WebAuthn credential for current user
// ============================================================

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../settings.php');
  exit;
}

sfs_csrf_check();

if (empty($_SESSION['user_id'])) {
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(401);
  echo json_encode(['ok' => false, 'error' => 'Not logged in']);
  exit;
}

try {
  $pdo = db();
  $userId = (int)$_SESSION['user_id'];

  $pdo->prepare('DELETE FROM webauthn_credentials WHERE user_id = :user_id')->execute([':user_id' => $userId]);

  $pdo->prepare('UPDATE users SET biometric_enabled = 0 WHERE id = :id')->execute([':id' => $userId]);

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'message' => 'Fingerprint removed']);
  exit;
} catch (Throwable $e) {
  error_log('[webauthn_reset] user_id=' . ($_SESSION['user_id'] ?? 'null') . ' err=' . $e->getMessage());
  header('Content-Type: application/json; charset=utf-8');
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to remove fingerprint: ' . $e->getMessage()]);
  exit;
}
