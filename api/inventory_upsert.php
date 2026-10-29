<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/../api/db.php';

$pdo = db();

$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$qty = isset($_POST['qty']) ? (int)$_POST['qty'] : null;
$action = isset($_POST['inv_action']) ? trim((string)$_POST['inv_action']) : 'set';
$newProductName = isset($_POST['new_product_name']) ? trim((string)$_POST['new_product_name']) : '';
$newCategory = isset($_POST['new_category']) ? trim((string)$_POST['new_category']) : '';
$newProductDescription = isset($_POST['new_product_description']) ? trim((string)$_POST['new_product_description']) : '';
$newProductPrice = isset($_POST['new_product_price']) ? (float)$_POST['new_product_price'] : 0.00;
$newProductSize = isset($_POST['new_product_size']) ? trim((string)$_POST['new_product_size']) : '';
$gstRate = isset($_POST['gst_rate']) ? (float)$_POST['gst_rate'] : null;

$priceAction = isset($_POST['price_action']) ? trim((string)$_POST['price_action']) : '';
$priceType = isset($_POST['price_type']) ? trim((string)$_POST['price_type']) : 'flat';
$priceValue = isset($_POST['price_value']) ? (float)$_POST['price_value'] : 0;

$allowCategory = ['vaccines','water','layer_meds','feed_meds','meds','supplies'];
$validActions = ['add', 'deduct', 'set'];
if (!in_array($action, $validActions, true)) {
  $action = 'set';
}

function inventory_error(string $message): void {
  echo json_encode(['ok' => false, 'error' => $message]);
  exit;
}

try {

// Ensure inventory row exists first (if product_id is valid)
function ensureInventoryRow(PDO $pdo, int $productId): void {
  $chk = $pdo->prepare('SELECT id FROM inventory WHERE product_id = :pid LIMIT 1');
  $chk->execute([':pid' => $productId]);
  if (!$chk->fetch()) {
    $ins = $pdo->prepare('INSERT INTO inventory (product_id, qty) VALUES (:pid, 0)');
    $ins->execute([':pid' => $productId]);
  }
}

// If product_id missing, we try to create a product using new_product_name + new_category.
if ($productId <= 0) {
  if ($newProductName === '' || !in_array($newCategory, $allowCategory, true)) {
    inventory_error('Missing product_id or missing new_product_name/new_category');
  }

  if ($newProductDescription === '') {
    inventory_error('Missing new_product_description');
  }


  // Upsert product first (so it appears immediately in products search/listing)
  $hash = substr(md5(mb_strtolower($newProductName)), 0, 8);
  $sku = strtoupper('NEW-' . str_replace([' ', '-', '__'], '_', $newCategory) . '-' . $hash);

  // If new_product_price is not set, default based on category
  if ($newProductPrice <= 0) {
    switch ($newCategory) {
      case 'vaccines': $newProductPrice = 1000.00; break;
      case 'layer_meds': $newProductPrice = 500.00; break;
      case 'feed_meds': $newProductPrice = 300.00; break;
      case 'water': $newProductPrice = 200.00; break;
      case 'meds': $newProductPrice = 500.00; break;
      case 'supplies': $newProductPrice = 250.00; break;
      default: $newProductPrice = 500.00;
    }
  }

  if ($gstRate === null) {
    $gstRate = 5.00;
  }
  $gstRate = max(0.00, min(100.00, round($gstRate, 2)));

  $createStmt = $pdo->prepare(
    "INSERT INTO products (sku, name, category, description, price, size, gst_rate, updated_at, synced_at)
     VALUES (:sku, :name, :cat, :desc, :price, :size, :gst, NOW(), NULL)
     ON DUPLICATE KEY UPDATE
       name = :name,
       category = :cat,
       description = :desc,
       price = :price,
       size = :size,
       gst_rate = :gst,
       updated_at = NOW(),
       synced_at = NULL"
  );

  $createStmt->execute([
    ':sku' => $sku,
    ':name' => $newProductName,
    ':cat' => $newCategory,
    ':desc' => $newProductDescription,
    ':price' => $newProductPrice,
    ':size' => $newProductSize,
    ':gst' => $gstRate,
  ]);


  // Fetch id of created/updated product
  $idStmt = $pdo->prepare("SELECT id FROM products WHERE sku = :sku LIMIT 1");
  $idStmt->execute([':sku' => $sku]);
  $productId = (int)($idStmt->fetchColumn() ?: 0);

  if ($productId <= 0) {
    inventory_error('Failed to create product');
  }

  // For new product, ensure inventory row exists with 0 qty first, then set
  ensureInventoryRow($pdo, $productId);

  // Handle image upload for new product
  if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
    }

    $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($ext, $allowedExts, true)) {
      $newFilename = $sku . '-' . $productId . '.' . $ext;
      $destPath = $uploadDir . $newFilename;

      if (move_uploaded_file($_FILES['product_image']['tmp_name'], $destPath)) {
        $logoPath = 'uploads/' . $newFilename;
        $updStmt = $pdo->prepare('UPDATE products SET logo_path = :logo WHERE id = :pid');
        $updStmt->execute([':logo' => $logoPath, ':pid' => $productId]);
      }
    }
  }
}


if ($qty === null || $qty < 0) {
  inventory_error('Missing or invalid qty');
}

// Ensure inventory row exists for existing products too
ensureInventoryRow($pdo, $productId);

// Execute based on action
if ($action === 'add') {
  // Add to existing stock
  $stmt = $pdo->prepare(
    "UPDATE inventory SET qty = qty + :qty, updated_at = CURRENT_TIMESTAMP, synced_at = NULL WHERE product_id = :pid"
  );
  $stmt->execute([
    ':pid' => $productId,
    ':qty' => $qty,
  ]);
} elseif ($action === 'deduct') {
  // Deduct from existing stock (minimum 0)
  $stmt = $pdo->prepare(
    "UPDATE inventory SET qty = GREATEST(0, qty - :qty), updated_at = CURRENT_TIMESTAMP, synced_at = NULL WHERE product_id = :pid"
  );
  $stmt->execute([
    ':pid' => $productId,
    ':qty' => $qty,
  ]);
} else {
  // 'set' - Absolute value (existing behavior)
  $stmt = $pdo->prepare(
    "INSERT INTO inventory (product_id, qty, synced_at)
     VALUES (:pid, :qty, NULL)
     ON DUPLICATE KEY UPDATE qty = :qty2, updated_at = CURRENT_TIMESTAMP, synced_at = NULL"
  );
  $stmt->execute([
    ':pid' => $productId,
    ':qty' => $qty,
    ':qty2' => $qty,
  ]);
}

// Price adjuster: update product price if requested
$validPriceActions = ['increase', 'decrease', 'set'];
if ($productId > 0 && in_array($priceAction, $validPriceActions, true) && $priceValue > 0) {
  // Fetch current price
  $priceStmt = $pdo->prepare("SELECT price FROM products WHERE id = :pid LIMIT 1");
  $priceStmt->execute([':pid' => $productId]);
  $currentPrice = (float)($priceStmt->fetchColumn() ?: 0);

  if ($priceType === 'percent') {
    // Percentage adjustment
    $percentChange = $priceValue / 100;
    if ($priceAction === 'increase') {
      $newPrice = $currentPrice * (1 + $percentChange);
    } elseif ($priceAction === 'decrease') {
      $newPrice = $currentPrice * (1 - $percentChange);
    } else {
      $newPrice = $priceValue; // set price - percent doesn't make sense, treat as flat
    }
  } else {
    // Flat amount adjustment
    if ($priceAction === 'increase') {
      $newPrice = $currentPrice + $priceValue;
    } elseif ($priceAction === 'decrease') {
      $newPrice = max(0, $currentPrice - $priceValue);
    } else {
      $newPrice = $priceValue; // set price to absolute value
    }
  }

  // Ensure price doesn't go below 0
  $newPrice = max(0, round($newPrice, 2));

  $updPrice = $pdo->prepare("UPDATE products SET price = :price WHERE id = :pid");
  $updPrice->execute([':price' => $newPrice, ':pid' => $productId]);
}

// Update product GST if provided
if ($productId > 0 && $gstRate !== null) {
  $gstRate = max(0.00, min(100.00, round($gstRate, 2)));
  $updGst = $pdo->prepare("UPDATE products SET gst_rate = :gst WHERE id = :pid");
  $updGst->execute([':gst' => $gstRate, ':pid' => $productId]);
}

// Return updated row
$invStmt = $pdo->prepare(
  "SELECT i.qty, i.updated_at, p.id AS product_id, p.name AS product, p.sku, p.category, p.price, p.size, p.gst_rate, p.logo_path
    FROM inventory i
    JOIN products p ON p.id = i.product_id
    WHERE i.product_id = :pid
    LIMIT 1"
);
  $invStmt->execute([':pid' => $productId]);
  $row = $invStmt->fetch();

  echo json_encode(['ok' => true, 'inventory' => $row]);
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
  exit;
}



