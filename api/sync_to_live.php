<?php
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider'], true);

require_once __DIR__ . '/db.php';

$local = db();

$created = 0;
$skipped = 0;
$qtyUpdated = 0;
$errors = [];

try {
    $pending = $local->query("
        SELECT p.id, p.sku, p.name, p.category, p.description, p.logo_path, p.price, p.size, p.gst_rate, p.updated_at,
               i.qty, i.updated_at AS inv_updated_at
        FROM products p
        LEFT JOIN inventory i ON i.product_id = p.id
        WHERE p.synced_at IS NULL OR p.synced_at < p.updated_at
        ORDER BY p.id ASC
    ")->fetchAll();
} catch (Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, "Unknown column 'p.synced_at'") !== false || strpos($msg, "Unknown column 'synced_at'") !== false) {
        $pending = $local->query("
            SELECT p.id, p.sku, p.name, p.category, p.description, p.logo_path, p.price, p.size, p.gst_rate, p.updated_at,
                   i.qty, i.updated_at AS inv_updated_at
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            ORDER BY p.id ASC
        ")->fetchAll();
    } else {
        echo json_encode(['ok' => false, 'error' => 'Failed to load pending products: ' . $msg]);
        exit;
    }
}

if (empty($pending)) {
    echo json_encode(['ok' => true, 'created' => 0, 'skipped' => 0, 'qty_updated' => 0, 'errors' => []]);
    exit;
}

$liveUrl = 'https://SAFEFLOCKSOLUTIONS.freedev.app/api/sync_from_local.php';

try {
    $ch = curl_init($liveUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-SFS-SYNC-SECRET: ' . trim(SFS_SYNC_SECRET),
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Referer: https://SAFEFLOCKSOLUTIONS.freedev.app/',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['products' => $pending]));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Network error: ' . $curlErr);
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['ok'])) {
        throw new Exception('Invalid response from live server. HTTP: ' . $httpCode . ' Body: ' . substr($response, 0, 300));
    }

    if ($data['ok'] && !empty($pending)) {
        $firstProductId = (int)$pending[0]['id'];
        $lastProductId = (int)$pending[count($pending) - 1]['id'];

        $local->prepare('UPDATE products SET synced_at = NOW() WHERE id >= :first AND id <= :last')->execute([
            ':first' => $firstProductId,
            ':last' => $lastProductId,
        ]);

        $local->prepare('UPDATE inventory SET synced_at = NOW() WHERE product_id >= :first AND product_id <= :last')->execute([
            ':first' => $firstProductId,
            ':last' => $lastProductId,
        ]);
    }

    echo json_encode([
        'ok' => $data['ok'],
        'created' => $data['created'] ?? 0,
        'skipped' => $data['skipped'] ?? 0,
        'qty_updated' => $data['qty_updated'] ?? 0,
        'errors' => $data['errors'] ?? [],
    ]);
} catch (Exception $e) {
    echo json_encode(['ok' => false, 'error' => 'Sync failed: ' . $e->getMessage()]);
}
