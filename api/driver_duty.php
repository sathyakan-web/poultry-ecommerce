<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();
require_once __DIR__ . '/../auth/audit.php';
require_once __DIR__ . '/db.php';

$userId = (int)($_SESSION['user_id'] ?? 0);

function ensureDutyRow(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT id, status, clock_in_at, clock_out_at FROM driver_duty WHERE driver_id = :uid FOR UPDATE');
  $stmt->execute([':uid' => $userId]);
  $row = $stmt->fetch();
  if ($row) return $row;

  $ins = $pdo->prepare('INSERT INTO driver_duty (driver_id, status, clock_in_at, clock_out_at) VALUES (:uid, "OFF_DUTY", NULL, NULL)');
  $ins->execute([':uid' => $userId]);

  $stmt->execute([':uid' => $userId]);
  return $stmt->fetch();
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : 'current_status';

try {
  $pdo = db();
  if ($action === 'current_status') {
    $row = ensureDutyRow($pdo, $userId);
    echo json_encode([
      'ok' => true,
      'status' => $row['status'] ?? 'OFF_DUTY',
      'clock_in_at' => $row['clock_in_at'] ?? null,
      'clock_out_at' => $row['clock_out_at'] ?? null,
    ]);
    exit;
  }

  if ($action === 'toggle') {
    $row = ensureDutyRow($pdo, $userId);
    $current = $row['status'] ?? 'OFF_DUTY';

    if ($current === 'ON_DUTY') {
      $upd = $pdo->prepare('UPDATE driver_duty SET status = "OFF_DUTY", clock_out_at = NOW() WHERE driver_id = :uid');
      $upd->execute([':uid' => $userId]);
      $newStatus = 'OFF_DUTY';
      $clockIn = $row['clock_in_at'];
      $clockOut = date('Y-m-d H:i:s');
    } else {
      $upd = $pdo->prepare('UPDATE driver_duty SET status = "ON_DUTY", clock_in_at = NOW(), clock_out_at = NULL WHERE driver_id = :uid');
      $upd->execute([':uid' => $userId]);
      $newStatus = 'ON_DUTY';
      $clockIn = date('Y-m-d H:i:s');
      $clockOut = null;
    }

    sfs_audit('driver_duty_toggled', [
      'driver_id' => $userId,
      'new_status' => $newStatus,
      'clock_in_at' => $clockIn,
      'clock_out_at' => $clockOut,
    ]);

    echo json_encode([
      'ok' => true,
      'status' => $newStatus,
      'clock_in_at' => $clockIn,
      'clock_out_at' => $clockOut,
    ]);
    exit;
  }

  echo json_encode(['ok' => false, 'error' => 'Unknown action']);
  exit;
} catch (Throwable $e) {
  error_log('[driver_duty] ' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Something went wrong. Please try again.']);
  exit;
}
