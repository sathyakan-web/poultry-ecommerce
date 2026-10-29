<?php
/**
 * SMS gateway diagnostics.
 *
 * Usage:
 *   php tools/sms_test.php <phone> ["custom message"]   send a test SMS
 *   php tools/sms_test.php --status <smsBatchId>        check delivery of a batch
 *   php tools/sms_test.php --history <phone>            list recent messages
 *   php tools/sms_test.php                              configuration check only
 *
 * Examples:
 *   php tools/sms_test.php 9876543210
 *   php tools/sms_test.php --history 9876543210
 */

if (PHP_SAPI !== 'cli') {
  http_response_code(403);
  echo "CLI only.\n";
  exit(1);
}

require_once __DIR__ . '/../auth/textbee_send_sms.php';

function textbee_get(string $path): array {
  $base = defined('TEXTBEE_BASE_URL') ? rtrim((string)TEXTBEE_BASE_URL, '/') : 'https://api.textbee.dev/api/v1';
  $ch = curl_init($base . $path);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'x-api-key: ' . TEXTBEE_API_KEY,
      'Accept: application/json',
    ],
    CURLOPT_TIMEOUT => 30,
  ]);
  $body = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  return [$code, (string)$body];
}

$mode = $argv[1] ?? '';

echo "== TextBee SMS test ==\n";
echo "base url   : " . TEXTBEE_BASE_URL . "\n";
echo "device id  : " . (TEXTBEE_DEVICE_ID !== '' ? TEXTBEE_DEVICE_ID : '(missing)') . "\n";
echo "api key    : " . (TEXTBEE_API_KEY !== '' ? substr(TEXTBEE_API_KEY, 0, 8) . '...' : '(missing)') . "\n";
echo "configured : " . (sfs_sms_is_configured() ? 'yes' : 'NO') . "\n";

if ($mode === '--status') {
  $batch = $argv[2] ?? '';
  if ($batch === '') { echo "\nMissing smsBatchId.\n"; exit(1); }
  [$code, $body] = textbee_get('/gateway/messages?smsBatchId=' . rawurlencode($batch));
  echo "\nHTTP $code\n";
  $json = json_decode($body, true);
  foreach (($json['data'] ?? []) as $m) {
    echo "recipient : " . ($m['recipient'] ?? '?') . "\n";
    echo "status    : " . ($m['status'] ?? '?') . "\n";
    echo "message   : " . ($m['message'] ?? '') . "\n";
  }
  exit(0);
}

if ($mode === '--history') {
  $phone = $argv[2] ?? '';
  if ($phone === '') { echo "\nMissing phone.\n"; exit(1); }
  $to = sfs_sms_normalise_phone($phone);
  [$code, $body] = textbee_get('/gateway/messages?direction=all&limit=20');
  echo "\nHTTP $code (messages to/from $to)\n";
  $json = json_decode($body, true);
  $rows = 0;
  foreach (($json['data'] ?? []) as $m) {
    if (($m['recipient'] ?? '') !== $to && ($m['sender'] ?? '') !== $to) continue;
    $rows++;
    printf("  %-11s %-10s %s\n", $m['status'] ?? '?', $m['direction'] ?? '?', substr(preg_replace('/\s+/', ' ', (string)($m['message'] ?? '')), 0, 60));
  }
  if ($rows === 0) echo "  (no messages found)\n";
  exit(0);
}

$phone = ($mode === '' ? '' : $mode);
$message = (isset($argv[2]) && trim((string)$argv[2]) !== '' && !str_starts_with($mode, '--'))
  ? (string)$argv[2]
  : 'SafeFlock Solution: SMS gateway test. ' . date('d M Y H:i:s');

if ($phone === '') {
  echo "\nNo phone supplied. Configuration check only.\n";
  exit(sfs_sms_is_configured() ? 0 : 1);
}

$to = sfs_sms_normalise_phone($phone);
echo "recipient  : $phone -> $to\n";

if (!preg_match('/^\+[1-9]\d{7,14}$/', $to)) {
  echo "RESULT: FAILED - not a valid E.164 number\n";
  exit(1);
}

try {
  $ok = textbee_send_sms($to, $message);
  echo "RESULT: " . ($ok ? 'OK - TextBee accepted the message' : 'FAILED') . "\n";
  echo "message   : $message\n";
  exit($ok ? 0 : 1);
} catch (Throwable $e) {
  echo "RESULT: FAILED - " . $e->getMessage() . "\n";
  exit(1);
}
