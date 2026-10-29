<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider'], true);

require_once __DIR__ . '/db.php';

$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';

$pdo = db();



// Include live inventory qty via LEFT JOIN so stock is always accurate.
$select = "SELECT p.id, p.sku, p.name, p.category, p.description, p.price, p.size, p.logo_path,
                  COALESCE(i.qty, 0) AS qty
           FROM products p
           LEFT JOIN inventory i ON i.product_id = p.id";

if ($category !== 'all') {
  $stmt = $pdo->prepare($select . " WHERE p.category = :cat ORDER BY p.name ASC");
  $stmt->execute([':cat' => $category]);
} else {
  $stmt = $pdo->query($select . " ORDER BY p.name ASC");
}

$products = $stmt->fetchAll();


echo json_encode(['ok' => true, 'products' => $products]);


