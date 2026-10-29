<?php
require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

// Ensure clean JSON output even on fatal errors
if (ob_get_level()) ob_clean();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../login.php');
  exit;
}

sfs_csrf_check();

$jsonMode = isset($_POST['json']) && (string)$_POST['json'] === '1';

function sfs_login_fail(string $message, bool $jsonMode): void {
  sfs_debug_log('login_fail', ['message' => $message, 'jsonMode' => $jsonMode, 'phone' => $phone ?? '']);
  if ($jsonMode) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
  }
  $_SESSION['login_error'] = $message;
  header('Location: ../login.php');
  exit;
}

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

sfs_debug_log('login_start', ['phone' => $phone, 'hasPassword' => $password !== '', 'jsonMode' => $jsonMode, 'method' => $_SERVER['REQUEST_METHOD'] ?? '']);

if (!preg_match('/^\d{10}$/', $phone)) {
  sfs_login_fail('Phone/ID must be exactly 10 digits.', $jsonMode);
}

if ($password === '') {
  sfs_login_fail('Password is required.', $jsonMode);
}

try {
  $pdo = db();
  $stmt = $pdo->prepare('SELECT id, phone, full_name, password_hash, role FROM users WHERE phone = :phone LIMIT 1');
  $stmt->execute([':phone' => $phone]);
  $user = $stmt->fetch();

  sfs_debug_log('login_user_lookup', ['phone' => $phone, 'found' => (bool)$user, 'hasHash' => $user ? (!empty($user['password_hash']) ? 'yes' : 'no') : 'n/a']);

if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    sfs_debug_log('login_password_mismatch', ['phone' => $phone, 'userFound' => (bool)$user, 'hashEmpty' => $user ? empty($user['password_hash']) : 'n/a']);
    sfs_login_fail('Invalid phone or password.', $jsonMode);
  }

  sfs_debug_log('login_password_ok', ['phone' => $phone, 'userId' => (int)$user['id'], 'role' => $user['role'] ?? '']);

  $userId = (int)$user['id'];
  sfs_store_user_session($pdo, $userId);
  $me = sfs_current_user();

  // Set flag so homepage can play a circular reveal transition
  $_SESSION['play_login_to_home_reveal'] = 1;

  if ($jsonMode) {
    header('Content-Type: application/json; charset=utf-8');
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
  }

  $role = (string)($me['role'] ?? 'customer');
  if ($role === 'provider') {
    header('Location: ' . SFS_BASE_URL . '/provider_dashboard.php');
  } elseif ($role === 'logistics') {
     header('Location: ' . SFS_BASE_URL . '/logistics_dashboard_spa.php');
  } else {
    header('Location: ' . SFS_BASE_URL . '/index.php');
  }
  exit;
} catch (Throwable $e) {
  sfs_login_fail('Login failed. Please try again.', $jsonMode);
}

