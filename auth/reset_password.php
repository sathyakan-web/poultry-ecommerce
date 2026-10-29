<?php
// ============================================================
// Reset passcode via OTP verification
// POST { phone, otp, new_password }
// ============================================================

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
$otp = isset($_POST['otp']) ? preg_replace('/\D/', '', (string)$_POST['otp']) : '';
$newPassword = isset($_POST['new_password']) ? (string)$_POST['new_password'] : '';

if (!preg_match('/^\d{10}$/', $phone)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Phone/ID must be exactly 10 digits.']);
  exit;
}

if (!preg_match('/^\d{6}$/', $otp)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'OTP must be 6 digits.']);
  exit;
}

if ($newPassword === '' || !ctype_digit($newPassword) || strlen($newPassword) < 4) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Passcode must be at least 4 numerical digits.']);
  exit;
}

$maxAttempts = defined('OTP_MAX_ATTEMPTS') ? (int)OTP_MAX_ATTEMPTS : 5;

try {
  $pdo = db();

  $stmt = $pdo->prepare(
    'SELECT * FROM otp_requests
     WHERE phone = :phone
     ORDER BY created_at DESC, id DESC
     LIMIT 1'
  );
  $stmt->execute([':phone' => $phone]);
  $req = $stmt->fetch();

  if (!$req) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No OTP requested for this phone. Please request OTP first.']);
    exit;
  }

  if (new DateTime($req['expires_at']) < new DateTime('now')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'OTP expired. Please request a new one.']);
    exit;
  }

  if ((int)$req['attempt_count'] >= $maxAttempts) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many attempts. Request OTP again.']);
    exit;
  }

  $otpValid = password_verify($otp, $req['otp_hash']);

  if (!$otpValid) {
    $nextAttempt = (int)$req['attempt_count'] + 1;
    $upd = $pdo->prepare('UPDATE otp_requests SET attempt_count = :a WHERE id = :id');
    $upd->execute([':a' => $nextAttempt, ':id' => (int)$req['id']]);

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid OTP']);
    exit;
  }

  $stmtUser = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
  $stmtUser->execute([':phone' => $phone]);
  $user = $stmtUser->fetch();

  if (!$user) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'No account found for this phone.']);
    exit;
  }

  $hash = password_hash($newPassword, PASSWORD_DEFAULT);
  $upd = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
  $upd->execute([':hash' => $hash, ':id' => (int)$user['id']]);

  echo json_encode(['ok' => true, 'message' => 'Passcode updated successfully.']);
  exit;
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Failed to reset passcode. Please try again.']);
  exit;
}
