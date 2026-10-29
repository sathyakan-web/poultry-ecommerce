<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$secret = '';
if (isset($_SERVER['HTTP_X_SFS_SYNC_SECRET'])) {
    $secret = (string)$_SERVER['HTTP_X_SFS_SYNC_SECRET'];
} elseif (isset($_POST['sync_secret'])) {
    $secret = (string)$_POST['sync_secret'];
}

$secretValid = false;
if (defined('SFS_SYNC_SECRET') && $secret !== '' && trim($secret) === trim(SFS_SYNC_SECRET)) {
    $secretValid = true;
}

if (!$secretValid) {
    http_response_code(401);
    echo json_encode([
        'ok' => false,
        'error' => 'Invalid sync secret',
        'received' => $secret !== '' ? '[hidden]' : 'missing',
        'expected_length' => defined('SFS_SYNC_SECRET') ? strlen(SFS_SYNC_SECRET) : 'undefined',
        'hint' => 'Make sure SFS_SYNC_SECRET matches in both localhost and live db/config.php'
    ]);
    exit;
}

require_once __DIR__ . '/db.php';

$pdo = db();

$input = json_decode(file_get_contents('php://input'), true);
$products = $input['products'] ?? [];

if (!is_array($products) || empty($products)) {
    echo json_encode(['ok' => true, 'created' => 0, 'skipped' => 0, 'qty_updated' => 0, 'errors' => []]);
    exit;
}

$created = 0;
$skipped = 0;
$qtyUpdated = 0;
$errors = [];

foreach ($products as $row) {
    try {
        $sku = strtoupper(trim((string)($row['sku'] ?? '')));
        $name = trim((string)($row['name'] ?? ''));

        if ($sku === '' && $name === '') {
            $errors[] = "Skipped product id {$row['id']}: missing SKU and name";
            continue;
        }

        $matchStmt = $pdo->prepare('SELECT id FROM products WHERE sku = :sku OR name = :name LIMIT 1');
        $matchStmt->execute([':sku' => $sku ?: $name, ':name' => $name]);
        $liveProduct = $matchStmt->fetch();

        if ($liveProduct) {
            $skipped++;
            if (isset($row['qty'])) {
                $qty = (int)$row['qty'];
                $upd = $pdo->prepare('UPDATE inventory SET qty = :qty, updated_at = NOW() WHERE product_id = :pid');
                $upd->execute([':qty' => $qty, ':pid' => $liveProduct['id']]);
                $qtyUpdated++;
            }
            continue;
        }

        $sku = $sku ?: 'AUTO-' . date('Ymd') . '-' . strtoupper(substr(md5((string)$row['id'] . microtime(true)), 0, 4));

        $pdo->prepare('INSERT INTO products (sku, name, category, description, logo_path, price, size, gst_rate, created_at)
                      VALUES (:sku, :name, :category, :description, :logo_path, :price, :size, :gst_rate, NOW())')
           ->execute([
               ':sku' => $sku,
               ':name' => $name,
               ':category' => $row['category'] ?? 'feed_meds',
               ':description' => $row['description'] ?? '',
               ':logo_path' => $row['logo_path'] ?? null,
               ':price' => $row['price'] ?? 0,
               ':size' => $row['size'] ?? '',
               ':gst_rate' => $row['gst_rate'] ?? 5.00,
           ]);

        $productId = (int)$pdo->lastInsertId();

        $qty = (int)($row['qty'] ?? 0);
        $pdo->prepare('INSERT INTO inventory (product_id, qty, updated_at)
                       VALUES (:pid, :qty, NOW())')
            ->execute([':pid' => $productId, ':qty' => $qty]);

        $created++;
    } catch (Exception $e) {
        $errors[] = "Product id {$row['id']}: " . $e->getMessage();
    }
}

echo json_encode([
    'ok' => empty($errors),
    'created' => $created,
    'skipped' => $skipped,
    'qty_updated' => $qtyUpdated,
    'errors' => $errors,
]);
