<?php
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
require_role(['customer', 'provider', 'logistics'], true);
require_once __DIR__ . '/../auth/csrf.php';
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../auth/textbee_send_sms.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

sfs_csrf_check();

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';
$location = isset($_POST['location']) ? (string)$_POST['location'] : 'Unknown Location';
$temperature = isset($_POST['temperature']) ? (string)$_POST['temperature'] : 'N/A';
$limit = isset($_POST['limit']) ? (string)$_POST['limit'] : '8.0°C';

if ($phone === '' || !preg_match('/^\d{10}$/', $phone)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Valid 10-digit phone number is required.']);
  exit;
}

$to = sfs_sms_normalise_phone($phone);

$message = "Poultry Alert: Cold storage temperature at {$location} reached {$temperature}°C, exceeding the safe limit of {$limit}. Immediate action required to prevent vaccine/medication spoilage.";

try {
  textbee_send_sms($to, $message);
  echo json_encode(['ok' => true, 'sms_sent' => true]);
} catch (Throwable $e) {
  error_log('[send_temp_alert_sms] Failed phone=' . $to . ' err=' . $e->getMessage());
  http_response_code(502);
  echo json_encode(['ok' => false, 'sms_sent' => false, 'error' => 'Failed to send SMS: ' . $e->getMessage()]);
}

