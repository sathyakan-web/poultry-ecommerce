<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider', 'logistics']);

require_once __DIR__ . '/auth/csrf.php';

$root = __DIR__;
$static = $root . '/iot.html';
if (file_exists($static)) {
  header('Content-Type: text/html; charset=utf-8');
  
  $token = sfs_csrf_token();
  ob_start();
  readfile($static);
  $content = ob_get_clean();
  $csrfMeta = '<meta name="csrf-token" content="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
  $pwaMeta = '<link rel="icon" type="image/x-icon" href="images/favicon.ico"><link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png"><link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png"><link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png"><link rel="manifest" href="manifest.json"><meta name="theme-color" content="#d4af37"><meta name="apple-mobile-web-app-capable" content="yes"><meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-status-bar-style" content="default">';
  $csrfScript = '<script>(function(){var t=document.querySelector("meta[name=csrf-token]")?.content;if(!t)return;var o=window.fetch;window.fetch=function(e,n){n=n||{};var r=(n.method||"GET").toUpperCase();if(r==="POST"){n.headers=n.headers||{};if(!("X-CSRF-Token"in n.headers))n.headers["X-CSRF-Token"]=t}return o(e,n)}})();</script>';
  $routeGuardScript = '<script src="js/route-guard.js"></script>';
  $roleAttr = ' data-role="' . htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8') . '"';
  $roleScript = '<script>window.__USER_ROLE__=' . json_encode(sfs_current_role()) . ';</script>';
  $content = preg_replace('/<head>/i', '<head>' . $csrfMeta . $pwaMeta . $csrfScript . $routeGuardScript . $roleScript . '<script src="js/theme.js"></script>', $content, 1);
  $content = preg_replace('/<body([^>]*)>/i', '<body$1' . $roleAttr . '>' . "\n" . '  <header class="doodle-header">' . "\n" . '    <h1 class="brand-title">Poultry</h1>' . "\n" . '  </header>' . "\n" . '  <div class="doodle-container">', $content, 1);
  $content = preg_replace('#  <nav class="mobile-dock" aria-label="Mobile navigation">
    <div class="dock-inner">
      <div id="mobile-dock-pill"></div>
    </div>
  </nav>
</body>#i', '  </div>' . "\n" . '  <nav class="mobile-dock" aria-label="Mobile navigation">
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
</body>', $content, 1);
  echo $content;
  exit;
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Poultry - IoT</title>
  <link rel="stylesheet" href="css/doodle.css" />
</head>
<body>
  <header class="doodle-header">
    <h1 class="brand-title">Poultry</h1>
  </header>
  <div class="doodle-container">

    <h3>Missing iot.html</h3>
    <p>Please create <code>iot.html</code> to use the IoT dashboard.</p>

    <script src="js/theme.js"></script>
    <script src="js/loading.js"></script>
    <script src="js/doodle.js"></script>
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
















