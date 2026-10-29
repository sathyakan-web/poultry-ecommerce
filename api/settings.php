<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_once __DIR__ . '/../auth/csrf.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

require_once __DIR__ . '/db.php';

try {
  $pdo = db();

  if ($method === 'GET') {
    $stmt = $pdo->query("SELECT `key`, `value`, updated_at FROM app_settings");
    $rows = $stmt->fetchAll();
    $settings = [];
    foreach ($rows as $row) {
      $settings[$row['key']] = $row['value'];
    }
    echo json_encode(['ok' => true, 'settings' => $settings]);
    exit;
  }

  if ($method === 'POST') {
    sfs_csrf_check();
    $role = sfs_current_role();
    if (!in_array($role, ['provider', 'admin'], true)) {
      echo json_encode(['ok' => false, 'error' => 'Forbidden']);
      http_response_code(403);
      exit;
    }

    $key = isset($_POST['key']) ? trim((string)$_POST['key']) : '';
    $value = isset($_POST['value']) ? (string)$_POST['value'] : '';

    if ($key === '') {
      echo json_encode(['ok' => false, 'error' => 'Missing key']);
      exit;
    }

    $allowedKeys = ['default_gst_rate'];
    if (!in_array($key, $allowedKeys, true)) {
      echo json_encode(['ok' => false, 'error' => 'Invalid setting key']);
      exit;
    }

    if ($key === 'default_gst_rate') {
      $value = max(0.00, min(100.00, round((float)$value, 2)));
      $value = number_format($value, 2, '.', '');
    }

    $stmt = $pdo->prepare(
      "INSERT INTO app_settings (`key`, `value`, updated_at)
       VALUES (:key, :value, NOW())
       ON DUPLICATE KEY UPDATE `value` = :value, updated_at = NOW()"
    );
    $stmt->execute([':key' => $key, ':value' => $value]);

    echo json_encode(['ok' => true, 'key' => $key, 'value' => $value]);
    exit;
  }

  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  http_response_code(405);
  exit;
} catch (Throwable $e) {
  error_log('[settings] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
