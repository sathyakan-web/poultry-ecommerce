<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider', 'logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/db.php';
$pdo = db();

// Accept POST form-data or JSON body.
$input = $_POST;
if (empty($input)) {
  $raw = file_get_contents('php://input');
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) $input = $decoded;
}

$sku = isset($input['sku']) ? trim((string)$input['sku']) : '';
$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
$readingType = isset($input['reading_type']) ? trim((string)$input['reading_type']) : '';
$value = isset($input['value']) ? (string)$input['value'] : '';
$status = isset($input['status']) ? trim((string)$input['status']) : '';

// We only care about cold room and shelf temp in your request.
$allowedReadingTypes = ['cold_room', 'shelf_temp', 'humidity'];
if ($readingType === '' || !in_array($readingType, $allowedReadingTypes, true)) {
  echo json_encode(['ok' => false, 'error' => 'Invalid reading_type']);
  exit;
}

if ($value === '' || !is_numeric($value)) {
  echo json_encode(['ok' => false, 'error' => 'Invalid value']);
  exit;
}

if ($status === '' || !in_array($status, ['OK', 'WARNING', 'CRITICAL'], true)) {
  $status = 'OK';
}

// Resolve product_id from sku when product_id is not supplied.
if ($productId <= 0) {
  if ($sku === '') {
    echo json_encode(['ok' => false, 'error' => 'Provide either sku or product_id']);
    exit;
  }
  $stmt = $pdo->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
  $stmt->execute([':sku' => $sku]);
  $productId = (int)$stmt->fetchColumn();
}

if ($productId <= 0) {
  echo json_encode(['ok' => false, 'error' => 'Product not found']);
  exit;
}

// Insert evidence.
$stmt = $pdo->prepare('INSERT INTO iot_readings (product_id, reading_type, value, status) VALUES (:pid, :rt, :val, :st)');
$stmt->execute([
  ':pid' => $productId,
  ':rt' => $readingType,
  ':val' => $value,
  ':st' => $status,
]);

echo json_encode(['ok' => true, 'product_id' => $productId, 'reading_type' => $readingType, 'value' => (float)$value, 'status' => $status]);


