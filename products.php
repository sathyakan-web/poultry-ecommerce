<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider']);

require_once __DIR__ . '/auth/csrf.php';

$root = __DIR__;
$static = $root . '/products.html';
if (file_exists($static)) {
  header('Content-Type: text/html; charset=utf-8');
  ob_start();
  readfile($static);
  $content = ob_get_clean();
  $csrfMeta = '<meta name="csrf-token" content="' . htmlspecialchars(sfs_csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
  $roleAttr = ' data-role="' . htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8') . '"';
  $roleScript = '<script>window.__USER_ROLE__=' . json_encode(sfs_current_role()) . ';</script>';
  $themeScript = '<script src="js/theme.js"></script>';
  $content = preg_replace('/<head>/i', '<head>' . $csrfMeta . $roleScript . $themeScript, $content, 1);
  $content = preg_replace('/<body([^>]*)>/i', '<body$1' . $roleAttr . '>', $content, 1);
  echo $content;
  exit;
}

// Fallback: some deployments may have products.html under a different location.
// If that happens, show a clear error.
http_response_code(500);
?>
<!doctype html><html><body><h3>Missing products.html. Looked for: <?php echo htmlspecialchars($static, ENT_QUOTES, 'UTF-8'); ?></h3></body></html>




