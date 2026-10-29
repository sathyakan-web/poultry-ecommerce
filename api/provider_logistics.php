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

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);

try {
  $stmt = $pdo->prepare(
    'SELECT odi.order_id,
            MIN(odi.delivery_address) AS delivery_address,
            MAX(odi.updated_at) AS last_status_at,
            (SELECT s.status
             FROM order_dispatch_items s
             WHERE s.order_id = odi.order_id
             ORDER BY s.updated_at DESC, s.id DESC
             LIMIT 1) AS status,
            odi.assigned_driver_name,
            MIN(odi.product_id) AS product_id,
            MIN(odi.qty) AS qty,
            MIN(p.name) AS product_name,
            MIN(p.sku) AS sku,
            MIN(p.category) AS category,
            MIN(p.size) AS size,
            MIN(p.price) AS price,
            MIN(p.logo_path) AS logo_path
     FROM order_dispatch_items odi
     LEFT JOIN products p ON p.id = odi.product_id
     WHERE odi.status IN (:assigned, :transit, :delivered)
       AND odi.provider_id = :provider_id
     GROUP BY odi.order_id
     ORDER BY last_status_at DESC'
  );
  $stmt->execute([
    ':assigned' => 'ASSIGNED',
    ':transit' => 'IN_TRANSIT',
    ':delivered' => 'DELIVERED',
    ':provider_id' => $userId,
  ]);
  $rows = $stmt->fetchAll();

  $statusLabels = [
    'ASSIGNED' => 'Assigned',
    'IN_TRANSIT' => 'In Transit',
    'DELIVERED' => 'Delivered',
  ];

  foreach ($rows as &$r) {
    $raw = (string)($r['status'] ?? 'ASSIGNED');
    $r['status'] = $raw;
    $r['status_label'] = $statusLabels[$raw] ?? $raw;
    unset($r['last_status_at']);
  }

  echo json_encode(['ok' => true, 'orders' => $rows]);
  exit;
} catch (Throwable $e) {
  error_log('[provider_logistics] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
