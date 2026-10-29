<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_once __DIR__ . '/auth/csrf.php';

header('Content-Type: text/html; charset=utf-8');

$user = sfs_current_user();
$role = (string)($user['role'] ?? '');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <title>403 — Access Denied | SafeFlock Solution</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/rbac.css" />
  <script src="js/route-guard.js"></script>
</head>
<body class="theme-bng" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <main class="sfs-403-wrap">
    <div class="sfs-403-card">
      <div class="code">403</div>
      <div class="msg">Access Denied — Unauthorized Role</div>
      <div class="sub">
        Your account is signed in as <b style="color:#f5d76c;"><?php echo htmlspecialchars($role ?: 'unknown', ENT_QUOTES, 'UTF-8'); ?></b>,
        which does not have permission to view this page.
      </div>
      <div class="sfs-403-actions">
        <a class="btn btn-primary" href="index.php">🏠 Go to Home</a>
        <a class="btn" href="auth/logout.php" onclick="localStorage.removeItem('safeFlock_session');">Logout</a>
      </div>
    </div>
  </main>
  <script src="js/rbac-nav.js"></script>

  <!-- Mobile Bottom Dock -->
      <nav class="mobile-dock" aria-label="Mobile navigation">
    <div class="dock-inner">
      <div id="mobile-dock-pill"></div>
      <a href="index.php" class="dock-btn" data-page="home"><i class="fa-solid fa-house"></i><span>Home</span></a>
      <a href="products.php" class="dock-btn" data-page="shop"><i class="fa-solid fa-pills"></i><span>Shop</span></a>
      <a href="orders.php" class="dock-btn" data-page="orders"><i class="fa-solid fa-box-open"></i><span>Orders</span></a>
      <a href="qr.php" class="dock-btn" data-page="verify"><i class="fa-solid fa-qrcode"></i><span>Verify QR</span></a>
      <a href="settings.php" class="dock-btn active" data-page="settings"><i class="fa-solid fa-gear"></i><span>Settings</span></a>
      <a href="auth/logout.php" class="dock-btn dock-btn-logout" data-page="logout"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span></a>
    </div>
  </nav>
  <script src="js/mobile-dock.js"></script>
</body>
</html>




