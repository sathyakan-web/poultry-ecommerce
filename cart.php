<?php
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'provider']);
require_once __DIR__ . '/auth/csrf.php';

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$staticJs = 'js/app.js';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

?><!doctype html>
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
  <title>SafeFlock Solution - Cart</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/doodle.css" />
  <script src="js/route-guard.js"></script>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
   <style>
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
      @media (min-width: 768px) {
        body { display: block !important; overflow-y: auto; }
        .web-dock-container { position: fixed !important; }
        .flock-dock { width: 100% !important; max-width: 1000px !important; height: 70px; }
        .nav-item { flex: 1 !important; min-width: 0 !important; }
        .active-indicator { display: block !important; }
      }

     .top-header {
       display: flex;
       justify-content: space-between;
       align-items: center;
       margin-bottom: 10px;
       flex-wrap: wrap;
       gap: 20px;
     }
     .brand-logo-container {
       display: flex;
       align-items: center;
       gap: 8px;
       text-decoration: none;
     }
      .logo-image-wrapper {
        width: 72px;
        height: 72px;
        display: flex;
        justify-content: center;
        align-items: center;
      }
      .logo-image-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        animation: gentle-bob 3s infinite ease-in-out;
      }
      @keyframes gentle-bob {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-3px); }
      }
     .brand-text {
       display: flex;
       flex-direction: column;
     }
     .brand-text-main {
       font-family: var(--font-display);
       font-size: 28px;
       font-weight: bold;
       color: var(--ink);
       line-height: 1;
       letter-spacing: 1px;
     }
     .brand-text-sub {
       font-size: 13px;
       font-weight: 900;
       color: var(--action-btn);
       letter-spacing: 3px;
       text-transform: uppercase;
       padding-left: 2px;
     }
   </style>

</head>
<body class="doodle-dark" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="doodle-container">

  <a class="skip-link" href="#main">Skip</a>

  <div class="top-header">
    <a href="index.php" class="brand-logo-container">
      <div class="logo-image-wrapper">
         <img src="images/sfs_icon.png" alt="SafeFlock Solution Logo" onerror="this.src='https://placehold.co/100x100/161616/fbbf24?text=SF'">
      </div>
      <div class="brand-text">
        <span class="brand-text-main">SafeFlock Solution</span>
        <span class="brand-text-sub">SafeFlock Solution</span>
      </div>
    </a>
  </div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
      <div class="sidebar-title">Cart</div>
      <div style="margin-top:12px;">
        <a href="products.php?category=all">🛒 Back to products</a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Items in your cart</div>
        <div class="sidebar-actions">
          <a href="#" id="cartReload">🔄 Refresh</a>
        </div>
      </div>
    </div>
  </aside>

  <main id="main" class="page">
    <section class="section" aria-label="Cart">
      <div class="container">
        <div class="section-head">
          <h2>Your Cart</h2>
          <p class="muted">Update quantities or remove items.</p>
          <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="products.php?category=all">🛒 Continue shopping</a>
            <button type="button" id="checkoutBtn" class="btn btn-primary">✅ Checkout → Orders</button>
          </div>
        </div>

        <div class="product-grid" id="cartGrid" style="grid-template-columns: repeat(4, 1fr);">
          <div class="doodle-card">Loading cart…</div>
        </div>

      </div>
    </section>
  </main>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'customer'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
        <a href="cart.php" class="nav-item active" data-page="cart"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div><span class="nav-label">Cart</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="calculator.php" class="nav-item" data-page="calculator"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div><span class="nav-label">Dosage Calc</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="provider_dashboard.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="logistics_route.php" class="nav-item" data-page="route"><div class="icon-container"><svg viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg></div><span class="nav-label">Route</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
   <style>
    #cartGrid.product-grid {
      grid-template-columns: repeat(4, 1fr) !important;
      gap: 16px !important;
    }

    #cartGrid .product-image-container {
      width: 28% !important;
      aspect-ratio: 1 / 1 !important;
    }

    #cartGrid .doodle-card {
      padding: 10px !important;
    }
  </style>
  <script src="js/desktop-dock.js"></script>
   <script src="js/doodle.js"></script>
   <script>
   (function(){
     function escapeHtml(str) {
       return String(str ?? '').replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
     }

     const grid = document.getElementById('cartGrid');
     const reloadBtn = document.getElementById('cartReload');
     if (!grid) return;

     function csrf() {
       return document.querySelector('meta[name="csrf-token"]')?.content || '';
     }

     async function loadCart() {
       grid.innerHTML = '<div class="doodle-card">Loading cart…</div>';
       try {
         const res = await fetch('api/cart.php', {
           method: 'POST',
           headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': csrf() },
           body: new URLSearchParams({ action: 'get' }).toString()
         });
         const data = await res.json();
         if (!data.ok) throw new Error(data.error || 'Failed');

         const items = data.cart || [];
         if (!items.length) {
           grid.innerHTML = '<div class="doodle-card">Your cart is empty.</div>';
           return;
         }

          grid.innerHTML = items.map(item => {
            const price = Number(item.price || 0).toFixed(2);
            const qty = Number(item.qty || 0);
            const line = (Number(item.price || 0) * qty).toFixed(2);
            const img = escapeHtml(item.logo_path || '');
            const imgBlock = img
              ? '<div class="product-image-container"><img class="product-image" src="' + img + '" alt="" onerror="this.style.display=\'none\'" /></div>'
              : '<div class="product-image-container"><span class="muted">No image</span></div>';
            return `
              <div class="doodle-card">
                <div style="display:flex; flex-direction:column; gap:8px; align-items:center; text-align:center;">
                  ${imgBlock}
                  <div class="product-title">${escapeHtml(item.name || 'Product')}</div>
                  <div class="desc-text">SKU: ${escapeHtml(item.sku || '')}</div>
                  <div class="desc-text">Price: ₹${price}</div>
                  <div class="desc-text">Qty: ${qty}</div>
                  <div class="desc-text">Line total: ₹${line}</div>
                  <button class="btn" data-remove="${escapeHtml(item.product_id)}">Remove</button>
                </div>
              </div>
            `;
          }).join('');

         grid.querySelectorAll('button[data-remove]').forEach(btn => {
           btn.addEventListener('click', async () => {
             const pid = btn.getAttribute('data-remove');
             await fetch('api/cart.php', {
               method: 'POST',
               headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': csrf() },
               body: new URLSearchParams({ action: 'remove', product_id: pid }).toString()
             });
             loadCart();
           });
         });
       } catch (e) {
         grid.innerHTML = '<div class="doodle-card">Error loading cart.</div>';
       }
     }

      reloadBtn?.addEventListener('click', (e) => { e.preventDefault(); loadCart(); });
      document.getElementById('checkoutBtn')?.addEventListener('click', () => {
        window.location.href = 'orders.php';
      });
      loadCart();
    })();
   </script>
   </body>
   </html>
















