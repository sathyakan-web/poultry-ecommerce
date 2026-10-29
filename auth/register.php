<?php
require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/audit.php';

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../login.php');
  exit;
}

sfs_csrf_check();

$jsonMode = isset($_POST['json']) && (string)$_POST['json'] === '1';

function sfs_register_fail(string $message, bool $jsonMode): void {
  if ($jsonMode) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
  }
  $_SESSION['register_error'] = $message;
  header('Location: ../login.php');
  exit;
}

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';
$confirm = isset($_POST['confirm_password']) ? (string)$_POST['confirm_password'] : '';
$fullName = isset($_POST['full_name']) ? trim((string)$_POST['full_name']) : '';
$role = isset($_POST['role']) ? sfs_normalize_role((string)$_POST['role']) : 'customer';

// Phone must be exactly 10 digits
if (!preg_match('/^\d{10}$/', $phone)) {
  sfs_register_fail('Phone/ID must be exactly 10 digits.', $jsonMode);
}

// Password must be numerical + minimum length (numeric passwords only per your requirement)
if ($password === '' || !ctype_digit($password)) {
  sfs_register_fail('Password must be numerical digits only.', $jsonMode);
}

if (strlen($password) < 4) {
  sfs_register_fail('Password is too short.', $jsonMode);
}

if ($confirm !== $password) {
  sfs_register_fail('Passwords do not match.', $jsonMode);
}

if ($role === '' || $role !== 'customer') {
  sfs_register_fail('Invalid account role.', $jsonMode);
}

try {
  $pdo = db();

  // Reject existing accounts to avoid silently overwriting passwords.
  $check = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
  $check->execute([':phone' => $phone]);
  $existing = $check->fetch();

  if ($existing) {
    sfs_register_fail('An account with this Phone/ID already exists. Please login.', $jsonMode);
  }

  $hash = password_hash($password, PASSWORD_DEFAULT);

  $stmt = $pdo->prepare('INSERT INTO users (phone, full_name, password_hash, role) VALUES (:phone, :name, :hash, :role)');
  $stmt->execute([':phone' => $phone, ':name' => $fullName, ':hash' => $hash, ':role' => $role]);

  sfs_audit('register_success', ['phone' => $phone, 'role' => $role]);

  if ($jsonMode) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => 'Account created successfully. Please login.']);
    exit;
  }

  $_SESSION['register_success'] = 'Account created successfully. Please login.';
  header('Location: ../login.php');
  exit;
} catch (Throwable $e) {
  sfs_register_fail('Registration failed. Please try again.', $jsonMode);
}

