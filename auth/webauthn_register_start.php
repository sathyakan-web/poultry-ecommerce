<?php
// ============================================================
// WebAuthn Registration Start
// POST { phone, role }
// Returns PublicKeyCredentialCreationOptions for navigator.credentials.create()
// ============================================================

require_once __DIR__ . '/rbac.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../api/db.php';
require_once __DIR__ . '/webauthn_helpers.php';

sfs_webauthn_cors_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  sfs_webauthn_fail('Method not allowed', 405);
}

sfs_csrf_check();

$phone = isset($_POST['phone']) ? preg_replace('/\D/', '', (string)$_POST['phone']) : '';

// If not logged in via session, phone is required
if (empty($_SESSION['user_id']) && !preg_match('/^\d{10}$/', $phone)) {
  sfs_webauthn_fail('Phone/ID must be exactly 10 digits.');
}

try {
  $pdo = db();

  // If phone provided, look up user; otherwise use session
  if ($phone !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();
    if (!$user) {
      sfs_webauthn_fail('No account found for this phone. Register or login first.', 404);
    }
    $userId = (int)$user['id'];
  } else {
    $userId = (int)$_SESSION['user_id'];
  }

  // Generate challenge
  $challenge = sfs_webauthn_random(32);
  $challengeB64 = sfs_webauthn_base64url_encode($challenge);

  // Store challenge in session temporarily
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['webauthn_register_challenge'] = $challenge;
  $_SESSION['webauthn_register_user_id'] = $userId;

  // Build user ID for WebAuthn
  $userIdBin = pack('N', $userId);

  $creationOptions = [
    'challenge' => $challengeB64,
    'rp' => [
      'name' => 'SafeFlock Solution',
      'id' => parse_url('https://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST) ?: $_SERVER['SERVER_NAME'] ?? 'localhost',
    ],
    'user' => [
      'id' => sfs_webauthn_base64url_encode($userIdBin),
      'name' => $phone ?: 'user',
      'displayName' => $phone ?: 'User',
    ],
    'pubKeyCredParams' => [
      ['type' => 'public-key', 'alg' => -7],
      ['type' => 'public-key', 'alg' => -257],
    ],
    'authenticatorSelection' => [
      'authenticatorAttachment' => 'platform',
      'userVerification' => 'required',
    ],
    'timeout' => 120000,
  ];

  sfs_webauthn_json_response([
    'ok' => true,
    'options' => $creationOptions,
  ]);
} catch (Throwable $e) {
  sfs_webauthn_fail('Registration start failed: ' . $e->getMessage(), 500);
}

