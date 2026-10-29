<?php
// ============================================================
// WebAuthn Registration Finish
// POST { phone, credential_id, public_key, attestation }
// Verifies attestation and stores credential
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
$credentialId = isset($_POST['credential_id']) ? (string)$_POST['credential_id'] : '';
$publicKeyB64 = isset($_POST['public_key']) ? (string)$_POST['public_key'] : '';
$attestationB64 = isset($_POST['attestation']) ? (string)$_POST['attestation'] : '';
$clientDataB64 = isset($_POST['client_data_json']) ? (string)$_POST['client_data_json'] : '';

if ($credentialId === '' || $attestationB64 === '') {
  sfs_webauthn_fail('Missing credential data.');
}



try {
  $pdo = db();

  // Verify challenge from session
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  $expectedChallenge = $_SESSION['webauthn_register_challenge'] ?? '';
  $expectedUserId = $_SESSION['webauthn_register_user_id'] ?? 0;

  if ($expectedChallenge === '' || $expectedUserId === 0) {
    sfs_webauthn_fail('No registration challenge found. Please start registration again.', 400);
  }

  // If phone was provided, verify it matches the session user
  if ($phone !== '') {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
    $stmt->execute([':phone' => $phone]);
    $user = $stmt->fetch();
    if (!$user || (int)$user['id'] !== $expectedUserId) {
      sfs_webauthn_fail('Phone does not match registration session.', 400);
    }
  }

  // Verify challenge from clientDataJSON if provided
  if ($clientDataB64 !== '') {
    $clientDataJson = sfs_webauthn_base64url_decode($clientDataB64);
    $clientData = json_decode($clientDataJson, true);
    if (is_array($clientData) && isset($clientData['challenge'])) {
      $clientChallenge = sfs_webauthn_base64url_decode((string)$clientData['challenge']);
      if (!hash_equals($expectedChallenge, $clientChallenge)) {
        sfs_webauthn_fail('Invalid attestation challenge.', 400);
      }
    }
  }


  // Store credential (replace existing credentials for this user)
  $credentialIdBin = sfs_webauthn_base64url_decode($credentialId);
  $publicKeyBin = sfs_webauthn_base64url_decode($publicKeyB64);

  $pdo->prepare('DELETE FROM webauthn_credentials WHERE user_id = :user_id')->execute([':user_id' => $expectedUserId]);

  $stmt = $pdo->prepare('INSERT INTO webauthn_credentials (user_id, credential_id, public_key, counter) VALUES (:user_id, :credential_id, :public_key, 0)');
  $stmt->execute([
    ':user_id' => $expectedUserId,
    ':credential_id' => $credentialIdBin,
    ':public_key' => $publicKeyBin,
  ]);


  // Enable biometric for user
  $stmt = $pdo->prepare('UPDATE users SET biometric_enabled = 1 WHERE id = :id');
  $stmt->execute([':id' => $expectedUserId]);

  // Clear session challenge
  unset($_SESSION['webauthn_register_challenge']);
  unset($_SESSION['webauthn_register_user_id']);

  sfs_webauthn_json_response([
    'ok' => true,
    'message' => 'Fingerprint registered successfully. You can now use it to login.',
  ]);
} catch (Throwable $e) {
  sfs_webauthn_fail('Registration failed: ' . $e->getMessage(), 500);
}

