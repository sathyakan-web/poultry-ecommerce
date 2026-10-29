<?php
// Returns the current authenticated session so the frontend can
// mirror it into localStorage under `safeFlock_session`.
//
//   GET  api/auth_session.php
//   200  { "ok": true, "session": { userId, phone, fullName, role, token } }
//   401  { "ok": false, "error": "Not logged in" }

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/../auth/check_login.php';
require_login(null);

$user = sfs_current_user();

echo json_encode([
  'ok'      => true,
  'session' => [
    'userId'   => (int)$user['user_id'],
    'phone'    => (string)$user['phone'],
    'fullName' => (string)($user['full_name'] ?: ''),
    'role'     => (string)$user['role'],
    'token'    => (string)session_id(),
  ],
]);

