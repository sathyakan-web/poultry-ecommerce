<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/auth/check_login.php';
require_once __DIR__ . '/auth/csrf.php';

// Reuse existing admin auth (same pattern used by other backend pages)
require_login(null);
require_role(['provider']);

header('Content-Type: text/html; charset=utf-8');

$pdo = db();

// Fetch products with one token per product (prefer earliest token)
$sql = "SELECT p.id, p.sku, p.name, p.category,
               p.logo_path,
               t.id AS token_row_id,
               t.token AS qr_token,
               t.batch_no AS batch_no,
               t.iot_status AS iot_status
        FROM products p
        LEFT JOIN qr_tokens t ON t.product_id = p.id
        WHERE t.id IS NULL OR t.id = (
          SELECT t2.id FROM qr_tokens t2
          WHERE t2.product_id = p.id
          ORDER BY t2.created_at ASC, t2.id ASC
          LIMIT 1
        )
        ORDER BY p.name ASC";

// The above query is a bit strict for MySQL modes; fallback to simpler query.
try {
  $stmt = $pdo->query($sql);
  $rows = $stmt->fetchAll();
} catch (Throwable $e) {
  $stmt = $pdo->query("SELECT p.id, p.sku, p.name, p.category, p.logo_path FROM products p ORDER BY p.name ASC");
  $rows = [];
  foreach ($stmt->fetchAll() as $p) {
    $tstmt = $pdo->prepare("SELECT token, batch_no, iot_status FROM qr_tokens WHERE product_id = :pid ORDER BY created_at ASC, id ASC LIMIT 1");
    $tstmt->execute([':pid' => (int)$p['id']]);
    $trow = $tstmt->fetch();
    $p['qr_token'] = $trow ? $trow['token'] : null;
    $p['batch_no'] = $trow ? $trow['batch_no'] : null;
    $p['iot_status'] = $trow ? $trow['iot_status'] : null;
    $rows[] = $p;
  }
}

$createdCount = 0;

// Ensure each product has at least one qr_tokens row
foreach ($rows as &$r) {
  $pid = (int)$r['id'];

  if (empty($r['qr_token'])) {
    $batchCode = 'BATCH-' . strtoupper(bin2hex(random_bytes(3))); // 6 chars
    $token = $r['sku'] . '|' . $batchCode;

    $ins = $pdo->prepare("INSERT INTO qr_tokens (token, product_id, batch_no, iot_status)
                          VALUES (:token, :pid, :batch_no, 'OK')");
    try {
      $ins->execute([
        ':token' => $token,
        ':pid' => $pid,
        ':batch_no' => $batchCode,
      ]);
      $createdCount++;

      $r['qr_token'] = $token;
      $r['batch_no'] = $batchCode;
      $r['iot_status'] = 'OK';
    } catch (Throwable $e) {
      // If token uniqueness fails, try again once with another batch.
      $batchCode = 'BATCH-' . strtoupper(bin2hex(random_bytes(3)));
      $token = $r['sku'] . '|' . $batchCode;
      $ins->execute([
        ':token' => $token,
        ':pid' => $pid,
        ':batch_no' => $batchCode,
      ]);

      $createdCount++;
      $r['qr_token'] = $token;
      $r['batch_no'] = $batchCode;
      $r['iot_status'] = 'OK';
    }
  }
}
unset($r);

function qr_img_url(string $data): string {
  $encoded = rawurlencode($data);
  return 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . $encoded;
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$products = $rows;
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
  <title>Safefolck Solutions - Product QR Labels</title>
  <script src="js/route-guard.js"></script>
  <style>
    body{font-family: Arial, Helvetica, sans-serif; margin:16px; color:#111;}
    .topbar{display:flex; gap:12px; align-items:center; margin-bottom:14px; flex-wrap:wrap;}
    .btn{border:1px solid #9ca3af; background:#fff; padding:8px 12px; border-radius:10px; cursor:pointer; font-weight:600;}
    .hint{color:#6b7280; font-size:13px;}
    .grid{display:grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap:14px;}
    @media print{ .noprint{display:none !important;} body{margin:0;} .grid{gap:10px;} }
    @media (max-width: 900px){ .grid{grid-template-columns: repeat(2, minmax(0, 1fr));} }
    .label{border:1px dashed #d1d5db; padding:10px; border-radius:12px; text-align:center;}
    .brand{font-weight:800; font-size:13px; margin-bottom:6px;}
    .qrbox{display:flex; flex-direction:column; align-items:center; gap:6px;}
    .serial{font-size:12px; margin-top:4px; font-weight:700;}
    .name{font-size:12px; margin-top:2px; font-weight:600;}
    .save{margin-top:8px;}
    .mono{font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; font-size:11px; color:#374151; word-break:break-word;}
  </style>
</head>
<body data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="topbar noprint">
    <button class="btn" type="button" onclick="window.print()">Print Labels</button>
    <div class="hint">{Safefolck Solutions} labels. Auto-created missing QR tokens: <b><?php echo (int)$createdCount; ?></b></div>
    <a class="btn" href="auth/logout.php" style="margin-left:auto;">🚪 Logout</a>
  </div>

  <div class="grid">
    <?php foreach ($products as $p):
      $serial = (string)$p['qr_token'];
      $img = qr_img_url($serial);
    ?>
      <div class="label">
        <div class="brand">Safefolck Solutions</div>
        <div class="qrbox">
          <img class="qrimg" alt="QR" src="<?php echo e($img); ?>" width="150" height="150" />
          <div class="name"><?php echo e((string)$p['name']); ?></div>
          <div class="serial">Serial: <span class="mono"><?php echo e($serial); ?></span></div>
        </div>
        <div class="save">
          <button class="btn" type="button" onclick="saveQrToPc(<?php echo json_encode($serial); ?>)">Save to PC</button>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <script>
    async function saveQrToPc(serialCode){
      const safeName = String(serialCode).replace(/[^a-zA-Z0-9._-]/g, '_');
      const filename = 'Safefolck_QR_' + safeName + '.png';

      // Direct download approach (avoids CORS/blob issues)
      // Works if the browser allows opening the image URL as a download.
      const url = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' + encodeURIComponent(serialCode);

      const a = document.createElement('a');
      a.href = url;
      a.download = filename;
      a.target = '_blank';
      document.body.appendChild(a);
      a.click();
      a.remove();
    }
  </script>

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




