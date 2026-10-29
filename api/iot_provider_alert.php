<?php
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);
require_role(['customer', 'provider', 'logistics'], true);
require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../auth/rbac.php';
require_once __DIR__ . '/../auth/textbee_send_sms.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$user = sfs_current_user();
$phone = preg_replace('/\D/', '', (string)($user['phone'] ?? ''));
if ($phone === '' || !preg_match('/^\d{10}$/', $phone)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No valid phone on account']);
    exit;
}

$location = isset($_POST['location']) ? (string)$_POST['location'] : 'IoT Cold Chain';
$temperature = isset($_POST['temperature']) ? (string)$_POST['temperature'] : 'N/A';

$to = sfs_sms_normalise_phone($phone);
$message = "Poultry Alert: Cold storage temperature at {$location} reached {$temperature}°C, exceeding safe limits. Immediate action required.";

try {
    textbee_send_sms($to, $message);
    echo json_encode(['ok' => true, 'sms_sent' => true]);
} catch (Throwable $e) {
    error_log('[iot_provider_alert] ' . $e->getMessage());
    http_response_code(502);
    echo json_encode(['ok' => false, 'sms_sent' => false, 'error' => $e->getMessage()]);
}
