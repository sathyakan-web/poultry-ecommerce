<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/db.php';

$pdo = db();

$name = isset($_POST['name']) ? trim((string)$_POST['name']) : '';
$category = isset($_POST['category']) ? trim((string)$_POST['category']) : '';
$sku = isset($_POST['sku']) ? trim((string)$_POST['sku']) : '';
$description = isset($_POST['description']) ? trim((string)$_POST['description']) : '';
$gstRate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : null;

$allowCategory = ['vaccines','water','layer_meds','feed_meds','meds','supplies'];

function products_upsert_error(string $message): void {
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

try {
  if ($name === '' && $sku === '') {
    products_upsert_error('Missing name or sku');
  }

  if ($category === '' || !in_array($category, $allowCategory, true)) {
    products_upsert_error('Missing or invalid category');
  }

  if ($sku === '') {
    $hash = substr(md5(mb_strtolower($name)), 0, 10);
    $sku = strtoupper('NEW-' . str_replace([' ', '-', '__'], '_', $category) . '-' . $hash);
  }

  if ($description === '') {
    $description = 'Created via Product Upsert';
  }

  if ($gstRate === null) {
    $gstRate = 5.00;
  }

  $gstRate = max(0.00, min(100.00, round($gstRate, 2)));

  $createStmt = $pdo->prepare(
    "INSERT INTO products (sku, name, category, description, gst_rate)
     VALUES (:sku, :name, :cat, :desc, :gst)
     ON DUPLICATE KEY UPDATE
       name = :name,
       category = :cat,
       description = :desc,
       gst_rate = :gst"
  );

  $createStmt->execute([
    ':sku' => $sku,
    ':name' => $name !== '' ? $name : $sku,
    ':cat' => $category,
    ':desc' => $description,
    ':gst' => $gstRate,
  ]);

  $idStmt = $pdo->prepare("SELECT id, sku, name, category, description, price, size, gst_rate, created_at FROM products WHERE sku = :sku LIMIT 1");
  $idStmt->execute([':sku' => $sku]);
  $row = $idStmt->fetch();

  if (!$row) {
    products_upsert_error('Failed to upsert product');
  }

  echo json_encode(['ok' => true, 'product' => $row]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
  exit;
}
