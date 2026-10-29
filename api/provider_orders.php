<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : (isset($_GET['action']) ? trim((string)$_GET['action']) : 'list_pending');

try {
  $pdo = db();
  if ($action === 'list_pending') {
    $stmt = $pdo->prepare(
      'SELECT odi.order_id,
              MIN(odi.delivery_address) AS delivery_address,
              MIN(u.full_name) AS customer_name,
              MAX(odi.updated_at) AS last_status_at,
              (SELECT s.status
               FROM order_dispatch_items s
               WHERE s.order_id = odi.order_id
               ORDER BY s.updated_at DESC, s.id DESC
               LIMIT 1) AS status,
              MIN(odi.product_id) AS product_id,
              MIN(odi.qty) AS qty,
              MIN(p.name) AS product_name,
              MIN(p.sku) AS sku,
              MIN(p.category) AS category,
              MIN(p.size) AS size,
              MIN(p.price) AS price,
              MIN(p.logo_path) AS logo_path,
              MIN(p.description) AS description
       FROM order_dispatch_items odi
       JOIN users u ON u.id = odi.user_id
       LEFT JOIN products p ON p.id = odi.product_id
       WHERE (odi.status = :pending OR odi.status = :assigned)
         AND (odi.assigned_driver_id IS NULL OR odi.assigned_driver_id = 0)
       GROUP BY odi.order_id
       ORDER BY last_status_at DESC'
    );
    $stmt->execute([':pending' => 'DISPATCHED', ':assigned' => 'ASSIGNED']);
    $rows = $stmt->fetchAll();

    $drivers = [];
    $stmtD = $pdo->prepare(
      'SELECT u.id, u.full_name, u.phone,
              COALESCE(dd.status, "OFF_DUTY") AS duty_status,
              dd.clock_in_at,
              dd.clock_out_at
       FROM users u
       LEFT JOIN driver_duty dd ON dd.driver_id = u.id
       WHERE u.role = :role
       ORDER BY u.full_name'
    );
    $stmtD->execute([':role' => 'logistics']);
    $drivers = $stmtD->fetchAll();

    echo json_encode(['ok' => true, 'orders' => $rows, 'drivers' => $drivers]);
    exit;
  }

  if ($action === 'home_stats') {
    $pendingStmt = $pdo->prepare(
      'SELECT COUNT(DISTINCT odi.order_id) AS cnt
       FROM order_dispatch_items odi
       WHERE (odi.status = :pending OR odi.status = :assigned)
         AND (odi.assigned_driver_id IS NULL OR odi.assigned_driver_id = 0)'
    );
    $pendingStmt->execute([':pending' => 'DISPATCHED', ':assigned' => 'ASSIGNED']);
    $pendingCount = (int)($pendingStmt->fetchColumn() ?: 0);

    $assignedStmt = $pdo->prepare(
      'SELECT COUNT(DISTINCT odi.order_id) AS cnt
       FROM order_dispatch_items odi
       WHERE odi.status IN (:assigned, :transit, :delivered)
         AND odi.provider_id = :provider_id'
    );
    $assignedStmt->execute([
      ':assigned' => 'ASSIGNED',
      ':transit' => 'IN_TRANSIT',
      ':delivered' => 'DELIVERED',
      ':provider_id' => $userId,
    ]);
    $assignedCount = (int)($assignedStmt->fetchColumn() ?: 0);

    $driversStmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = :role');
    $driversStmt->execute([':role' => 'logistics']);
    $driversCount = (int)($driversStmt->fetchColumn() ?: 0);

    echo json_encode([
      'ok' => true,
      'pending_count' => $pendingCount,
      'assigned_count' => $assignedCount,
      'drivers_count' => $driversCount,
    ]);
    exit;
  }

  if ($action === 'get_items') {
    $orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
    if ($orderId === '') {
      echo json_encode(['ok' => false, 'error' => 'order_id is required']);
      exit;
    }

    $stmt = $pdo->prepare(
      'SELECT odi.order_id,
              odi.qty,
              odi.delivery_address,
              odi.status,
              p.name,
              p.logo_path,
              p.sku,
              p.category,
              p.size,
              p.price,
              u.full_name AS customer_name,
              u.phone AS customer_phone
       FROM order_dispatch_items odi
       JOIN products p ON p.id = odi.product_id
       JOIN users u ON u.id = odi.user_id
       WHERE odi.order_id = :order_id
       ORDER BY odi.id ASC'
    );
    $stmt->execute([':order_id' => $orderId]);
    echo json_encode(['ok' => true, 'items' => $stmt->fetchAll()]);
    exit;
  }

  if ($action === 'search_order') {
    $orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
    if ($orderId === '') {
      echo json_encode(['ok' => false, 'error' => 'Order ID is required']);
      exit;
    }

    $stmt = $pdo->prepare(
      'SELECT odi.order_id,
              odi.qty,
              odi.delivery_address,
              odi.status,
              odi.created_at,
              p.name,
              p.logo_path,
              p.sku,
              p.category,
              p.size,
              p.price,
              u.full_name AS customer_name,
              u.phone AS customer_phone
       FROM order_dispatch_items odi
       JOIN products p ON p.id = odi.product_id
       JOIN users u ON u.id = odi.user_id
       WHERE odi.order_id = :order_id
       ORDER BY odi.id ASC'
    );
    $stmt->execute([':order_id' => $orderId]);
    $items = $stmt->fetchAll();

    if (!$items) {
      echo json_encode(['ok' => false, 'error' => 'Order not found']);
      exit;
    }

    $grandTotal = 0;
    foreach ($items as $item) {
      $price = (float)($item['price'] ?? 0);
      $qty = (int)($item['qty'] ?? 0);
      $grandTotal += $price * $qty;
    }

    echo json_encode([
      'ok' => true,
      'order_id' => $orderId,
      'customer_name' => $items[0]['customer_name'] ?? '',
      'customer_phone' => $items[0]['customer_phone'] ?? '',
      'delivery_address' => $items[0]['delivery_address'] ?? '',
      'status' => $items[0]['status'] ?? '',
      'created_at' => $items[0]['created_at'] ?? '',
      'items' => $items,
      'grand_total' => $grandTotal,
    ]);
    exit;
  }

  if ($action === 'assign_driver') {
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

    $pdo->beginTransaction();
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

      $pdo->commit();
      echo json_encode(['ok' => true, 'message' => 'Driver assigned successfully']);
      exit;
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  echo json_encode(['ok' => false, 'error' => 'Unknown action']);
  exit;
} catch (Throwable $e) {
  error_log('[provider_orders] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong: ' . $e->getMessage()]);
  exit;
}
