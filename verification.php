<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
// DEPRECATED: verification.php was removed from the user flow.
// Keeping this endpoint for backward compatibility, but it will simply redirect
// to the verified product page using `token`.
//
// If you need to rebuild the flow from ground, delete this file.

require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider', 'logistics']);
require_once __DIR__ . '/auth/csrf.php';

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
if ($token !== '') {
  $token = rawurldecode($token);
  $token = preg_replace('/\s+/', '', $token);
}




$data = null;
$err = '';

if ($token === '') {
  $err = 'Missing QR token.';
} else {
  // Invoke verification logic directly (no HTTP/relative URL dependency).
  $_GET['token'] = $token;
  ob_start();
  require __DIR__ . '/api/verify_qr.php';
  $json = ob_get_clean();

  $data = json_decode($json, true);
  if (!is_array($data) || !isset($data['ok'])) {
    $err = 'Invalid response from verification API.';
  }
}

function h($s){
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

$ok = is_array($data) && !empty($data['ok']);
$verification = $ok && isset($data['verification']) && is_array($data['verification']) ? $data['verification'] : null;
$inventory = $ok && isset($data['inventory']) && is_array($data['inventory']) ? $data['inventory'] : null;
$message = $ok ? ($data['message'] ?? '') : ($data['error'] ?? $err);

// So badge is:
// - VERIFIED / ORIGINAL if ok === true
// - FAKE if ok === false
$badgeLabel = $ok ? 'VERIFIED • ORIGINAL PRODUCT' : 'FAKE / UNVERIFIED';
$badgeColor = $ok ? '#22c55e' : '#ef4444';
$badgeIcon = $ok ? '✅' : '🚨';

// The verify endpoint returns token+batch_no+iot_status+product name/category/sku, but not image.
// Best-effort: fetch product image from products table via SKU if possible.
$productImageUrl = '';
$productCategory = '';
$productName = '';
$batchNo = '';
$sku = '';
$iotStatus = '';

if ($verification) {
  $productName = $verification['product'] ?? ($verification['name'] ?? '');
  $productCategory = $verification['category'] ?? '';
  $sku = $verification['sku'] ?? '';
  $batchNo = $verification['batch_no'] ?? '';
  $iotStatus = $verification['iot_status'] ?? '';
}

if ($sku) {
  require_once __DIR__ . '/api/db.php';
  $pdo = db();
  // Schema in db/schema.sql: products table has logo_path (no image_path).
  // Keep compatible lookup and avoid referencing missing columns.
  $stmt = $pdo->prepare('SELECT logo_path FROM products WHERE sku = :sku LIMIT 1');
  $stmt->execute([':sku' => $sku]);
  $row = $stmt->fetch();
  if ($row && isset($row['logo_path'])) {
    $productImageUrl = $row['logo_path'] ?: '';
  }
}

// Normalize path separators for Windows so HTML src works reliably.
if ($productImageUrl) {
  $productImageUrl = str_replace('\\\\', '/', $productImageUrl);
}


// inventory qty/display
$invQty = '';
$invUpdated = '';
if (is_array($inventory)) {
  $invQty = $inventory['qty'] ?? '';
  $invUpdated = $inventory['updated_at'] ?? '';
}

if ($ok && $sku) {
  // Open verified product page (not the products grid/modal page).
  // IMPORTANT: pass the EXACT verified token from verify_qr.php (DB value),
  // because verification badge must be based on the token that exists in qr_tokens.
  // Use the token exactly as stored in qr_tokens (DB token) for consistent verification.
  // This prevents any accidental use of a "whole code" payload coming from client query params.
  $verifiedToken = '';
  if (is_array($data) && isset($data['verification']) && is_array($data['verification'])) {
    $verifiedToken = (string)($data['verification']['token'] ?? $token);
  } else {
    $verifiedToken = (string)$token;
  }

  $verifiedToken = rawurldecode($verifiedToken);
  $verifiedToken = preg_replace('/\s+/', '', $verifiedToken);

  header('Location: verified_product.php?sku=' . rawurlencode((string)$sku) . '&token=' . rawurlencode($verifiedToken));
  exit;
}





?><!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <title>SAFEFLOCK SOLUTION - Verification</title>
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
  </style>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
</head>
<body class="theme-bng" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">

<a class="skip-link" href="#main">Skip</a>

<header class="site-header">
  <div class="container header-inner">
    <div class="brand">
      <span class="brand-name" aria-label="SAFEFLOCK SOLUTION">SAFEFLOCK SOLUTION</span>
    </div>
    <div class="header-actions">
      <a class="btn" href="index.php">🏠 Home</a>
      <a class="btn btn-primary" href="qr.php">📶 QR</a>
    </div>
  </div>
</header>

<main id="main" class="page">
  <section class="section verified-page-wrap">
    <div class="container">
      <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <h2 style="margin:0;">Product Verification</h2>
        <span class="badge-pill"><?php echo h($badgeIcon); ?> <?php echo h($badgeLabel); ?></span>
      </div>

      <!-- Verification page shows the exact same product section layout (image + fields) -->
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
              <?php echo h($productCategory ?: '—'); ?><?php echo $sku ? ' • ' . h($sku) : ''; ?>
            </div>

            <div class="meta-row muted">
              <div><span style="font-weight:900;">Batch:</span> <span class="mono"><?php echo h($batchNo ?: '—'); ?></span></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">IoT Status:</span> <?php echo h($iotStatus ?: '—'); ?></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">Inventory Qty:</span> <span class="mono"><?php echo h($invQty ?: '—'); ?></span></div>
              <div style="margin-top:6px;"><span style="font-weight:900;">Updated:</span> <?php echo h($invUpdated ?: '—'); ?></div>
            </div>

            <?php if ($message) { ?>
              <div style="margin-top:10px; color: #cbd5e1; font-weight:800;">
                <?php echo h($message); ?>
              </div>
            <?php } ?>

            <div class="cta-row">
              <a class="btn btn-primary" href="products.php?category=<?php echo h($productCategory ?: 'all'); ?>&search=<?php echo h($productName ?: ''); ?>">View in Products Catalog</a>
              <?php if ($sku) { ?>
                <a class="btn" href="inventory.php?product_id=<?php echo h($sku); ?>" style="display:none;">View inventory</a>
              <?php } ?>
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

</body>
</html>



