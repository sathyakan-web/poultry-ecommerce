<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/db.php';

// Simple DB-backed cart per user (one row per product)
// Cart is for adding products from UI; qty is count.

$pdo = db();
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
  echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
  exit;
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : (isset($_GET['action']) ? trim((string)$_GET['action']) : 'get');

try {
  if ($action === 'add') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
    if ($productId <= 0) throw new Exception('Invalid product_id');
    if ($qty <= 0) $qty = 1;

    // If product doesn't exist, reject
    // (Newly added products might exist in UI but not yet in products table / wrong id.)
    $chk = $pdo->prepare('SELECT id FROM products WHERE id = :pid LIMIT 1');
    $chk->execute([':pid' => $productId]);
    if (!$chk->fetch()) {
      // Provide clearer error to help debug newly added products.
      throw new Exception('Product not found for product_id=' . $productId);
    }

    // Check inventory availability before adding to cart
    $invStmt = $pdo->prepare('SELECT qty FROM inventory WHERE product_id = :pid LIMIT 1');
    $invStmt->execute([':pid' => $productId]);
    $invRow = $invStmt->fetch();
    $availQty = $invRow ? (int)$invRow['qty'] : 0;

    if ($availQty <= 0) {
      throw new Exception('This product is out of stock — we will be in touch');
    }

    if ($qty > $availQty) {
      throw new Exception('Only ' . $availQty . ' units available for this product');
    }

    // UPSERT
    $stmt = $pdo->prepare(
      'INSERT INTO carts (user_id, product_id, qty)
       VALUES (:uid, :pid, :qty)
        ON DUPLICATE KEY UPDATE qty = qty + :qty'
    );
    $stmt->execute([':uid' => $userId, ':pid' => $productId, ':qty' => $qty]);

    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'remove') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    if ($productId <= 0) throw new Exception('Invalid product_id');

    $stmt = $pdo->prepare('DELETE FROM carts WHERE user_id = :uid AND product_id = :pid');
    $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    echo json_encode(['ok' => true]);
    exit;
  }

  if ($action === 'set') {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 0;
    if ($productId <= 0) throw new Exception('Invalid product_id');
    if ($qty < 0) $qty = 0;

    if ($qty === 0) {
      $stmt = $pdo->prepare('DELETE FROM carts WHERE user_id = :uid AND product_id = :pid');
      $stmt->execute([':uid' => $userId, ':pid' => $productId]);
    } else {
      $stmt = $pdo->prepare(
        'INSERT INTO carts (user_id, product_id, qty)
         VALUES (:uid, :pid, :qty)
         ON DUPLICATE KEY UPDATE qty = :qty'
      );
      $stmt->execute([':uid' => $userId, ':pid' => $productId, ':qty' => $qty]);
    }

    echo json_encode(['ok' => true]);
    exit;
  }

  // get cart
  $stmt = $pdo->prepare(
    'SELECT c.product_id, c.qty, p.sku, p.name, p.category, p.description, p.price, p.size, p.logo_path
     FROM carts c
     JOIN products p ON p.id = c.product_id
     WHERE c.user_id = :uid
     ORDER BY c.id DESC'
  );
  $stmt->execute([':uid' => $userId]);
  $rows = $stmt->fetchAll();
  echo json_encode(['ok' => true, 'cart' => $rows]);
  exit;
} catch (Throwable $e) {
  echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
  exit;
}


