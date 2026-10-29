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

require_once __DIR__ . '/csrf.php';

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/textbee_send_sms.php';

// Ensure clean JSON output even on fatal errors
if (ob_get_level()) ob_clean();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
  exit;
}

sfs_csrf_check();

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';
$role = isset($_POST['role']) ? strtolower(trim((string)$_POST['role'])) : 'customer';
$allowed = ['customer', 'provider', 'logistics', 'guest'];
if (!in_array($role, $allowed, true)) $role = 'customer';
$_SESSION['pending_otp_role'] = $role;

sfs_debug_log('request_otp_start', ['phone' => $phone, 'role' => $role]);

if (!preg_match('/^\d{10}$/', $phone)) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => 'Phone/ID must be exactly 10 digits.']);
  exit;
}

// Always generate a new OTP for each request.
$otp = (string)random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

$expiresMinutes = defined('OTP_EXPIRES_MINUTES') ? (int)OTP_EXPIRES_MINUTES : 5;
$expiresAt = (new DateTime('now'))->modify('+' . $expiresMinutes . ' minutes')->format('Y-m-d H:i:s');



try {
  $pdo = db();



  // Daily limit: max 50 OTP SMS per phone per day (no time-window throttling).
  $stmtCnt = $pdo->prepare(
    'SELECT COUNT(*) AS cnt
     FROM otp_requests
     WHERE phone = :phone AND created_at >= CURDATE() AND created_at < (CURDATE() + INTERVAL 1 DAY)'
  );
  $stmtCnt->execute([':phone' => $phone]);
  $cnt = (int)($stmtCnt->fetch()['cnt'] ?? 0);
  $dailyLimit = 50;
  if ($cnt >= $dailyLimit) {
    http_response_code(429);
    echo json_encode(['ok' => false, 'error' => 'Daily OTP limit reached. Try again tomorrow.']);
    exit;
  }

  $check = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
  $check->execute([':phone' => $phone]);
  $found = $check->fetch();
  sfs_debug_log('request_otp_user_check', ['phone' => $phone, 'found' => (bool)$found]);

  if (!$found) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Phone number is not registered.']);
    exit;
  }


  // Store OTP as latest record (insert new row, verification will use newest valid row).
  // This ensures OTP changes every time.
  $stmt = $pdo->prepare(
    'INSERT INTO otp_requests (phone, otp_hash, expires_at, attempt_count)
     VALUES (:phone, :otp_hash, :expires_at, 0)'
  );
  $stmt->execute([
    ':phone' => $phone,
    ':otp_hash' => $otpHash,
    ':expires_at' => $expiresAt,
  ]);
  $otpRowId = $pdo->lastInsertId();

  // Send SMS via Textbee
  $countryCode = defined('OTP_COUNTRY_CODE') ? OTP_COUNTRY_CODE : '+91';
  $to = rtrim($countryCode) . $phone;

  $msg = 'SafeFlock Solution OTP: ' . $otp;

  // Send the OTP SMS. If the gateway fails (device offline, API error, etc.)
  // the error is surfaced to the client instead of pretending the SMS went
  // out, because otherwise the user waits forever for a message that will
  // never arrive.
  $smsSent = false;
  $smsError = '';
  try {
    textbee_send_sms($to, $msg);
    $smsSent = true;
  } catch (Throwable $e) {
    $smsError = $e->getMessage();
    error_log('[request_otp] SMS send failed for ' . $to . ': ' . $smsError);
  }

  $resp = ['ok' => true, 'sms_sent' => $smsSent];

  // Dev fallback: expose the OTP only on localhost when SMS is unavailable.
  // Never expose demo_otp in production.
  $isLocal = isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'], true);
  if (!$smsSent && $isLocal) {
    $resp['warning'] = 'SMS gateway unavailable. Using demo OTP (local only).';
    $resp['demo_otp'] = $otp;
    $resp['sms_error'] = $smsError;
  } elseif (!$smsSent) {
    // Production: the OTP is useless without the SMS, so fail loudly and
    // let the user fall back to passcode / biometric login.
    $pdo->prepare('DELETE FROM otp_requests WHERE id = :id')
        ->execute([':id' => (int)$otpRowId]);
    http_response_code(502);
    echo json_encode([
      'ok' => false,
      'sms_sent' => false,
      'error' => 'Could not send the OTP SMS. Please try again, or sign in with your passcode.',
      'sms_error' => $smsError,
    ]);
    exit;
  }

  echo json_encode($resp);
  exit;
} catch (Throwable $e) {
  // Keep frontend safe: don’t reveal otp
  http_response_code(500);
  error_log('[request_otp] Failed phone=' . ($phone ?? 'null') . ' err=' . $e->getMessage());
  echo json_encode(['ok' => false, 'error' => 'Failed to send OTP: ' . $e->getMessage()]);

  exit;
}



