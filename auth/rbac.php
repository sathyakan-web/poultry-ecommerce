<?php

require_once __DIR__ . '/../db/config.php';

// ============================================================
// RBAC core: role helpers shared by pages and API endpoints.
//
// Roles: customer | provider | logistics   ('admin' = legacy
// super-user that passes every guard for backwards compatibility)
// ============================================================

if (session_status() !== PHP_SESSION_ACTIVE) {
  // Ensure the session cookie is usable across the whole site
  // (including /api/*) so user_id/role never disappear.
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

/** Normalize a stored role to one of the three platform roles. */
function sfs_normalize_role(string $role): string {
  $role = strtolower(trim($role));
  if (in_array($role, ['customer', 'provider', 'logistics'], true)) {
    return $role;
  }
  // Legacy admin is treated as a provider (inventory management).
  if ($role === 'admin') {
    return 'provider';
  }
  return 'customer';
}

/** Current authenticated role (empty string when logged out). */
function sfs_current_role(): string {
  if (empty($_SESSION['user_id'])) return '';
  $raw = (string)($_SESSION['role'] ?? '');
  if ($raw === '') {
    $raw = 'customer';
  }
  return sfs_normalize_role($raw);
}

/** Is the given role allowed by an allow-list? (admin bypasses). */
function sfs_role_allowed(string $role, array $allowed): bool {
  if ($role === 'admin') return true;
  return in_array($role, $allowed, true);
}

/**
 * Route guard. Must be called AFTER require_login() (or it auto-checks).
 *
 * @param array      $roles      allowed roles, e.g. ['provider']
 * @param bool       $isApi      true  -> JSON 401/403 response
 * @param string|null $loginTarget page to redirect anonymous users to
 */
function sfs_require_role(array $roles, bool $isApi = false, ?string $loginTarget = null): void {
  $loggedIn = !empty($_SESSION['user_id']);

  if (!$loggedIn) {
    if ($isApi) {
      http_response_code(401);
      header('Content-Type: application/json; charset=utf-8');
      echo json_encode(['ok' => false, 'error' => 'Not logged in']);
      exit;
    }
    $target = $loginTarget ?: SFS_BASE_URL . '/login.php';
    header('Location: ' . $target);
    exit;
  }

  $role = sfs_current_role();
  if (sfs_role_allowed($role, $roles)) return;

  if ($isApi) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'Forbidden: role not permitted']);
    exit;
  }

  header('Location: ' . SFS_BASE_URL . '/403.php');
  exit;
}

/** Current user summary for JSON responses / client session. */
function sfs_current_user(): array {
  $role = sfs_current_role();
  $userId = (int)($_SESSION['user_id'] ?? 0);
  $phone = (string)($_SESSION['phone'] ?? '');
  $name = (string)($_SESSION['full_name'] ?? '');
  if ($name === '') $name = $phone;
  return [
    'user_id'   => $userId,
    'phone'     => $phone,
    'full_name' => $name,
    'role'      => $role,
  ];
}

/** Persist the current user summary into the session (used after login). */
function sfs_store_user_session(PDO $pdo, int $userId): array {
  $stmt = $pdo->prepare('SELECT id, phone, full_name, role, biometric_enabled FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $u = $stmt->fetch();
  if (!$u) return [];

  $_SESSION['user_id']            = (int)$u['id'];
  $_SESSION['phone']              = (string)$u['phone'];
  $_SESSION['full_name']          = (string)($u['full_name'] ?? '');
  $_SESSION['role']               = sfs_normalize_role((string)$u['role']);
  $_SESSION['biometric_enabled']  = (int)($u['biometric_enabled'] ?? 0);

  return sfs_current_user();
}

