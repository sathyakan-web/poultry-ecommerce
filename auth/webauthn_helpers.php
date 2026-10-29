<?php
// ============================================================
// WebAuthn helpers for SafeFlock Solution login
// ============================================================

require_once __DIR__ . '/../db/config.php';
require_once __DIR__ . '/../api/db.php';

// Set error handler to ensure JSON responses even on fatal errors
set_error_handler(function($severity, $message, $file, $line) {
  if (!(error_reporting() & $severity)) return false;
  throw new ErrorException($message, 0, $severity, $file, $line);
});

function sfs_webauthn_random(int $len = 32): string {
  try {
    return random_bytes($len);
  } catch (Exception $e) {
    return hash('sha256', (string)(microtime(true) * 1000000) . bin2hex(random_bytes(16)), true);
  }
}

function sfs_webauthn_base64url_encode(string $data): string {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function sfs_webauthn_base64url_decode(string $data): string {
  $remainder = strlen($data) % 4;
  if ($remainder) {
    $data .= str_repeat('=', 4 - $remainder);
  }
  return base64_decode(strtr($data, '-_', '+/'));
}

function sfs_webauthn_json_response(array $payload, int $status = 200): void {
  // Clear any previous output
  if (ob_get_level()) ob_clean();
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}

function sfs_webauthn_fail(string $message, int $status = 400): void {
  sfs_webauthn_json_response(['ok' => false, 'error' => $message], $status);
}

function sfs_webauthn_cors_headers(): void {
  header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
  header('Access-Control-Allow-Methods: POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type');
  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}

