<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/db.php';

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

$orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
$driverId = isset($_POST['driver_id']) ? trim((string)$_POST['driver_id']) : '';
$driverName = isset($_POST['driver_name']) ? trim((string)$_POST['driver_name']) : '';

if ($orderId === '' || $driverId === '' || $driverName === '') {
  echo json_encode(['ok' => false, 'error' => 'order_id, driver_id and driver_name are required']);
  exit;
}

$dutyStmt = $pdo->prepare('SELECT status FROM driver_duty WHERE driver_id = :uid LIMIT 1');
$dutyStmt->execute([':uid' => $driverId]);
$dutyRow = $dutyStmt->fetch();
$dutyStatus = $dutyRow ? ($dutyRow['status'] ?? 'OFF_DUTY') : 'OFF_DUTY';
if ($dutyStatus !== 'ON_DUTY') {
  echo json_encode(['ok' => false, 'error' => 'Selected driver is off duty and cannot be assigned']);
  exit;
}

try {
  $upd = $pdo->prepare(
    'UPDATE order_dispatch_items
     SET status = :status, assigned_driver_id = :driver_id, assigned_driver_name = :driver_name, provider_id = :provider_id, updated_at = NOW()
     WHERE order_id = :order_id'
  );
  $upd->execute([
    ':status' => 'ASSIGNED',
    ':driver_id' => $driverId,
    ':driver_name' => $driverName,
    ':provider_id' => $userId,
    ':order_id' => $orderId,
  ]);

  echo json_encode(['ok' => true, 'message' => 'Driver assigned successfully']);
  exit;
} catch (Throwable $e) {
  error_log('[assign_driver] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
