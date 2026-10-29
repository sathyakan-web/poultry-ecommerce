<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);

require_once __DIR__ . '/db.php';

$pdo = db();

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;


if ($productId > 0) {
  $stmt = $pdo->prepare("SELECT i.qty, i.updated_at, p.name AS product, p.category, p.sku, p.price, p.size, p.logo_path
                         FROM inventory i
                         JOIN products p ON p.id = i.product_id
                         WHERE i.product_id = :pid");
  $stmt->execute([':pid' => $productId]);
  $row = $stmt->fetch();
  echo json_encode(['ok' => true, 'inventory' => $row]);
  exit;
}

$stmt = $pdo->query("SELECT p.id AS product_id, p.name AS product, p.category, p.sku, p.price, p.size, p.logo_path, i.qty, i.updated_at
                     FROM inventory i
                     JOIN products p ON p.id = i.product_id
                     ORDER BY p.category ASC, p.name ASC");

echo json_encode(['ok' => true, 'inventory' => $stmt->fetchAll()]);


