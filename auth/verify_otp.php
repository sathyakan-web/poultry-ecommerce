<?php
require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

// Ensure clean JSON output even on fatal errors
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

sfs_csrf_check();

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';
$otp = isset($_POST['otp']) ? preg_replace('/\D/', '', (string)$_POST['otp']) : '';
$postedRole = isset($_POST['role']) ? strtolower(trim((string)$_POST['role'])) : '';
$sessionRole = isset($_SESSION['pending_otp_role']) ? strtolower(trim((string)$_SESSION['pending_otp_role'])) : '';
$role = $postedRole !== '' ? $postedRole : $sessionRole;
$allowed = ['customer', 'provider', 'logistics', 'guest'];
if (!in_array($role, $allowed, true)) $role = 'customer';

sfs_debug_log('verify_otp_start', ['phone' => $phone, 'otpLen' => strlen($otp), 'role' => $role, 'hasSessionRole' => $sessionRole !== '']);

if (!preg_match('/^\d{10}$/', $phone) || !preg_match('/^\d{1,6}$/', $otp)) {
  sfs_debug_log('verify_otp_invalid_input', ['phone' => $phone, 'otpLen' => strlen($otp)]);
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Invalid phone or OTP']);
  exit;
}

$maxAttempts = defined('OTP_MAX_ATTEMPTS') ? (int)OTP_MAX_ATTEMPTS : 5;

try {
  $pdo = db();

  // Take the newest OTP request row for this phone.
  $stmt = $pdo->prepare(
    'SELECT * FROM otp_requests
     WHERE phone = :phone
     ORDER BY created_at DESC, id DESC
     LIMIT 1'
  );
  $stmt->execute([':phone' => $phone]);
  $req = $stmt->fetch();

  sfs_debug_log('verify_otp_lookup', ['phone' => $phone, 'found' => (bool)$req, 'attempts' => $req ? (int)$req['attempt_count'] : 'n/a']);

  if (!$req) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No OTP requested for this phone']);
    exit;
  }

  if (new DateTime($req['expires_at']) < new DateTime('now')) {
    sfs_debug_log('verify_otp_expired', ['phone' => $phone, 'expires_at' => $req['expires_at']]);
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'OTP expired']);
    exit;
  }

  if ((int)$req['attempt_count'] >= $maxAttempts) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Too many attempts. Request OTP again.']);
    exit;
  }

  $ok = password_verify($otp, $req['otp_hash']);

  sfs_debug_log('verify_otp_check', ['phone' => $phone, 'otpValid' => $ok]);

  if (!$ok) {
    $nextAttempt = (int)$req['attempt_count'] + 1;
    $upd = $pdo->prepare('UPDATE otp_requests SET attempt_count = :a WHERE id = :id');
    $upd->execute([':a' => $nextAttempt, ':id' => (int)$req['id']]);

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid OTP']);
    exit;
  }

  // Success: require the user to already exist. No auto-creation.
  $stmtUser = $pdo->prepare('SELECT id, role, full_name, biometric_enabled FROM users WHERE phone = :phone LIMIT 1');
  $stmtUser->execute([':phone' => $phone]);
  $user = $stmtUser->fetch();

  sfs_debug_log('verify_otp_user_lookup', ['phone' => $phone, 'found' => (bool)$user, 'role' => $user ? $user['role'] : 'n/a']);

  if (!$user) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Phone not registered. Please create an account first.']);
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
  echo json_encode(['ok' => false, 'error' => 'OTP verification failed']);
  exit;
}

