<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/../auth/audit.php';
require_once __DIR__ . '/../auth/textbee_send_sms.php';
require_once __DIR__ . '/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);
$action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'list';

try {
  $pdo = db();
  if ($action === 'list' || $action === 'list_orders') {
    $stmt = $pdo->prepare(
      'SELECT odi.order_id,
              MIN(odi.delivery_address) AS delivery_address,
              MAX(odi.updated_at) AS last_status_at,
              (SELECT s.status
               FROM order_dispatch_items s
               WHERE s.order_id = odi.order_id
               ORDER BY s.updated_at DESC, s.id DESC
               LIMIT 1) AS status,
              MIN(u.full_name) AS customer_name,
              MIN(u.phone) AS customer_phone
       FROM order_dispatch_items odi
       JOIN users u ON u.id = odi.user_id
       LEFT JOIN products p ON p.id = odi.product_id
WHERE odi.assigned_driver_id = :driver_id
          AND odi.status IN (:assigned, :transit, :delivered, :dispatched)
       GROUP BY odi.order_id
       ORDER BY last_status_at DESC'
    );
    $stmt->execute([
      ':driver_id' => $userId,
      ':assigned' => 'ASSIGNED',
      ':transit' => 'IN_TRANSIT',
      ':delivered' => 'DELIVERED',
      ':dispatched' => 'DISPATCHED',
    ]);
    $rows = $stmt->fetchAll();

    $statusLabels = [
      'DISPATCHED'       => 'Pending',
      'ASSIGNED'         => 'Assigned',
      'IN_TRANSIT'       => 'In Transit',
      'DELIVERED'        => 'Delivered',
      'RETURN_REQUESTED' => 'Return Requested',
      'RETURNED'         => 'Returned',
    ];

    $orderIds = array_column($rows, 'order_id');
    $itemsByOrder = [];
    if ($orderIds) {
      $in = str_repeat('?,', count($orderIds) - 1) . '?';
      $stmtItems = $pdo->prepare(
        'SELECT odi.order_id, p.name AS product_name, p.sku AS product_sku, p.logo_path, odi.qty
         FROM order_dispatch_items odi
         JOIN products p ON p.id = odi.product_id
         WHERE odi.order_id IN (' . $in . ')
         ORDER BY odi.id ASC'
      );
      $stmtItems->execute($orderIds);
      foreach ($stmtItems->fetchAll() as $it) {
        $itemsByOrder[$it['order_id']][] = $it;
      }
    }

    foreach ($rows as &$r) {
      $raw = (string)($r['status'] ?? 'ASSIGNED');
      $r['status'] = $raw;
      $r['status_label'] = $statusLabels[$raw] ?? $raw;
      $r['status_updated_at'] = $r['last_status_at'] ?? null;
      $r['items'] = $itemsByOrder[$r['order_id']] ?? [];
      $r['item_count'] = count($r['items']);
      unset($r['last_status_at']);
    }

    echo json_encode(['ok' => true, 'orders' => $rows]);
    exit;
  }

  if ($action === 'home_stats') {
    $stmt = $pdo->prepare(
      'SELECT
          COUNT(*) AS total_orders,
          SUM(CASE WHEN latest_status IN (\'DISPATCHED\', \'ASSIGNED\') THEN 1 ELSE 0 END) AS pending_orders,
          SUM(CASE WHEN latest_status = \'IN_TRANSIT\' THEN 1 ELSE 0 END) AS in_route_orders,
          SUM(CASE WHEN latest_status = \'DELIVERED\' THEN 1 ELSE 0 END) AS delivered_orders
       FROM (
          SELECT odi.order_id,
                 (SELECT s.status
                  FROM order_dispatch_items s
                  WHERE s.order_id = odi.order_id
                  ORDER BY s.updated_at DESC, s.id DESC
                  LIMIT 1) AS latest_status
          FROM order_dispatch_items odi
          WHERE odi.assigned_driver_id = :driver_id
          GROUP BY odi.order_id
       ) t'
    );
    $stmt->execute([':driver_id' => $userId]);
    $stats = $stmt->fetch() ?: [];
    echo json_encode([
      'ok' => true,
      'total_orders' => (int)($stats['total_orders'] ?? 0),
      'pending_orders' => (int)($stats['pending_orders'] ?? 0),
      'in_route_orders' => (int)($stats['in_route_orders'] ?? 0),
      'delivered_orders' => (int)($stats['delivered_orders'] ?? 0),
    ]);
    exit;
  }

  if ($action === 'get_details') {
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
              u.full_name AS customer_name,
              u.phone AS customer_phone
       FROM order_dispatch_items odi
       JOIN products p ON p.id = odi.product_id
       JOIN users u ON u.id = odi.user_id
       WHERE odi.assigned_driver_id = :uid
         AND odi.order_id = :order_id
       ORDER BY odi.id ASC'
    );
    $stmt->execute([':uid' => $userId, ':order_id' => $orderId]);
    $rows = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'items' => $rows]);
    exit;
  }

  if ($action === 'update_status') {
    $orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : '';
    $newStatus = isset($_POST['status']) ? trim((string)$_POST['status']) : '';

    if ($orderId === '' || $newStatus === '') {
      echo json_encode(['ok' => false, 'error' => 'order_id and status are required']);
      exit;
    }

    $allowedStatuses = ['DISPATCHED', 'ASSIGNED', 'IN_TRANSIT', 'DELIVERED', 'RETURN_REQUESTED', 'RETURNED'];
    if (!in_array($newStatus, $allowedStatuses, true)) {
      echo json_encode(['ok' => false, 'error' => 'Invalid status']);
      exit;
    }

    $pdo->beginTransaction();
    try {
      $check = $pdo->prepare(
        'SELECT id FROM order_dispatch_items
         WHERE order_id = :order_id
           AND assigned_driver_id = :driver_id
         LIMIT 1'
      );
      $check->execute([':order_id' => $orderId, ':driver_id' => $userId]);
      if (!$check->fetch()) {
        echo json_encode(['ok' => false, 'error' => 'Order not assigned to you']);
        exit;
      }

      $upd = $pdo->prepare(
        'UPDATE order_dispatch_items
         SET status = :status, updated_at = NOW()
         WHERE order_id = :order_id'
      );
      $upd->execute([':status' => $newStatus, ':order_id' => $orderId]);

      $statusLabels = [
        'DISPATCHED'       => 'Pending',
        'ASSIGNED'         => 'Assigned',
        'IN_TRANSIT'       => 'In Transit',
        'DELIVERED'        => 'Delivered',
        'RETURN_REQUESTED' => 'Return Requested',
        'RETURNED'         => 'Returned',
      ];

      $pdo->commit();

      // Notify the customer after the status change is committed, so a slow
      // gateway cannot hold the transaction open.
      $smsSent = false;
      $smsError = '';
      if ($newStatus === 'DELIVERED') {
        $customerStmt = $pdo->prepare(
          'SELECT odi.user_id AS customer_id, u.phone
           FROM order_dispatch_items odi
           JOIN users u ON u.id = odi.user_id
           WHERE odi.order_id = :order_id
           LIMIT 1'
        );
        $customerStmt->execute([':order_id' => $orderId]);
        $customerRow = $customerStmt->fetch();
        if ($customerRow) {
          $phone = preg_replace('/\D/', '', (string)($customerRow['phone'] ?? ''));
          if (preg_match('/^\d{10}$/', $phone)) {
            $toE164 = sfs_sms_normalise_phone($phone);
            $message = "SafeFlock Solution\nYour order {$orderId} has been delivered.\nThank you for choosing us!";
            try {
              textbee_send_sms($toE164, $message);
              $smsSent = true;
            } catch (Throwable $e) {
              $smsError = $e->getMessage();
              error_log('[logistics_orders] delivery SMS failed for order ' . $orderId . ': ' . $smsError);
            }
          } else {
            $smsError = 'Customer has no valid phone number.';
            error_log('[logistics_orders] delivery SMS skipped for order ' . $orderId . ': ' . $smsError);
          }
        }
      }

      sfs_audit('order_status_updated', [
        'order_id' => $orderId,
        'new_status' => $newStatus,
        'label' => $statusLabels[$newStatus] ?? $newStatus,
      ]);
      echo json_encode([
        'ok' => true,
        'order_id' => $orderId,
        'status' => $newStatus,
        'status_label' => $statusLabels[$newStatus] ?? $newStatus,
        'sms_sent' => $smsSent,
        'sms_error' => $smsError,
      ]);
      exit;
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  echo json_encode(['ok' => false, 'error' => 'Unknown action']);
  exit;
} catch (Throwable $e) {
  error_log('[logistics_orders] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
