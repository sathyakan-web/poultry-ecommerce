<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/../auth/textbee_send_sms.php';
require_once __DIR__ . '/db.php';

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
  echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
  exit;
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : (isset($_GET['action']) ? trim((string)$_GET['action']) : 'get_cart_for_confirm');


try {
  if ($action === 'get_cart_for_confirm') {
    $stmt = $pdo->prepare(
      'SELECT c.product_id, c.qty, p.sku, p.name, p.category, p.description, p.logo_path, p.price, p.size
       FROM carts c
       JOIN products p ON p.id = c.product_id
       WHERE c.user_id = :uid
       ORDER BY c.id DESC'
    );
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll();
    echo json_encode(['ok' => true, 'cart' => $rows]);
    exit;
  }

  if ($action === 'get_all_orders') {
    $statusLabels = [
      'DISPATCHED'       => 'Pending',
      'ASSIGNED'         => 'Assigned',
      'IN_TRANSIT'       => 'In-Transit',
      'DELIVERED'        => 'Delivered',
      'RETURN_REQUESTED' => 'Return Requested',
      'RETURNED'         => 'Returned',
    ];

    $stmt = $pdo->prepare(
      'SELECT o.order_id,
              MIN(o.delivery_address) AS delivery_address,
              MAX(o.updated_at) AS last_status_at,
              (SELECT s.status
               FROM order_dispatch_items s
               WHERE s.order_id = o.order_id
               ORDER BY s.updated_at DESC, s.id DESC
               LIMIT 1) AS status
       FROM order_dispatch_items o
       GROUP BY o.order_id
       ORDER BY last_status_at DESC'
    );
    $stmt->execute();
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
      $raw = (string)($r['status'] ?? 'DISPATCHED');
      $r['status'] = $raw;
      $r['status_label'] = $statusLabels[$raw] ?? $raw;
      unset($r['last_status_at']);
    }

    echo json_encode(['ok' => true, 'orders' => $rows]);
    exit;
  }

  if ($action === 'get_my_orders') {
    $statusLabels = [
      'DISPATCHED'       => 'Pending',
      'ASSIGNED'         => 'Assigned',
      'IN_TRANSIT'       => 'In-Transit',
      'DELIVERED'        => 'Delivered',
      'RETURN_REQUESTED' => 'Return Requested',
      'RETURNED'         => 'Returned',
    ];

    $stmt = $pdo->prepare(
      'SELECT o.order_id,
              MIN(o.delivery_address) AS delivery_address,
              MAX(o.updated_at) AS last_status_at,
              (SELECT s.status
               FROM order_dispatch_items s
               WHERE s.order_id = o.order_id AND s.user_id = o.user_id
               ORDER BY s.updated_at DESC, s.id DESC
               LIMIT 1) AS status
       FROM order_dispatch_items o
       WHERE o.user_id = :uid
       GROUP BY o.order_id
       ORDER BY last_status_at DESC'
    );
    $stmt->execute([':uid' => $userId]);
    $rows = $stmt->fetchAll();

    foreach ($rows as &$r) {
      $raw = (string)($r['status'] ?? 'DISPATCHED');
      $r['status'] = $raw;
      $r['status_label'] = $statusLabels[$raw] ?? $raw;
      unset($r['last_status_at']);
    }

    echo json_encode(['ok' => true, 'orders' => $rows]);
    exit;
  }

  if ($action === 'get_order_details') {
    $orderId = isset($_POST['order_id']) ? trim((string)$_POST['order_id']) : (isset($_GET['order_id']) ? trim((string)$_GET['order_id']) : '');
    if ($orderId === '') {
      echo json_encode(['ok' => false, 'error' => 'order_id is required']);
      exit;
    }

    $stmt = $pdo->prepare(
      'SELECT odi.order_id,
              odi.qty,
              odi.delivery_address,
              odi.status,
              odi.updated_at,
              p.name,
              p.logo_path,
              p.sku,
              p.price
        FROM order_dispatch_items odi
        JOIN products p ON p.id = odi.product_id
        WHERE odi.user_id = :uid
          AND odi.order_id = :order_id
        ORDER BY odi.id ASC'
    );
    $stmt->execute([':uid' => $userId, ':order_id' => $orderId]);
    $rows = $stmt->fetchAll();

    echo json_encode(['ok' => true, 'items' => $rows]);
    exit;
  }

  if ($action === 'confirm') {
    $deliveryAddress = isset($_POST['delivery_address']) ? trim((string)$_POST['delivery_address']) : '';
    if ($deliveryAddress === '') {
      echo json_encode(['ok' => false, 'error' => 'Delivery address is required']);
      exit;
    }

    $pdo->beginTransaction();

    try {
      // Resolve the phone used for the order confirmation SMS.
      // A missing or invalid phone must NOT block the order itself, the
      // confirmation SMS is informational only.
      $stmtU = $pdo->prepare('SELECT phone FROM users WHERE id = :uid LIMIT 1');
      $stmtU->execute([':uid' => $userId]);
      $u = $stmtU->fetch();
      $phone = preg_replace('/\D/', '', (string)($u['phone'] ?? ''));
      $toE164 = preg_match('/^\d{10}$/', $phone) ? sfs_sms_normalise_phone($phone) : '';
      if ($toE164 === '') {
        error_log('[orders] user ' . $userId . ' has no valid 10-digit phone, order confirmation SMS will be skipped');
      }

      $stmt = $pdo->prepare(
        'SELECT c.product_id, c.qty, p.sku, p.name, p.category, p.logo_path, p.price, p.size
         FROM carts c
         JOIN products p ON p.id = c.product_id
         WHERE c.user_id = :uid'
      );
      $stmt->execute([':uid' => $userId]);
      $cart = $stmt->fetchAll();
      if (!$cart) {
        throw new Exception('Cart is empty');
      }

      $stmtInv = $pdo->prepare(
        'SELECT i.qty FROM inventory i WHERE i.product_id = :pid FOR UPDATE'
      );
      $shortages = [];
      foreach ($cart as $item) {
        $pid = (int)($item['product_id'] ?? 0);
        $qtyNeeded = (int)($item['qty'] ?? 0);
        $stmtInv->execute([':pid' => $pid]);
        $invRow = $stmtInv->fetch();
        $availQty = $invRow ? (int)$invRow['qty'] : 0;

        if ($qtyNeeded > $availQty) {
          $productName = (string)($item['name'] ?? 'Unknown product');
          if ($availQty <= 0) {
            $shortages[] = "{$productName} is out of stock - we will be in touch";
          } else {
            $shortages[] = "{$productName} only has {$availQty} left, but you requested {$qtyNeeded}";
          }
        }
      }

      if (!empty($shortages)) {
        throw new Exception(implode('. ', $shortages));
      }

      $orderId = 'ORD-' . date('Ymd-His') . '-' . random_int(100, 999);

      $itemLines = [];
      $grandTotal = 0;
      foreach ($cart as $item) {
        $nm = (string)($item['name'] ?? 'Product');
        $qty = (int)($item['qty'] ?? 0);
        $price = (float)($item['price'] ?? 0);
        $size = (string)($item['size'] ?? '');
        $lineTotal = $qty * $price;
        $grandTotal += $lineTotal;
        $itemLines[] = '  ' . $nm
          . "\n     " . 'Qty: ' . $qty . ' x ' . $size
          . "\n     " . 'Rs.' . number_format($lineTotal, 2);
      }
      $orderDate = date('d M Y, h:i A');
      $formattedTotal = number_format($grandTotal, 2);

      $nl = "\n";
      $message = '';
      $message .= '========== ORDER CONFIRMED ==========' . $nl;
      $message .= '   SafeFlock Solution' . $nl;
      $message .= '=====================================' . $nl . $nl;
      $message .= 'Order ID: ' . $orderId . $nl;
      $message .= 'Date: ' . $orderDate . $nl . $nl;
      $message .= '--- ITEMS ---' . $nl;
      $message .= implode($nl . $nl, $itemLines) . $nl . $nl;
      $message .= '--- PAYMENT ---' . $nl;
      $message .= '   Cash on Delivery (COD)' . $nl;
      $message .= '   Total Payable: Rs.' . $formattedTotal . $nl . $nl;
      $message .= '--- DELIVERY ---' . $nl;
      $message .= '   Address: ' . $deliveryAddress . $nl;
      $message .= '   Dispatch: Within 2 days' . $nl;
      $message .= '=====================================' . $nl;
      $message .= 'Thank you for your order!' . $nl;
      $message .= 'SafeFlock Solution';

      $stmtIns = $pdo->prepare(
        'INSERT INTO order_dispatch_items (order_id, user_id, product_id, qty, price, delivery_address, status)
         VALUES (:order_id, :user_id, :product_id, :qty, :price, :delivery_address, :status)'
      );

      $stmtReduce = $pdo->prepare(
        'UPDATE inventory SET qty = GREATEST(0, qty - :qty), updated_at = NOW() WHERE product_id = :pid'
      );

      foreach ($cart as $item) {
        $pid = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['qty'] ?? 0);
        $price = (float)($item['price'] ?? 0);

        $stmtIns->execute([
          ':order_id' => $orderId,
          ':user_id' => $userId,
          ':product_id' => $pid,
          ':qty' => $qty,
          ':price' => $price,
          ':delivery_address' => $deliveryAddress,
          ':status' => 'DISPATCHED'
        ]);

        $stmtReduce->execute([
          ':qty' => $qty,
          ':pid' => $pid
        ]);
      }

      $stmtDel = $pdo->prepare('DELETE FROM carts WHERE user_id = :uid');
      $stmtDel->execute([':uid' => $userId]);

      $pdo->commit();

      // Send the order confirmation SMS. If the gateway fails, the order is
      // still committed (non-fatal) and the error is logged for debugging.
      // The send happens AFTER the commit so a slow or hanging gateway can
      // never hold the inventory transaction open.
      $smsSent = false;
      $smsError = '';
      if ($toE164 !== '') {
        try {
          textbee_send_sms($toE164, $message);
          $smsSent = true;
        } catch (Throwable $e) {
          $smsError = $e->getMessage();
          error_log('[orders] SMS send failed for order ' . $orderId . ': ' . $smsError);
        }
      }

      echo json_encode(['ok' => true, 'order_id' => $orderId, 'sms_sent' => $smsSent, 'sms_error' => $smsError]);
      exit;
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      error_log('[orders] confirm failed: ' . $e->getMessage());
      echo json_encode(['ok' => false, 'error' => 'Order failed: ' . $e->getMessage()]);
      exit;
    }
  }


  echo json_encode(['ok' => false, 'error' => 'Unknown action']);
  exit;
} catch (Throwable $e) {
  error_log('[orders] outer error: ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}

