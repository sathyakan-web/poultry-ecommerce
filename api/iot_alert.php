<?php
/**
 * Manual IoT cold-chain alert trigger (provider role).
 *
 * POST product_id  -> records a cold-chain excursion (the product gets hot),
 *                     shakes the card on the dashboard and sends a real SMS
 *                     through the TextBee gateway to the phone number that
 *                     belongs to the logged-in provider account.
 *
 * The only data the endpoint writes:
 *   - ONE temperature reading (source = 'alert') that represents the
 *     excursion the provider just triggered;
 *   - an audit_logs entry (event_type 'iot_alert_sms') that records who
 *     triggered the alert and when.
 *
 * It never touches the inventory quantity.
 *
 * The recipient number is resolved server-side from the session, it is never
 * accepted from the client (see api/send_temp_alert_sms.php for the older
 * endpoint that does accept a client supplied number).
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['provider', 'logistics'], true);

require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../auth/textbee_send_sms.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/iot_telemetry.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

sfs_csrf_check();

// Accept form-encoded bodies (like the other provider APIs) or JSON.
$input = $_POST;
if (empty($input)) {
  $raw = file_get_contents('php://input');
  $decoded = json_decode($raw, true);
  if (is_array($decoded)) $input = $decoded;
}

$productId = isset($input['product_id']) ? (int)$input['product_id'] : 0;
if ($productId <= 0) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'product_id is required']);
  exit;
}

// Minimum delay between two alerts for the same vaccine (gateway abuse guard).
// IOT_WARNING_LIMIT comes from api/iot_telemetry.php.
define('IOT_ALERT_THROTTLE_SECONDS', 30);

try {
  $pdo = db();

  $stmt = $pdo->prepare('SELECT id, sku, name, category FROM products WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $productId]);
  $product = $stmt->fetch();

  if (!$product || (string)$product['category'] !== 'vaccines') {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Vaccine not found']);
    exit;
  }

  // Server-side throttle: look at the most recent alerts for this product.
  // The age is computed by the database (TIMESTAMPDIFF against NOW()) so the
  // comparison never depends on the PHP/DB timezone offset.
  $recent = [];
  try {
    $throttleStmt = $pdo->query(
      "SELECT meta, TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age_seconds
       FROM audit_logs
       WHERE event_type = 'iot_alert_sms'
       ORDER BY id DESC
       LIMIT 50"
    );
    $recent = $throttleStmt->fetchAll();
  } catch (Throwable $e) {
    $recent = []; // audit_logs not deployed -> no throttle, still send.
  }

  foreach ($recent as $row) {
    $meta = json_decode((string)($row['meta'] ?? ''), true);
    if (!is_array($meta) || (int)($meta['product_id'] ?? 0) !== $productId) continue;
    $age = (int)($row['age_seconds'] ?? -1);
    if ($age >= 0 && $age < IOT_ALERT_THROTTLE_SECONDS) {
      $wait = IOT_ALERT_THROTTLE_SECONDS - $age;
      http_response_code(429);
      echo json_encode([
        'ok'        => false,
        'sms_sent'  => false,
        'error'     => 'An alert for this vaccine was already sent. Try again in ' . max(1, $wait) . 's.',
        'retry_in'  => max(1, $wait),
      ]);
      exit;
    }
    break;
  }

  // ---------------------------------------------------------------
  // Cold chain excursion
  //
  // Triggering the alert is the ONLY action that may make a product
  // hot. The excursion is stored as a normal reading (source = 'alert')
  // so the dashboard, the SMS message and the database all show the
  // very same temperature.
  // ---------------------------------------------------------------
  $lastReading = iot_last_temp_reading($pdo, (int)$productId);
  $excursionType = $lastReading ? $lastReading['reading_type'] : 'cold_room';
  if (!in_array($excursionType, ['cold_room', 'shelf_temp'], true)) {
    $excursionType = 'cold_room';
  }

  // Base of the excursion: the cold baseline of the product. Hot rows are
  // ignored, so repeated alerts always spike from the same cool baseline.
  $excursionBase = iot_cold_baseline($pdo, (int)$productId, (string)$product['sku']);
  $excursionTemp = $excursionBase + 4.5;
  if ($excursionTemp < 8.6) $excursionTemp = 8.6;          // always at least WARNING
  if (mt_rand() / mt_getrandmax() < 0.35) {                 // sometimes CRITICAL
    $excursionTemp = max(10.2, $excursionBase + 6.4);
  }
  $excursionTemp = round($excursionTemp, 2);

  iot_insert_reading(
    $pdo,
    (int)$productId,
    $excursionType,
    (float)$excursionTemp,
    iot_status_for((float)$excursionTemp),
    'alert'
  );
  iot_prune_readings($pdo, (int)$productId);

  // Latest stored temperature reading (server side truth, never a client
  // supplied value). Humidity readings are not temperatures, so they are
  // ignored here.
  $readingStmt = $pdo->prepare(
    'SELECT value, status, source, recorded_at
     FROM iot_readings
     WHERE product_id = :pid
       AND reading_type IN (\'cold_room\', \'shelf_temp\')
     ORDER BY recorded_at DESC, id DESC
     LIMIT 1'
  );
  $readingStmt->execute([':pid' => $productId]);
  $reading = $readingStmt->fetch();

  $temp = null;
  $readingStatus = 'OK';
  $readingSource = '';
  $recordedAt = '';
  if ($reading) {
    if ($reading['value'] !== null && $reading['value'] !== '') {
      $temp = (float)$reading['value'];
    }
    $readingStatus = strtoupper((string)$reading['status']);
    $readingSource = (string)($reading['source'] ?? '');
    $recordedAt = (string)($reading['recorded_at'] ?? '');
  }

  // Recipient: the phone number of the logged-in provider account.
  $user = sfs_current_user();
  $rawPhone = (string)($user['phone'] ?? '');
  $to = $rawPhone !== '' ? sfs_sms_normalise_phone($rawPhone) : '';
  if ($to === '' || !preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No valid phone number on your account']);
    exit;
  }

  $name = (string)$product['name'];
  $sku = (string)$product['sku'];

  // Use the database clock so the SMS timestamp matches the timestamps that
  // are stored with the telemetry readings.
  $dbNow = '';
  try {
    $dbNow = (string)$pdo->query('SELECT NOW()')->fetchColumn();
  } catch (Throwable $e) {
    $dbNow = '';
  }
  $when = $dbNow !== '' ? date('d M Y, h:i A', strtotime($dbNow)) : date('d M Y, h:i A');

  // Readings that were not produced by a real device are marked in the SMS
  // so the recipient knows the number comes from the simulated feed.
  $originNote = ($readingSource !== '' && $readingSource !== 'sensor') ? ' [simulated feed]' : '';

  if ($temp !== null && $temp > IOT_WARNING_LIMIT) {
    $message = 'SafeFlock IoT ALERT: Vaccine "' . $name . '" (SKU ' . $sku . ') is at '
      . number_format($temp, 1) . 'C, above the ' . number_format(IOT_WARNING_LIMIT, 1)
      . 'C cold-chain limit. Manual trigger by provider on ' . $when . '.' . $originNote;
  } elseif ($temp !== null) {
    $message = 'SafeFlock IoT ALERT: Manual cold-chain alert for vaccine "' . $name
      . '" (SKU ' . $sku . '). Current reading ' . number_format($temp, 1) . 'C (limit '
      . number_format(IOT_WARNING_LIMIT, 1) . 'C). Triggered by provider on ' . $when . '.' . $originNote;
  } else {
    $message = 'SafeFlock IoT ALERT: Manual cold-chain alert for vaccine "' . $name
      . '" (SKU ' . $sku . '). No telemetry reading available. Triggered by provider on '
      . $when . '.' . $originNote;
  }

  try {
    textbee_send_sms($to, $message);
  } catch (Throwable $e) {
    error_log('[iot_alert] SMS failed product=' . $productId . ' to=' . $to . ' err=' . $e->getMessage());
    http_response_code(502);
    echo json_encode([
      'ok'       => false,
      'sms_sent' => false,
      'error'    => 'Failed to send SMS: ' . $e->getMessage(),
    ]);
    exit;
  }

  // Audit trail - the excursion reading and the stock level are untouched.
  try {
    $audit = $pdo->prepare(
      'INSERT INTO audit_logs (event_type, user_id, phone, ip_address, user_agent, meta)
       VALUES (:event_type, :user_id, :phone, :ip, :ua, :meta)'
    );
    $audit->execute([
      ':event_type' => 'iot_alert_sms',
      ':user_id'    => (int)($user['user_id'] ?? 0) ?: null,
      ':phone'      => $rawPhone !== '' ? $rawPhone : null,
      ':ip'         => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
      ':ua'         => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
      ':meta'       => json_encode([
        'product_id' => $productId,
        'sku'        => $sku,
        'name'       => $name,
        'temp'       => $temp,
        'status'     => $readingStatus,
        'source'     => $readingSource,
        'to'         => $to,
      ], JSON_UNESCAPED_SLASHES),
    ]);
  } catch (Throwable $e) {
    error_log('[iot_alert] audit log failed: ' . $e->getMessage());
  }

  echo json_encode([
    'ok'       => true,
    'sms_sent' => true,
    'to'       => $to,
    'product'  => [
      'id'   => $productId,
      'sku'  => $sku,
      'name' => $name,
    ],
    'temp'     => $temp,
    'status'   => $readingStatus,
    // The new reading is sent back so the dashboard can repaint the card
    // with the hot temperature straight away.
    'reading'  => [
      'temp'         => $temp,
      'status'       => $readingStatus,
      'source'       => $readingSource,
      'recorded_at'  => $recordedAt,
      'age_seconds'  => 0,
      'has_reading'  => $temp !== null,
    ],
    'message'  => $message,
    'sent_at'  => $dbNow !== '' ? $dbNow : date('Y-m-d H:i:s'),
  ]);
} catch (Throwable $e) {
  error_log('[iot_alert] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Could not process the alert']);
}
