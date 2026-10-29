<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);

require_once __DIR__ . '/../auth/audit.php';
require_once __DIR__ . '/db.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

// If token comes URL-encoded (e.g. VCX%7CBATCH), decode it so DB lookup matches.
$token = rawurldecode($token);


// QR payloads sometimes come with extra whitespace/newlines or with a structured prefix.
// We DO NOT drop the "|..." part, because in this demo the token itself is stored like:
//   VCX-NEW-001|BATCH-7A92
$token = preg_replace('/\s+/', '', $token); // remove all whitespace characters

if ($token === '') {
  echo json_encode(['ok' => false, 'error' => 'Missing token']);
  exit;
}

$pdo = db();


$stmt = $pdo->prepare("SELECT t.token, t.batch_no, t.iot_status,
                               p.name AS product, p.category, p.sku
                        FROM qr_tokens t
                        JOIN products p ON p.id = t.product_id
                        WHERE t.token = :token");
$stmt->execute([':token' => $token]);
$row = $stmt->fetch();

if (!$row) {
  echo json_encode(['ok' => false, 'error' => 'Invalid QR token']);
  sfs_audit('qr_scan', ['token' => $token, 'result' => 'invalid']);
  exit;
}

// Inventory qty for that product
$prodStmt = $pdo->prepare("SELECT p.id FROM products p WHERE p.sku = :sku LIMIT 1");
$prodStmt->execute([':sku' => $row['sku']]);
$prodId = (int)$prodStmt->fetchColumn();

$invStmt = $pdo->prepare("SELECT qty, updated_at FROM inventory WHERE product_id = :pid LIMIT 1");
$invStmt->execute([':pid' => $prodId]);
$inv = $invStmt->fetch();

// Load logo_path for the verified product (used by verified_product.php).
$logoPath = '';
try{
  $logoStmt = $pdo->prepare('SELECT logo_path FROM products WHERE sku = :sku LIMIT 1');
  $logoStmt->execute([':sku' => $row['sku']]);
  $logoRow = $logoStmt->fetch();
  if($logoRow && isset($logoRow['logo_path'])){
    $logoPath = (string)($logoRow['logo_path'] ?? '');
  }
}catch (Throwable $e){}

$row['logo_path'] = $logoPath;

// Also include a fallback "product_image" for frontend convenience.
$row['product_image'] = $logoPath;

echo json_encode(['ok' => true, 'message' => 'Genuine product of SafeFlock Solutions', 'verification' => $row, 'inventory' => $inv]);
sfs_audit('qr_scan', ['token' => $token, 'product_id' => $row['product_id'] ?? null, 'result' => 'verified']);




