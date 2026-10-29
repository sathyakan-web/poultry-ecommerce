<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_once __DIR__ . '/db.php';

$pdo = db();

$startOfWeek = date('Y-m-d H:i:s', strtotime('monday this week'));

$stmt = $pdo->prepare("
  SELECT 
    p.id,
    p.sku,
    p.name,
    p.category,
    p.description,
    p.price,
    p.size,
    p.logo_path,
    COALESCE(SUM(odi.qty), 0) AS total_qty,
    COALESCE(SUM(odi.qty * p.price), 0) AS total_revenue
  FROM order_dispatch_items odi
  JOIN products p ON p.id = odi.product_id
  WHERE odi.created_at >= :start
  GROUP BY p.id, p.sku, p.name, p.category, p.description, p.price, p.size, p.logo_path
  ORDER BY total_qty DESC, total_revenue DESC
  LIMIT 1
");
$stmt->execute([':start' => $startOfWeek]);
$product = $stmt->fetch();

if (!$product) {
  $stmt = $pdo->query("
    SELECT 
      p.id,
      p.sku,
      p.name,
      p.category,
      p.description,
      p.price,
      p.size,
      p.logo_path,
      0 AS total_qty,
      0 AS total_revenue
    FROM products p
    ORDER BY p.name ASC
    LIMIT 1
  ");
  $product = $stmt->fetch();
}

if (!$product) {
  echo json_encode(['ok' => true, 'product' => null, 'message' => 'No products available']);
  exit;
}

$product['total_qty'] = (int)$product['total_qty'];
$product['total_revenue'] = (float)$product['total_revenue'];

echo json_encode(['ok' => true, 'product' => $product]);
