<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iot_telemetry.php';

$pdo = db();

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$vaccinesOnly = isset($_GET['vaccines_only']) && (string)$_GET['vaccines_only'] === '1';

// The IoT page (iot.php) reloads this feed itself every 15 seconds. For
// provider / logistics accounts the feed is refreshed with one new COLD
// reading per vaccine, so that page keeps showing live temperatures.
// The simulation never raises a temperature (cold band 1.5 C .. 7.5 C) and it
// never sends an alert - only api/iot_alert.php can do that.
// Single product lookups (the product modal) stay strictly read-only.
if ($productId === 0
    && in_array(sfs_current_role(), ['provider', 'logistics'], true)
    && iot_simulate_is_due($pdo)) {
  try {
    iot_simulate_cold_readings($pdo);
  } catch (Throwable $e) {
    // The feed must never break because of the simulator (for example when
    // the iot_readings.source column is not deployed yet): the page keeps
    // showing the stored readings instead.
    error_log('[iot_readings] simulation skipped: ' . $e->getMessage());
  }
}

if ($productId > 0) {
  $sql = "SELECT reading_type, value, status, recorded_at
          FROM iot_readings
          WHERE product_id = :pid
          ORDER BY recorded_at DESC
          LIMIT 50";
  if ($vaccinesOnly) {
    // Ensure the chosen product is a vaccine.
    $sql = "SELECT ir.reading_type, ir.value, ir.status, ir.recorded_at
            FROM iot_readings ir
            JOIN products p ON p.id = ir.product_id
            WHERE ir.product_id = :pid AND p.category = 'vaccines'
            ORDER BY ir.recorded_at DESC
            LIMIT 50";
  }

  $stmt = $pdo->prepare($sql);
  $stmt->execute([':pid' => $productId]);
  $rows = $stmt->fetchAll();
} else {
  if ($vaccinesOnly) {
    $stmt = $pdo->query("SELECT p.id AS product_id, p.name AS product_name, p.sku, ir.reading_type, ir.value, ir.status, ir.recorded_at
                         FROM iot_readings ir
                         JOIN products p ON p.id = ir.product_id
                         WHERE p.category = 'vaccines'
                         ORDER BY ir.recorded_at DESC
                         LIMIT 200");
  } else {
    $stmt = $pdo->query("SELECT product_id, reading_type, value, status, recorded_at
                         FROM iot_readings
                         ORDER BY recorded_at DESC
                         LIMIT 100");
  }
  $rows = $stmt->fetchAll();
}


echo json_encode(['ok' => true, 'readings' => $rows]);


