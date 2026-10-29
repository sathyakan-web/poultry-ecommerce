<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider', 'logistics']);

require_once __DIR__ . '/auth/csrf.php';

if (isset($_GET['debug'])) {
  header('Content-Type: text/plain; charset=utf-8');
  echo "inventory.php reached\n";
  echo "user_id=" . ($_SESSION['user_id'] ?? 'none') . "\n";
  echo "role=" . ($_SESSION['role'] ?? 'none') . "\n";
  exit;
}

$root = __DIR__;
$isMobile = isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/Mobile|Android|Silk\/|Kindle|BlackBerry|Opera Mini|Opera Mobi/i', $_SERVER['HTTP_USER_AGENT']);
$static = $isMobile ? $root . '/inventory_mobile.html' : $root . '/inventory.html';
if (file_exists($static)) {
  header('Content-Type: text/html; charset=utf-8');
  ob_start();
  readfile($static);
  $content = ob_get_clean();
  $csrfMeta = '<meta name="csrf-token" content="' . htmlspecialchars(sfs_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
  $routeGuardScript = '<script src="js/route-guard.js?v=' . filemtime(__DIR__ . '/js/route-guard.js') . '"></script>';
  $roleAttr = ' data-role="' . htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8') . '"';
  $roleScript = '<script>window.__USER_ROLE__=' . json_encode(sfs_current_role()) . ';</script>';
  $content = preg_replace('/<head>/i', '<head>' . $csrfMeta . $routeGuardScript . $roleScript . '<script src="js/theme.js"></script>', $content, 1);
  $content = preg_replace('/<body([^>]*)>/i', '<body$1' . $roleAttr . '>', $content, 1);
  $content = str_replace('  <nav class="mobile-dock" aria-label="Mobile navigation">
    <div class="dock-inner">
      <div id="mobile-dock-pill"></div>
    </div>
  </nav>
</body>', '</body>', $content);
  if ($content === null || $content === false || $content === '') {
    $content = file_get_contents($static);
    if ($content === false) {
      $content = '<!doctype html><html><head><meta charset="utf-8" /><title>SafeFlock Solution - Inventory</title><link rel="stylesheet" href="css/doodle.css" /></head><body>';
      $content .= '<header class="doodle-header"><h1 class="brand-title">SafeFlock Solution</h1></header>';
      $content .= '<div class="doodle-container">';
      $content .= '<h3>Missing inventory.html</h3><p>Please create <code>inventory.html</code> to use inventory.</p>';
      $content .= '<script src="js/theme.js"></script><script src="js/loading.js"></script><script src="js/doodle.js"></script></body></html>';
    }
  }
  echo '<!-- inventory.php rendered len=' . strlen($content) . ' -->';
  echo $content;
  exit;
}













