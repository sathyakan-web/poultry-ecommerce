<?php
/**
 * Cold chain simulator for the provider dashboard.
 *
 * POST -> writes ONE new "cold" reading per vaccine product and returns the
 *         complete dashboard feed (same JSON shape as api/iot_vaccines.php).
 *
 * Rules of the simulation:
 *   - a normal reading always stays inside the cold band (1.5 C .. 7.5 C),
 *     so a refresh can NEVER make a product warmer and can NEVER push a
 *     product over the 8.0 C warning limit;
 *   - the value wanders around a stable per product baseline, so the cards
 *     look like a live sensor feed instead of a frozen number;
 *   - products that are fed by a real device (source = 'sensor') are never
 *     touched, real measurements always win;
 *   - only a manual alert (api/iot_alert.php) can make a product hot.
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider', 'logistics'], true);

require_once __DIR__ . '/../auth/csrf.php';
sfs_csrf_check();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iot_telemetry.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

try {
  $pdo = db();

  // The simulation itself lives in api/iot_telemetry.php so the IoT page
  // feed (api/iot_readings.php) and this endpoint behave identically.
  $counts = iot_simulate_cold_readings($pdo);
  $simulated = (int)$counts['simulated'];
  $skipped = (int)$counts['skipped'];

  $payload = iot_telemetry_payload($pdo);
  $payload['simulated'] = $simulated;
  $payload['skipped'] = $skipped;
  $payload['message'] = $simulated . ' vaccine reading(s) refreshed';

  echo json_encode($payload);
} catch (Throwable $e) {
  error_log('[iot_simulate] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Could not refresh the simulated telemetry']);
}
