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
require_once __DIR__ . '/../api/db.php';

$pdo = db();

try {
  $stmt = $pdo->prepare(
    'SELECT p.id AS product_id,
            p.name,
            p.category,
            p.sku,
            p.logo_path,
            i.qty,
            i.updated_at
     FROM inventory i
     JOIN products p ON p.id = i.product_id
     WHERE i.qty < 5
     ORDER BY i.qty ASC,
              p.name ASC'
  );
  $stmt->execute();
  $alerts = $stmt->fetchAll();

  echo json_encode(['ok' => true, 'alerts' => $alerts]);
  exit;
} catch (Throwable $e) {
  error_log('[provider_low_stock] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
