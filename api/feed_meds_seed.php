<?php
// Seed feed_meds products into `products` and `inventory` tables.
// IMPORTANT: run once manually (or via POST) then delete or keep as a reference.

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);

require_once __DIR__ . '/db.php';
$pdo = db();

$now = (new DateTime())->format('Y-m-d H:i:s');

$items = [
  ['name' => 'Vitamin AD3E',          'category' => 'feed_meds'],
  ['name' => 'Vitamin B-Complex',     'category' => 'feed_meds'],
  ['name' => 'Vitamin C Supplement',  'category' => 'feed_meds'],
  ['name' => 'Electrolyte Powder',    'category' => 'feed_meds'],
  ['name' => 'Calcium Supplement',    'category' => 'feed_meds'],
  ['name' => 'Liver Tonic',         'category' => 'feed_meds'],
  ['name' => 'Probiotic Powder',    'category' => 'feed_meds'],
];

// You can set initial stock here.
$initialQty = 0;

$allowCategory = ['vaccines','water','layer_meds','feed_meds','meds','supplies'];

function slugish(string $s): string {
  $s = trim($s);
  $s = preg_replace('/\s+/', ' ', $s);
  $s = str_replace(' ', '_', $s);
  $s = preg_replace('/[^A-Za-z0-9_\-]/', '', $s);
  return $s;
}

$results = [];

foreach ($items as $it) {
  $name = $it['name'];
  $category = $it['category'];

  if ($name === '' || $category === '' || !in_array($category, $allowCategory, true)) {
    $results[] = ['ok' => false, 'name' => $name, 'error' => 'Invalid input'];
    continue;
  }

  // Deterministic SKU so re-running is safe.
  $hash = substr(md5(mb_strtolower($name) . '|' . $category), 0, 10);
  $sku = strtoupper('FEED-' . slugish($category) . '-' . slugish($name) . '-' . $hash);

  // Upsert product
  $stmt = $pdo->prepare(
    "INSERT INTO products (sku, name, category, description)
     VALUES (:sku, :name, :cat, :desc)
     ON DUPLICATE KEY UPDATE
       name = :name,
       category = :cat,
       description = :desc"
  );

  $stmt->execute([
    ':sku' => $sku,
    ':name' => $name,
    ':cat' => $category,
    ':desc' => 'Seeded via feed_meds_seed.php',
  ]);

  // Ensure inventory exists/upsert
  $idStmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
  $idStmt->execute([':sku' => $sku]);
  $productId = (int)($idStmt->fetchColumn() ?: 0);

  if ($productId <= 0) {
    $results[] = ['ok' => false, 'name' => $name, 'error' => 'Failed to find product after upsert', 'sku' => $sku];
    continue;
  }

  $invStmt = $pdo->prepare(
    "INSERT INTO inventory (product_id, qty)
     VALUES (:pid, :qty)
     ON DUPLICATE KEY UPDATE qty = :qty, updated_at = CURRENT_TIMESTAMP"
  );
  $invStmt->execute([':pid' => $productId, ':qty' => $initialQty]);

  $results[] = ['ok' => true, 'name' => $name, 'sku' => $sku, 'product_id' => $productId, 'qty' => $initialQty];
}

echo json_encode(['ok' => true, 'initialQty' => $initialQty, 'results' => $results], JSON_PRETTY_PRINT);


