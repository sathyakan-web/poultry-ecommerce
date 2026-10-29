<?php
// ============================================================
// WebAuthn Login Start
// POST { phone }
// Returns PublicKeyCredentialRequestOptions for navigator.credentials.get()
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
if (!preg_match('/^\d{10}$/', $phone)) {
  sfs_webauthn_fail('Phone/ID must be exactly 10 digits.');
}

try {
  $pdo = db();

  $stmt = $pdo->prepare('SELECT id, full_name, role, biometric_enabled FROM users WHERE phone = :phone LIMIT 1');
  $stmt->execute([':phone' => $phone]);
  $user = $stmt->fetch();

  if (!$user) {
    sfs_webauthn_fail('No account found for this phone. Register first.', 404);
  }

  if (!(int)($user['biometric_enabled'] ?? 0)) {
    sfs_webauthn_fail('Passkey not enrolled for this account. Use SMS OTP or Passcode.', 403);
  }

  $userId = (int)$user['id'];

  // Get all credentials for this user
  $stmt = $pdo->prepare('SELECT credential_id FROM webauthn_credentials WHERE user_id = :user_id');
  $stmt->execute([':user_id' => $userId]);
  $credentials = $stmt->fetchAll(PDO::FETCH_COLUMN);

  if (empty($credentials)) {
    sfs_webauthn_fail('No passkey registered for this account.', 403);
  }

  // Generate challenge
  $challenge = sfs_webauthn_random(32);
  $challengeB64 = sfs_webauthn_base64url_encode($challenge);

  // Store challenge in session
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $_SESSION['webauthn_login_challenge'] = $challenge;
  $_SESSION['webauthn_login_user_id'] = $userId;

  $allowCredentials = [];
  foreach ($credentials as $credIdBin) {
    $allowCredentials[] = [
      'type' => 'public-key',
      'id' => sfs_webauthn_base64url_encode($credIdBin),
    ];
  }

  $requestOptions = [
    'challenge' => $challengeB64,
    'allowCredentials' => $allowCredentials,
    'userVerification' => 'required',
    'timeout' => 120000,
  ];

  sfs_webauthn_json_response([
    'ok' => true,
    'options' => $requestOptions,
  ]);
} catch (Throwable $e) {
  sfs_webauthn_fail('Login start failed: ' . $e->getMessage(), 500);
}

