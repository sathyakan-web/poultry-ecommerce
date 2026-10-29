<?php
/**
 * Sends SMS using the TextBee gateway.
 *
 * Current TextBee API (https://textbee.dev/docs/api-reference):
 *   POST https://api.textbee.dev/api/v1/gateway/send-sms
 *   Header: x-api-key: <API_KEY>
 *   Body:   { "recipients": ["+91XXXXXXXXXX"], "message": "...", "deviceId": "<ID>" }
 *
 * Response (HTTP 200):
 *   { "data": { "success": true, "message": "...", "smsBatchId": "...",
 *               "recipientCount": 1, "successCount": 1, "failureCount": 0,
 *               "warning": { "code": "...", "message": "..." } } }
 *
 * A 200 only means TextBee accepted the batch; the sending phone still has
 * to be online. Anything else (400 disabled device / unverified email,
 * 401 bad key, 429 plan limit) is a hard failure and is raised as an
 * exception so callers can surface it instead of silently pretending the
 * message went out.
 *
 * The legacy device-scoped route (/gateway/devices/{id}/send-sms) is kept as
 * a fallback for accounts still pinned to the old API version.
 */

require_once __DIR__ . '/../db/config.php';

/**
 * Normalise a phone number to E.164 (+<country><number>).
 * Accepts 10-digit local numbers and already-formatted E.164 values.
 */
function sfs_sms_normalise_phone(string $phone): string {
  $raw = trim($phone);
  if ($raw === '') return '';

  $hasPlus = str_starts_with($raw, '+');
  $digits = preg_replace('/\D/', '', $raw);
  if ($digits === '') return '';

  if ($hasPlus) {
    return '+' . $digits;
  }

  $countryCode = defined('OTP_COUNTRY_CODE') ? (string)OTP_COUNTRY_CODE : '+91';
  $countryCode = '+' . preg_replace('/\D/', '', $countryCode);

  // Already contains the country code (e.g. 919876543210) -> keep as is.
  $ccDigits = ltrim($countryCode, '+');
  if ($ccDigits !== '' && strpos($digits, $ccDigits) === 0 && strlen($digits) > strlen($ccDigits)) {
    return $countryCode . substr($digits, strlen($ccDigits));
  }

  return $countryCode . $digits;
}

/** True when the gateway has both an API key and a device id available. */
function sfs_sms_is_configured(): bool {
  return defined('TEXTBEE_API_KEY') && TEXTBEE_API_KEY !== ''
      && defined('TEXTBEE_DEVICE_ID') && TEXTBEE_DEVICE_ID !== '';
}

/**
 * Send an SMS through TextBee.
 *
 * @throws RuntimeException on any gateway or transport failure.
 * @return bool true when TextBee accepted the message.
 */
function textbee_send_sms(string $toE164, string $message): bool {
  $apiKey = defined('TEXTBEE_API_KEY') ? (string)TEXTBEE_API_KEY : '';
  $deviceId = defined('TEXTBEE_DEVICE_ID') ? (string)TEXTBEE_DEVICE_ID : '';

  if ($apiKey === '' || $deviceId === '') {
    throw new RuntimeException('TextBee API key / Device ID not configured. Set TEXTBEE_API_KEY and TEXTBEE_DEVICE_ID in db/config.php, config/sfs/sfs_config.php or the environment.');
  }

  $toE164 = sfs_sms_normalise_phone($toE164);
  if ($toE164 === '' || !preg_match('/^\+[1-9]\d{7,14}$/', $toE164)) {
    throw new RuntimeException('Invalid recipient number in E.164 format: ' . $toE164);
  }

  $message = trim((string)$message);
  if ($message === '') {
    throw new RuntimeException('Refusing to send an empty SMS.');
  }

  $baseUrl = defined('TEXTBEE_BASE_URL') ? rtrim((string)TEXTBEE_BASE_URL, '/') : 'https://api.textbee.dev/api/v1';

  $payload = [
    'recipients' => [$toE164],
    'message'    => $message,
    'deviceId'   => $deviceId,
  ];

  // Preferred route (current API) with the legacy device route as fallback.
  $attempts = [
    $baseUrl . '/gateway/send-sms',
    $baseUrl . '/gateway/devices/' . rawurlencode($deviceId) . '/send-sms',
  ];

  $lastError = 'unknown error';
  $lastStatus = 0;
  $lastBody = '';

  foreach ($attempts as $index => $url) {
    $body = $index === 0
      ? json_encode($payload)
      : json_encode(['recipients' => $payload['recipients'], 'message' => $payload['message']]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json',
        'x-api-key: ' . $apiKey,
      ],
      CURLOPT_POSTFIELDS => $body,
      CURLOPT_CONNECTTIMEOUT => 10,
      CURLOPT_TIMEOUT => 30,
    ]);

    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false) {
      $lastError = 'curl error: ' . ($err !== '' ? $err : 'unknown');
      $lastStatus = 0;
      error_log('[textbee_send_sms] to=' . $toE164 . ' transport failure url=' . $url . ' err=' . $lastError);
      continue;
    }

    $lastStatus = $status;
    $lastBody = (string)$resp;

    if ($status < 200 || $status >= 300) {
      $lastError = 'HTTP ' . $status . ': ' . sfs_textbee_error_body($lastBody);
      // 404/405 means this route is not available on the account -> try the
      // legacy device-scoped route. Any other status is terminal.
      if ($status === 404 || $status === 405) {
        error_log('[textbee_send_sms] route unavailable (' . $status . ') url=' . $url . ' body=' . $lastBody);
        continue;
      }
      error_log('[textbee_send_sms] to=' . $toE164 . ' FAILED status=' . $status . ' body=' . $lastBody);
      throw new RuntimeException('TextBee HTTP error ' . $status . ': ' . sfs_textbee_error_body($lastBody));
    }

    // HTTP 200 -> inspect the payload, TextBee can still report a failure.
    $json = json_decode($lastBody, true);
    if (!is_array($json)) {
      error_log('[textbee_send_sms] to=' . $toE164 . ' status=200 unparseable body=' . $lastBody);
      throw new RuntimeException('TextBee returned an unreadable response.');
    }

    $data = is_array($json['data'] ?? null) ? $json['data'] : $json;

    if (isset($data['success']) && $data['success'] === false) {
      $msg = (string)($data['message'] ?? 'gateway reported failure');
      error_log('[textbee_send_sms] to=' . $toE164 . ' FAILED success=false body=' . $lastBody);
      throw new RuntimeException('TextBee rejected the message: ' . $msg);
    }

    $failureCount = isset($data['failureCount']) ? (int)$data['failureCount'] : 0;
    $successCount = isset($data['successCount']) ? (int)$data['successCount'] : null;
    $recipientCount = isset($data['recipientCount']) ? (int)$data['recipientCount'] : null;

    if ($failureCount > 0 && ($successCount === null || $successCount === 0)) {
      error_log('[textbee_send_sms] to=' . $toE164 . ' FAILED failureCount=' . $failureCount . ' body=' . $lastBody);
      throw new RuntimeException('TextBee could not push the message to the device (failureCount=' . $failureCount . ').');
    }

    $warning = '';
    if (is_array($data['warning'] ?? null)) {
      $warning = (string)($data['warning']['message'] ?? $data['warning']['code'] ?? '');
    }

    $line = '[textbee_send_sms] to=' . $toE164
      . ' status=' . $status
      . ' batch=' . (string)($data['smsBatchId'] ?? '-')
      . ' recipients=' . ($recipientCount ?? '-')
      . ' sent=' . ($successCount ?? '-')
      . ' failed=' . $failureCount
      . ' warning=' . ($warning !== '' ? $warning : 'none');
    error_log($line);

    if ($warning !== '') {
      // Accepted, but TextBee flagged a likely delivery problem.
      error_log('[textbee_send_sms] WARNING for ' . $toE164 . ': ' . $warning);
    }

    return true;
  }

  throw new RuntimeException('TextBee request failed: ' . $lastError . (($lastStatus > 0) ? ' (status ' . $lastStatus . ')' : '') . (($lastBody !== '') ? ' body=' . $lastBody : ''));
}

/**
 * Extract a human readable message out of a TextBee error body.
 */
function sfs_textbee_error_body(string $body): string {
  $body = trim($body);
  if ($body === '') return 'empty response';
  $json = json_decode($body, true);
  if (is_array($json)) {
    foreach (['error', 'message', 'detail', 'title'] as $key) {
      if (!empty($json[$key]) && is_string($json[$key])) {
        return $json[$key];
      }
    }
    if (!empty($json['data']) && is_array($json['data']) && !empty($json['data']['message'])) {
      return (string)$json['data']['message'];
    }
  }
  return mb_substr($body, 0, 300);
}
