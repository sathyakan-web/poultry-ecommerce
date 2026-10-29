<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/../auth/textbee_send_sms.php';
require_once __DIR__ . '/db.php';

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

$orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
if ($orderId === '') {
  echo json_encode(['ok' => false, 'error' => 'order_id is required']);
  exit;
}

try {
  $stmt = $pdo->prepare(
    'SELECT odi.user_id AS customer_id
     FROM order_dispatch_items odi
     WHERE odi.order_id = :order_id
       AND odi.assigned_driver_id = :driver_id
     LIMIT 1'
  );
  $stmt->execute([':order_id' => $orderId, ':driver_id' => $userId]);
  $row = $stmt->fetch();
  if (!$row) {
    echo json_encode(['ok' => false, 'error' => 'Order not found or not assigned to you']);
    exit;
  }

  $customerId = (int)$row['customer_id'];

  $productName = '';
  $productSku = '';
  $productCategory = '';
  $productSize = '';
  $productPrice = 0;
  try {
    $pStmt = $pdo->prepare(
      'SELECT p.name, p.sku, p.category, p.size, p.price
       FROM order_dispatch_items odi
       JOIN products p ON p.id = odi.product_id
       WHERE odi.order_id = :order_id
       LIMIT 1'
    );
    $pStmt->execute([':order_id' => $orderId]);
    $pRow = $pStmt->fetch();
    if ($pRow) {
      $productName = (string)($pRow['name'] ?? '');
      $productSku = (string)($pRow['sku'] ?? '');
      $productCategory = (string)($pRow['category'] ?? '');
      $productSize = (string)($pRow['size'] ?? '');
      $productPrice = (float)($pRow['price'] ?? 0);
    }
  } catch (Throwable $e) {
    error_log('[send_delivery_otp] product lookup failed: ' . $e->getMessage());
  }

  $otpCode = str_pad((string)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
  $otpHash = password_hash($otpCode, PASSWORD_DEFAULT);
  $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (defined('OTP_EXPIRES_MINUTES') ? OTP_EXPIRES_MINUTES : 5) . ' minutes'));

  $ins = $pdo->prepare(
    'INSERT INTO delivery_otps (order_id, driver_id, customer_id, otp_code, otp_hash, expires_at)
     VALUES (:order_id, :driver_id, :customer_id, :otp_code, :otp_hash, :expires_at)'
  );
  $ins->execute([
    ':order_id' => $orderId,
    ':driver_id' => $userId,
    ':customer_id' => $customerId,
    ':otp_code' => $otpCode,
    ':otp_hash' => $otpHash,
    ':expires_at' => $expiresAt,
  ]);

  $stmtU = $pdo->prepare('SELECT phone FROM users WHERE id = :uid LIMIT 1');
  $stmtU->execute([':uid' => $customerId]);
  $u = $stmtU->fetch();
  $phone = preg_replace('/\D/', '', (string)($u['phone'] ?? ''));
  if (!preg_match('/^\d{10}$/', $phone)) {
    $phone = '';
  }
  $toE164 = $phone !== '' ? sfs_sms_normalise_phone($phone) : '';

  $smsSent = false;
  $smsError = '';
  if ($toE164 === '') {
    $smsError = 'Customer has no valid phone number.';
    error_log('[send_delivery_otp] SMS skipped for order ' . $orderId . ': ' . $smsError);
  } else {
    try {
      $message = "SafeFlock Solution\nYour delivery OTP for order {$orderId} is: {$otpCode}\nShare this with the driver to complete handover.";
      require_once __DIR__ . '/../auth/textbee_send_sms.php';
      textbee_send_sms($toE164, $message);
      $smsSent = true;
    } catch (Throwable $e) {
      $smsError = $e->getMessage();
      error_log('[send_delivery_otp] SMS failed: ' . $smsError);
    }
  }

  echo json_encode([
    'ok' => true,
    'otp_code' => $otpCode,
    'sms_sent' => $smsSent,
    'sms_error' => $smsError,
    'expires_at' => $expiresAt,
    'product' => [
      'name' => $productName ?? '',
      'sku' => $productSku ?? '',
      'category' => $productCategory ?? '',
      'size' => $productSize ?? '',
      'price' => $productPrice ?? 0,
    ],
  ]);
  exit;
} catch (Throwable $e) {
  error_log('[send_delivery_otp] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
