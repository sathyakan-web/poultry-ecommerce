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
$qrToken = isset($_POST['qr_token']) ? trim((string)$_POST['qr_token']) : '';

if ($orderId === '' || $qrToken === '') {
  echo json_encode(['ok' => false, 'error' => 'order_id and qr_token are required']);
  exit;
}

// Decode and normalize token (same as verify_qr.php)
$qrToken = rawurldecode($qrToken);
$qrToken = preg_replace('/\s+/', '', $qrToken);

try {
  $pdo = db();

  // 1. Verify this order is assigned to this driver
  $stmt = $pdo->prepare(
    'SELECT odi.product_id, p.sku, p.name, p.category, p.size
     FROM order_dispatch_items odi
     JOIN products p ON p.id = odi.product_id
     WHERE odi.order_id = :order_id
       AND odi.assigned_driver_id = :driver_id
     LIMIT 1'
  );
  $stmt->execute([':order_id' => $orderId, ':driver_id' => $userId]);
  $orderItem = $stmt->fetch();

  if (!$orderItem) {
    echo json_encode(['ok' => false, 'error' => 'Order not found or not assigned to you']);
    exit;
  }

  $expectedSku = (string)($orderItem['sku'] ?? '');
  $expectedProductId = (int)($orderItem['product_id'] ?? 0);
  $expectedProductName = (string)($orderItem['name'] ?? '');
  $expectedCategory = (string)($orderItem['category'] ?? '');
  $expectedSize = (string)($orderItem['size'] ?? '');

  // 2. Look up the scanned QR token
  $stmt = $pdo->prepare(
    'SELECT t.token, t.product_id, t.batch_no, t.iot_status,
            p.sku, p.name, p.category, p.size
     FROM qr_tokens t
     JOIN products p ON p.id = t.product_id
     WHERE t.token = :token'
  );
  $stmt->execute([':token' => $qrToken]);
  $qrRow = $stmt->fetch();

  if (!$qrRow) {
    echo json_encode(['ok' => false, 'error' => 'Invalid QR code - product not recognized']);
    exit;
  }

  $scannedSku = (string)($qrRow['sku'] ?? '');
  $scannedProductId = (int)($qrRow['product_id'] ?? 0);
  $scannedProductName = (string)($qrRow['name'] ?? '');
  $scannedBatchNo = (string)($qrRow['batch_no'] ?? '');
  $scannedIotStatus = (string)($qrRow['iot_status'] ?? 'OK');

  // 3. Validate the scanned product matches the order item
  if ($scannedProductId !== $expectedProductId) {
    echo json_encode([
      'ok' => false,
      'error' => 'QR code does not match the expected product for this order',
      'expected' => ['sku' => $expectedSku, 'name' => $expectedProductName],
      'scanned' => ['sku' => $scannedSku, 'name' => $scannedProductName]
    ]);
    exit;
  }

  // 4. Success - return product info for display
  echo json_encode([
    'ok' => true,
    'message' => 'Product verified successfully',
    'product' => [
      'name' => $scannedProductName,
      'sku' => $scannedSku,
      'category' => $expectedCategory,
      'size' => $expectedSize,
      'batch_no' => $scannedBatchNo,
      'iot_status' => $scannedIotStatus,
    ],
    'order_id' => $orderId
  ]);
  exit;

} catch (Throwable $e) {
  error_log('[verify_delivery_qr] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}