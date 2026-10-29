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
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid product id']);
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('DELETE FROM iot_readings WHERE product_id = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM inventory WHERE product_id = :id')->execute([':id' => $id]);
    $pdo->prepare('DELETE FROM products WHERE id = :id')->execute([':id' => $id]);
    $pdo->commit();
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('[inventory_remove] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Remove failed']);
}
