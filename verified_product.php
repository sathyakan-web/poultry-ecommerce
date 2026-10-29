<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider', 'logistics']);
require_once __DIR__ . '/auth/csrf.php';

// Expected params:
// - sku (recommended)
// - token (optional)
// - batch_no (optional)
// - iot_status (optional)

$sku = isset($_GET['sku']) ? trim((string)$_GET['sku']) : '';
$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$batchNo = isset($_GET['batch_no']) ? trim((string)$_GET['batch_no']) : '';
$iotStatus = isset($_GET['iot_status']) ? trim((string)$_GET['iot_status']) : '';

// If user is not logged in, redirect to login.
if (empty($_SESSION['user_id'])) {
  header('Location: login.php');
  exit;
}




// Normalize whitespace similar to verify_qr.php
$skuNorm = $sku;
if ($skuNorm !== '') {
  $skuNorm = rawurldecode($skuNorm);
  $skuNorm = preg_replace('/\s+/', '', $skuNorm);
}

// If no sku, but token exists, verify again to extract sku/batch/iot.
$data = null;
$verifyOk = false;
if ($token !== '') {
  $tokenNorm = rawurldecode($token);
  $tokenNorm = preg_replace('/\s+/', '', $tokenNorm);

  $_GET['token'] = $tokenNorm;
  ob_start();
  require __DIR__ . '/api/verify_qr.php';
  $json = ob_get_clean();
  $data = json_decode($json, true);

  $verifyOk = is_array($data) && !empty($data['ok']);
  if ($verifyOk) {
    $verification = $data['verification'] ?? [];
    // trust verified values for display
    $skuNorm = isset($verification['sku']) ? (string)$verification['sku'] : $skuNorm;
    $skuNorm = $skuNorm !== '' ? preg_replace('/\s+/', '', $skuNorm) : '';
    $batchNo = isset($verification['batch_no']) ? (string)$verification['batch_no'] : $batchNo;
    $iotStatus = isset($verification['iot_status']) ? (string)$verification['iot_status'] : $iotStatus;
  }
}


function h($s){

  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Load product info (name/category/logo) by SKU
$productName = '';
$productCategory = '';
$productImageUrl = '';
$productSku = $skuNorm;

if ($productSku !== '') {
  require_once __DIR__ . '/api/db.php';
  $pdo = db();

  $stmt = $pdo->prepare('SELECT sku, name, category, logo_path FROM products WHERE sku = :sku LIMIT 1');
  $stmt->execute([':sku' => $productSku]);
  $row = $stmt->fetch();
  if ($row) {
    $productSku = $row['sku'] ?? $productSku;
    $productName = $row['name'] ?? '';
    $productCategory = $row['category'] ?? '';
    $productImageUrl = $row['logo_path'] ?? '';
  }
}

if ($productImageUrl) {
  $productImageUrl = str_replace('\\', '/', $productImageUrl);
}

$badgeLabel = $verifyOk ? '✅ VERIFIED • ORIGINAL PRODUCT' : '🚨 FAKE / UNVERIFIED';
$badgeColor = $verifyOk ? '#22c55e' : '#ef4444';

// If we are showing verified content but iot/batch are missing, set placeholders.
$batchNo = $batchNo ?: '—';
$iotStatus = $iotStatus ?: '—';

// Inventory qty
$invQty = '';
$invUpdated = '';
if ($productSku !== '') {
  require_once __DIR__ . '/api/db.php';
  $pdo = db();

  $prodStmt = $pdo->prepare('SELECT id FROM products WHERE sku = :sku LIMIT 1');
  $prodStmt->execute([':sku' => $productSku]);
  $prodId = (int)$prodStmt->fetchColumn();

  $invStmt = $pdo->prepare('SELECT qty, updated_at FROM inventory WHERE product_id = :pid LIMIT 1');
  $invStmt->execute([':pid' => $prodId]);
  $inv = $invStmt->fetch();

  if ($inv) {
    $invQty = $inv['qty'] ?? '';
    $invUpdated = $inv['updated_at'] ?? '';
  }
}

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>SAFEFLOCK SOLUTION - Verified Product</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/products-modal.css" />
  <script src="js/route-guard.js"></script>
  <style>
    .verified-page-wrap{ padding: 20px 0; }
    .verified-card{ max-width: 820px; margin: 0 auto; }
    .badge-pill{
      display:inline-flex; align-items:center; gap:8px;
      padding:10px 14px; border-radius:999px;
      border:1px solid rgba(255,255,255,0.18);
      background: rgba(0,0,0,0.15);
      font-weight:1000; letter-spacing:0.2px;
      color: <?php echo $badgeColor; ?>;
    }
    .product-card{
      margin-top:16px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(0,0,0,0.12);
      border-radius: 16px;
      padding: 16px;
    }
    .product-card-top{ display:flex; gap:16px; align-items:flex-start; flex-wrap:wrap; }
    .product-image{
      width: 132px; height: 132px;
      border-radius: 16px;
      object-fit: cover;
      background: rgba(0,0,0,0.18);
      border: 1px solid rgba(255,255,255,0.12);
    }
    .product-meta{ flex:1; min-width: 220px; }
    .mono{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .meta-row{ margin-top:8px; color: rgba(226,232,240,0.92); }
    .cta-row{ margin-top:14px; display:flex; gap:10px; flex-wrap:wrap; }
    @media (min-width: 768px) {
      .web-dock-container { position: fixed !important; }
      .flock-dock { width: 100% !important; max-width: 1000px !important; }
      .nav-item { flex: 1 !important; min-width: 0 !important; }
      .active-indicator { display: block !important; }
    }
  </style>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
</head>
<body class="theme-bng" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">

<header class="site-header">
  <div class="container header-inner">
    <div class="brand">
      <span class="brand-name" aria-label="SAFEFLOCK SOLUTION">SAFEFLOCK SOLUTION</span>
    </div>
    <div class="header-actions">
      <a class="btn" href="index.php">🏠 Home</a>
      <a class="btn btn-primary" href="qr.php">📶 QR</a>
      <a class="btn" href="auth/logout.php">🚪 Logout</a>
    </div>
  </div>
</header>

<main class="page">
  <section class="section verified-page-wrap">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <h2 style="margin:0;">Product Verification</h2>
        <span class="badge-pill"><?php echo h($badgeLabel); ?></span>
      </div>

      <div class="product-card verified-card">
        <div class="product-card-top">
          <?php if (!empty($productImageUrl)) { ?>
            <img class="product-image" src="<?php echo h($productImageUrl); ?>" alt="Product image" onerror="this.style.display='none'" />
          <?php } else { ?>
            <div class="product-image" style="display:flex; align-items:center; justify-content:center; color:rgba(226,232,240,0.65); font-weight:1000;">No Image</div>
          <?php } ?>

          <div class="product-meta">
            <div style="font-weight:1000; font-size:22px; line-height:1.15;">
              <?php echo h($productName ?: 'Unknown product'); ?>
            </div>
            <div class="muted" style="margin-top:6px; font-weight:900;">
              <?php echo h($productCategory ?: '—'); ?><?php echo $productSku ? ' • ' . h($productSku) : ''; ?>
            </div>

            <div class="meta-row muted">
              <div><span style="font-weight:900;">Batch:</span> <span class="mono"><?php echo h($batchNo); ?></span></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">IoT Status:</span> <?php echo h($iotStatus); ?></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">Inventory Qty:</span> <span class="mono"><?php echo h($invQty ?: '—'); ?></span></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">Updated:</span> <?php echo h($invUpdated ?: '—'); ?></div>
            </div>

            <div class="cta-row">
              <a class="btn btn-primary" href="products.php?category=<?php echo h($productCategory ?: 'all'); ?>&search=<?php echo h($productName ?: ''); ?>">View in Products Catalog</a>
              <a class="btn" href="qr.php">Verify another QR</a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="container footer-inner">
    <div class="footer-brand">SAFEFLOCK SOLUTION</div>
    <div class="footer-copy muted">© <span id="year"></span></div>
  </div>
</footer>

<script>
  document.getElementById('year').textContent = new Date().getFullYear();
</script>

  </div>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'customer'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item active" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item active" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item active" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
  <script src="js/desktop-dock.js"></script>
</body>
</html>





