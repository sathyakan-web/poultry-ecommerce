<?php
/**
 * IoT vaccine telemetry for the provider dashboard.
 *
 * GET  -> one row per real vaccine product (products.category = 'vaccines')
 *         joined with its stock level and its LATEST iot_readings row.
 *
 * This endpoint is strictly read-only: it never generates, changes or stores
 * a temperature. The values returned are the ones physically stored in the
 * database (uploaded through api/iot_upload.php or produced by the cold
 * chain simulator api/iot_simulate.php).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iot_telemetry.php';

try {
  $pdo = db();
  echo json_encode(iot_telemetry_payload($pdo));
} catch (Throwable $e) {
  error_log('[iot_vaccines] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Could not load vaccine telemetry: ' . $e->getMessage()]);
}
