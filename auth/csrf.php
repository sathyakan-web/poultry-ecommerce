<?php
// CSRF token helper for SafeFlock Solution.
// Usage:
//   require_once __DIR__ . '/csrf.php';
//   sfs_csrf_check();           // validate on POST endpoints
//   $token = sfs_csrf_token();  // get token for forms/headers

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function sfs_csrf_token(): string {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf_token'];
}

function sfs_csrf_check(): void {
  $token = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if (!$token || !hash_equals(sfs_csrf_token(), (string)$token)) {
    http_response_code(419);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Invalid or missing CSRF token']);
    exit;
  }
}

/**
 * Debug logging helper. Writes to /tmp/sfs_debug.log so we can
 * trace why login fails on the live server without exposing
 * secrets to the client.
 */
function sfs_debug_log(string $event, array $context = []): void {
  static $enabled = null;
  if ($enabled === null) {
    $enabled = (bool)(getenv('SFS_DEBUG_LOG') ?: file_exists(__DIR__ . '/../debug_on'));
  }
  if (!$enabled) return;
  try {
    $line = date('c') . ' [' . $event . '] ' . json_encode($context, JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(sys_get_temp_dir() . '/sfs_debug.log', $line, FILE_APPEND);
  } catch (Throwable $e) {}
}

