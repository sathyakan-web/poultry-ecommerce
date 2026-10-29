<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider', 'logistics']);
require_once __DIR__ . '/auth/csrf.php';

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$orderId = isset($_GET['order_id']) ? trim((string)$_GET['order_id']) : '';
if ($orderId === '') {
  http_response_code(400);
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
  <title>SafeFlock Solution - Order details</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/orders.css" />
  <script src="js/route-guard.js"></script>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
  <style>
    body.theme-orders { background: #050505; color: rgba(255,255,255,0.92); }
    .details-card {
      max-width: 980px;
      margin: 28px auto;
      border-radius: 16px;
      border: 1px solid rgba(212,175,55,0.35);
      background: rgba(212,175,55,0.06);
      padding: 18px;
    }
    .gold { color: #f5d76c; font-weight: 1000; }
    .muted { color: rgba(255,255,255,0.7); font-weight: 900; }
    .order-grid { display:grid; gap:12px; grid-template-columns: 1fr; }
    .order-items-title { margin-top: 10px; }
    .btn-orders { border: 1px solid rgba(212,175,55,0.35); background: rgba(212,175,55,0.12); color: #f5d76c; border-radius: 14px; padding: 12px 14px; font-weight: 1000; cursor: pointer; }
    .btn-orders.primary { background: rgba(212,175,55,0.22); }
    .orders-item { border: 1px solid rgba(255,255,255,0.08); border-radius: 14px; padding: 12px; background: rgba(0,0,0,0.18); }
    .orders-item-logo { width: 54px; height: 54px; object-fit: cover; border-radius: 12px; background: rgba(255,255,255,0.05); }
    .orders-item-meta { margin-top: 10px; }

    /* Truck transition (Home -> Orders) */
    .truck-transition {
      display:flex;
      justify-content:center;
      align-items:center;
      margin: 6px auto 0;
      opacity: 0.95;
      pointer-events: none;
    }
    .truck-transition .truck-path {
      stroke-dasharray: 8, 4;
      animation: dash-move 1s linear infinite;
    }
    @keyframes dash-move {
      to { stroke-dashoffset: -12; }
    }
    .truck-transition .truck-anim {
      animation: truck-drive 3s ease-in-out infinite;
    }
    @keyframes truck-drive {
      0% { transform: translateX(-10px); }
      50% { transform: translateX(180px); }
      100% { transform: translateX(380px); }
    }

    .web-dock-container {
      position: fixed; bottom: 15px; left: 0; right: 0;
      display: flex; justify-content: center; pointer-events: none; z-index: 1000;
    }
    .flock-dock {
      pointer-events: auto;
      background-color: #ffffff;
      border: 2px solid #2b2b2b;
      border-radius: 32px 40px 36px 44px / 44px 36px 40px 32px;
      box-shadow: 0px 6px 0px #2b2b2b, 0px 12px 20px rgba(0,0,0,0.2);
      display: flex; align-items: center; padding: 8px 12px;
      height: 70px; width: 90%; max-width: 650px; position: relative;
    }
    .nav-item {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-decoration: none; color: #2b2b2b; flex: 1; height: 100%; position: relative;
      z-index: 2; cursor: pointer; transition: transform 0.2s;
    }
    .icon-container { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; }
    .nav-item svg {
      width: 22px; height: 22px; fill: none; stroke: #2b2b2b; stroke-width: 2.5;
      stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .nav-label { font-size: 10px; font-weight: 900; }
    .active-indicator {
      position: absolute; height: 55px; background-color: #ffe66d;
      border: 2px dashed #2b2b2b; border-radius: 24px 32px 24px 32px / 32px 24px 32px 24px;
      box-shadow: 2px 2px 0px #2b2b2b; top: 3px; z-index: 1;
      transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      animation: squish-dock 4s infinite alternate;
    }
    .nav-item.active { color: #2b2b2b; }
    .nav-item.active svg {
      transform: translateY(-5px) scale(1.15) rotate(-5deg);
      filter: drop-shadow(2px 2px 0px rgba(0,0,0,0.1));
    }
    .nav-item.logout svg { stroke: #ff6b6b; }
    .nav-item.logout .nav-label { color: #ff6b6b; }
    @keyframes squish-dock {
      0% { border-radius: 24px 32px 24px 32px / 32px 24px 32px 24px; }
      100% { border-radius: 32px 24px 32px 24px / 24px 32px 24px 32px; }
    }
    @media (max-width: 768px) {
      .flock-dock { width: 95%; height: 60px; padding: 5px; }
      .nav-item svg { width: 24px; height: 24px; }
      .nav-label { font-size: 10px; }
      .active-indicator { height: 45px; top: 3px; }
    }
  </style>

</head>
<body class="theme-orders" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <a class="skip-link" href="#main">Skip</a>

  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <span class="brand-name" aria-label="SAFEFLOCK SOLUTION">SAFEFLOCK SOLUTION</span>
      </div>
      <nav class="top-nav" aria-label="Primary">
        <a class="top-nav-link" href="index.php">🏠 Home</a>
        <a class="top-nav-link" href="orders.php">✅ Orders</a>
        <a class="top-nav-link" href="auth/logout.php">🚪 Logout</a>
      </nav>
    </div>
  </header>

  <main id="main" class="page">
    <div class="container">
      <div class="details-card">
        <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
          <div>
            <div class="gold" style="font-size:22px; margin-bottom:6px;">Order details</div>
            <div class="muted">Order ID: <span id="orderIdLabel"><?php echo e($orderId ?: 'local'); ?></span></div>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="past_orders.php" onclick="return sfsTransition.go(event, 'past_orders.php');">← Back to Past Orders</a>
          </div>

        </div>

        <div style="height:18px;"></div>

        <div class="order-items-title muted">Dispatched items</div>
        <div id="orderItemsContainer" class="order-grid" style="margin-top:10px;">
          <div class="muted">Loading order items…</div>
        </div>

        <div style="height:18px;"></div>
        <div class="muted">Tip: "View Past Orders" lists your order history; click "View details" to see items and the live delivery status here.</div>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>
        <div class="footer-brand">SafeFlock Solution</div>
        <div class="muted">IoT + QR + regional chain inventory for poultry layer industry.</div>
      </div>
      <div class="footer-copy muted">© <span id="year"></span></div>
    </div>
  </footer>

  <script src="js/page-transition.js"></script>
  <script>
    (function(){

      const orderId = <?php echo json_encode($orderId); ?>;
      const container = document.getElementById('orderItemsContainer');
      const yearEl = document.getElementById('year');
      yearEl.textContent = new Date().getFullYear();

      // price placeholder logic kept consistent with orders.php
      function priceForProduct(productId) {
        function mulberry32(a) {
          return function () {
            let t = (a += 0x6D2B79F5);
            t = Math.imul(t ^ (t >>> 15), t | 1);
            t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
          };
        }
        const seed = Number(productId) || 0;
        const rnd = mulberry32(seed)();
        const min = 300;
        const max = 10000;
        return Math.floor(min + rnd * (max - min + 1));
      }

      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
      }

      async function loadDetails(){
        try{
          if(!orderId){
            container.innerHTML = '<div class="muted">Missing order id.</div>';
            return;
          }
          container.innerHTML = '<div class="muted">Loading order items…</div>';

          const res = await fetch('api/orders.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body:new URLSearchParams({action:'get_order_details', order_id: orderId, csrf_token: (document.querySelector('meta[name="csrf-token"]')?.content || '')}).toString(),
          });
          const data = await res.json();
          if(!data.ok) throw new Error(data.error || 'Failed to load order details');

          const items = data.items || [];
          if(!items.length){
            container.innerHTML = '<div class="muted">No dispatched items found for this order.</div>';
            return;
          }

          container.innerHTML = items.map((it)=>{
            const pid = it.product_id ?? '';
            const name = escapeHtml(it.name || 'Product');
            const qty = Number(it.qty || 0);
            const logo = it.logo_path ? escapeHtml(it.logo_path) : '';
            const price = priceForProduct(pid);
            const lineTotal = price * qty;

            const rawStatus = String(it.status || 'DISPATCHED').toUpperCase();
            const statusLabel = ({ 'DISPATCHED':'Pending', 'ASSIGNED':'Assigned', 'IN_TRANSIT':'In-Transit', 'DELIVERED':'Delivered', 'RETURN_REQUESTED':'Return Requested', 'RETURNED':'Returned' })[rawStatus] || rawStatus;
            const statusColor = rawStatus === 'DELIVERED' ? '#34d399'
                              : rawStatus === 'IN_TRANSIT' ? '#fbbf24'
                              : rawStatus === 'ASSIGNED' ? '#fbbf24'
                              : rawStatus === 'RETURN_REQUESTED' ? '#fb923c'
                              : rawStatus === 'RETURNED' ? '#a5b4fc'
                              : '#f5d76c';

            return `
              <div class="orders-item">
                <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                  <div style="display:flex; gap:12px; align-items:flex-start;">
                    <img class="orders-item-logo" src="${logo}" alt="" onerror="this.style.display='none'" />
                    <div>
                      <div class="orders-label">${name}</div>
                      <div class="muted" style="margin-top:4px; font-weight:900;">Qty: ${qty}</div>
                      <div class="muted" style="margin-top:4px; font-weight:900;">SKU: ${escapeHtml(it.sku || '')}</div>
                    </div>
                  </div>
                    <div style="text-align:right;">
                     <div class="muted" style="font-weight:1000;">Price: ₹${price}</div>
                     <div class="muted" style="font-weight:1000;">Line: ₹${lineTotal}</div>
                   </div>
                </div>
              </div>
            `;
          }).join('');

        }catch(e){
          console.error(e);
          container.innerHTML = '<div class="muted" style="color:#f87171;">Error loading order details.</div>';
        }
      }

      loadDetails();
    })();
  </script>

  </div>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'customer'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
        <a href="orders.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="order_dispatch.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
  <script src="js/desktop-dock.js"></script>
</body>
</html>






