<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
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

$jsonMode = isset($_GET['json']) && (string)$_GET['json'] === '1';

$_SESSION = [];
if (ini_get('session.use_cookies')) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $params['path'],
    'domain' => $params['domain'],
    'secure' => $params['secure'],
    'httponly' => $params['httponly'],
    'samesite' => $params['samesite'] ?? 'Lax',
  ]);
}

session_destroy();

if ($jsonMode) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['ok' => true, 'message' => 'Logged out']);
  exit;
}

header('Location: ../login.php');
exit;

