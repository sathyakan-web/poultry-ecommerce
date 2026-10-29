<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
$otpCode = isset($_POST['otp_code']) ? trim((string)$_POST['otp_code']) : '';

if ($orderId === '' || $otpCode === '') {
  echo json_encode(['ok' => false, 'error' => 'order_id and otp_code are required']);
  exit;
}

try {
  $pdo = db();
  $stmt = $pdo->prepare(
    'SELECT id, otp_hash, expires_at, status
     FROM delivery_otps
     WHERE order_id = :order_id
       AND driver_id = :driver_id
       AND status = :pending
     ORDER BY created_at DESC
     LIMIT 1'
  );
  $stmt->execute([':order_id' => $orderId, ':driver_id' => $userId, ':pending' => 'PENDING']);
  $otpRow = $stmt->fetch();

  if (!$otpRow) {
    echo json_encode(['ok' => false, 'error' => 'No pending OTP found for this order']);
    exit;
  }

  if (strtotime((string)$otpRow['expires_at']) < time()) {
    $pdo->prepare('UPDATE delivery_otps SET status = :expired WHERE id = :id')->execute([':expired' => 'EXPIRED', ':id' => $otpRow['id']]);
    echo json_encode(['ok' => false, 'error' => 'OTP expired']);
    exit;
  }

  if (!password_verify($otpCode, (string)$otpRow['otp_hash'])) {
    $pdo->prepare('UPDATE delivery_otps SET status = :failed WHERE id = :id')->execute([':failed' => 'FAILED', ':id' => $otpRow['id']]);
    echo json_encode(['ok' => false, 'error' => 'Invalid OTP']);
    exit;
  }

  $pdo->beginTransaction();
  try {
    $pdo->prepare('UPDATE delivery_otps SET status = :verified, verified_at = NOW() WHERE id = :id')
        ->execute([':verified' => 'VERIFIED', ':id' => $otpRow['id']]);

    $upd = $pdo->prepare(
      'UPDATE order_dispatch_items
       SET status = :delivered, updated_at = NOW()
       WHERE order_id = :order_id'
    );
    $upd->execute([':delivered' => 'DELIVERED', ':order_id' => $orderId]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'message' => 'Order marked as delivered']);
    exit;
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
} catch (Throwable $e) {
  error_log('[verify_delivery_otp] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
