<?php
// Simulated Passkey / Biometric sign-in.
// Real deployments would use WebAuthn; this demo checks the user's
// `biometric_enabled` flag (seeded on demo accounts) and signs them in.
//
//   POST auth/passkey_login.php   { phone }
//   200 { ok: true, session: {...} }
//   403 { ok: false, error: "Passkey not enrolled for this phone" }

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

sfs_csrf_check();

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';

sfs_debug_log('passkey_login_start', ['phone' => $phone]);

if (!preg_match('/^\d{10}$/', $phone)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Phone/ID must be exactly 10 digits.']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT id, phone, full_name, role, biometric_enabled FROM users WHERE phone = :phone LIMIT 1');
  $stmt->execute([':phone' => $phone]);
  $user = $stmt->fetch();

  sfs_debug_log('passkey_login_user_lookup', ['phone' => $phone, 'found' => (bool)$user, 'biometric_enabled' => $user ? (int)($user['biometric_enabled'] ?? 0) : 'n/a']);

  if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No account found for this phone. Register first.']);
    exit;
  }

  if (!(int)($user['biometric_enabled'] ?? 0)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Passkey not enrolled for this account. Use SMS OTP or Passcode.']);
    exit;
  }

  sfs_store_user_session($pdo, (int)$user['id']);
  $me = sfs_current_user();

  $_SESSION['play_login_to_home_reveal'] = 1;

  echo json_encode([
    'ok'      => true,
    'session' => [
      'userId'   => (int)$me['user_id'],
      'phone'    => (string)$me['phone'],
      'fullName' => (string)($me['full_name'] ?? ''),
      'role'     => (string)$me['role'],
      'token'    => (string)session_id(),
    ],
  ]);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Passkey verification failed']);
  exit;
}

