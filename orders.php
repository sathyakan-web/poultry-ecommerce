<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer', 'logistics']);
require_once __DIR__ . '/auth/csrf.php';

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
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
  <title>SafeFlock Solution - Orders</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Gochi+Hand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/orders.css" />
  <link rel="stylesheet" href="css/doodle.css" />
  <script src="js/route-guard.js"></script>
  <script src="js/desktop-dock.js"></script>
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
      body[data-role="logistics"] .web-dock-container { left: 50%; transform: translateX(-50%); width: 100%; max-width: 100%; }
      body[data-role="logistics"] .flock-dock { width: 90% !important; max-width: 650px !important; height: 60px; padding: 6px 8px; }
      body[data-role="logistics"] .nav-item { flex: 1 !important; min-width: 0 !important; height: 60px; }
      body[data-role="logistics"] .icon-container { width: 28px; height: 28px; }
      body[data-role="logistics"] .nav-item svg { width: 22px; height: 22px; }
      body[data-role="logistics"] .nav-label { font-size: 11px; }
      body[data-role="logistics"] .active-indicator { height: 45px; display: block !important; }
      body[data-role="provider"] .flock-dock { width: 100% !important; max-width: 1000px !important; height: 70px; }
      body[data-role="provider"] .nav-item { flex: 1 !important; min-width: 0 !important; }
      body[data-role="provider"] .active-indicator { display: block !important; }
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
<body data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
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
      <div class="sidebar-title">Orders</div>
      <div style="margin-top:12px;">
        <a href="cart.php">🛒 Back to cart</a>
      </div>

      <div class="sidebar-section">
        <div class="sidebar-title">Quick actions</div>
        <div class="sidebar-actions">
          <a href="products.php?category=all">🛒 Add more products</a>
        </div>
      </div>
    </div>
  </aside>

  <main id="main" class="page">
    <div class="truck-transition" aria-hidden="true" style="display:none;"></div>

    <section class="orders-hero">
      <div class="container">
        <div class="orders-title">
          <h2 class="orders-title-text">Orders</h2>
          <span class="orders-badge" id="ordersCountBadge">Loading…</span>
        </div>

        <div style="height:10px;"></div>

        <div style="height:12px;"></div>

        <div class="orders-grid">
          <div class="doodle-card orders-card">
            <div class="orders-header-row">
              <div>
                <div class="orders-label">Items to order</div>
                <div class="orders-muted orders-subhint">Set delivery location per item.</div>
              </div>
              <div>
                <a class="doodle-btn doodle-btn-outline" href="cart.php">Edit Cart</a>
              </div>
            </div>

            <div id="ordersItems" style="margin-top: 14px; display:grid; gap:12px;">
              <div class="orders-muted" style="font-weight:900;">Loading cart items…</div>
            </div>
          </div>

          <div class="doodle-card orders-card">
            <div class="orders-label">Order summary</div>
            <div class="orders-summary-line"><span>Items</span><span id="sumItems">—</span></div>
            <div class="orders-summary-line"><span>Quantity total</span><span id="sumQty">—</span></div>
            <div class="orders-summary-line"><span>Estimated total</span><span id="sumTotal">—</span></div>

            <div style="height:14px;"></div>

            <button class="doodle-btn" id="confirmOrderBtn" type="button" style="width:100%;">✅ Confirm Order & Send SMS</button>
            <div class="orders-note">SMS is sent to your registered phone number (via TextBEE).</div>

            <div id="ordersStatus" class="orders-status" style="display:none;"></div>

            <div style="height:16px;"></div>
            <button class="doodle-btn doodle-btn-outline" id="initiateReturnBtn" type="button" disabled style="width:100%;">↩️ Initiate Return</button>
            <div class="orders-note">Enabled after successful order confirmation.</div>

            <div style="height:12px;"></div>
            <a class="doodle-btn doodle-btn-outline" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none; width:100%;"
               href="past_orders.php">
               🗂️ View Past Orders
            </a>

            <input type="hidden" id="orderId" value="" />
          </div>
        </div>
      </div>
    </section>
  </main>

  <script src="js/page-transition.js"></script>
  <script>
    (function(){
      const itemsWrap = document.getElementById('ordersItems');
      const ordersCountBadge = document.getElementById('ordersCountBadge');
      const sumItems = document.getElementById('sumItems');
      const sumQty = document.getElementById('sumQty');
      const sumTotal = document.getElementById('sumTotal');
      const confirmOrderBtn = document.getElementById('confirmOrderBtn');
      const statusEl = document.getElementById('ordersStatus');
      const returnBtn = document.getElementById('initiateReturnBtn');
      const orderIdInput = document.getElementById('orderId');

      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
      }

      // Must match pricing placeholder used in cart.php.
      function priceForProduct(productId, cartItems) {
        const item = (cartItems || []).find((x) => Number(x.product_id) === Number(productId));
        return item && item.price ? Number(item.price) : 0;
      }

      function sizeForProduct(productId, cartItems) {
        const item = (cartItems || []).find((x) => Number(x.product_id) === Number(productId));
        return item && item.size ? item.size : '—';
      }

      let lastCartItems = [];

      async function fetchCartForOrders() {
        const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const req = fetch('api/orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: new URLSearchParams({ action: 'get_cart_for_confirm', csrf_token: token }).toString(),
        });
        const res = await (window.sfsWithLoading ? window.sfsWithLoading(req) : req);
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to load cart for order');
        return data;
      }

      function setStatus(text, ok) {
        statusEl.style.display = 'block';
        statusEl.classList.remove('ok','err');
        statusEl.classList.add(ok ? 'ok' : 'err');
        statusEl.textContent = text;
      }

      function renderItems(cart) {
        lastCartItems = cart || [];

        if (!cart || !cart.length) {
          itemsWrap.innerHTML = '<div class="orders-muted" style="font-weight:900;">Your cart is empty.</div>';
          ordersCountBadge.textContent = '0 items';
          sumItems.textContent = '0';
          sumQty.textContent = '0';
          sumTotal.textContent = '₹0';
          confirmOrderBtn.disabled = true;
          return;
        }

        const count = cart.length;
        const qtyTotal = cart.reduce((a, x) => a + Number(x.qty || 0), 0);
        const total = cart.reduce((a, x) => {
          const price = priceForProduct(x.product_id, cart);
          return a + price * Number(x.qty || 0);
        }, 0);

        ordersCountBadge.textContent = count + ' item' + (count === 1 ? '' : 's');
        sumItems.textContent = String(count);
        sumQty.textContent = String(qtyTotal);
        sumTotal.textContent = '₹' + total.toFixed(2);

        itemsWrap.innerHTML = cart.map((item, idx) => {
          const pid = item.product_id;
          const name = escapeHtml(item.name || 'Product');
          const qty = Number(item.qty || 0);
          const logo = item.logo_path ? escapeHtml(item.logo_path) : '';
          const price = priceForProduct(pid, cart);
          const size = sizeForProduct(pid, cart);
          const lineTotal = price * qty;

          return `
            <div class="doodle-card orders-item">
              <img class="orders-item-logo product-image" src="${logo}" alt="" onerror="this.style.display='none'" />
              <div class="orders-item-meta">
                <div style="display:flex; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                  <div>
                    <div class="product-title">${name}</div>
                    <div class="desc-text" style="margin-top:4px;">Qty: ${qty}</div>
                    <div class="desc-text" style="margin-top:2px;">Size: ${escapeHtml(size)}</div>
                  </div>
                  <div style="text-align:right;">
                    <div class="info-value price" style="font-size:13px;">Price: ₹${price.toFixed(2)}</div>
                    <div class="info-value price" style="font-size:13px;">Line: ₹${lineTotal.toFixed(2)}</div>
                  </div>
                </div>

                ${idx === 0 ? `
                  <div id="ordersDeliveryBlock" style="grid-column: 1 / -1; margin: 0 0 6px 0;">
                    <div class="orders-label" style="margin-bottom:6px;">Delivery Address (whole order)</div>
                    <div class="orders-note" style="margin-top:0; margin-bottom:6px;">Enter the delivery address for all products in this order. Example: Farm Gate A, Lagos / Shed 3.</div>
                    <input class="orders-input orders-input-professional doodle-input" type="text" required value="" id="deliveryAddressWholeOrder" placeholder="e.g., Farm Gate A, Lagos / Shed 3" />
                  </div>
                ` : ''}

              </div>
            </div>
          `;
        }).join('');

        confirmOrderBtn.disabled = false;
      }

      async function load() {
        try {
          confirmOrderBtn.disabled = true;
          setStatus('', true);
          returnBtn.disabled = true;
          orderIdInput.value = '';

          const data = await fetchCartForOrders();
          renderItems(data.cart || []);

          const cached = sessionStorage.getItem('sfs_last_order_confirmed');
          if (cached) {
            const parsed = JSON.parse(cached);
            if (parsed && parsed.ok) {
              orderIdInput.value = parsed.orderId || '';
              returnBtn.disabled = !parsed.ok;
            }
          }
        } catch (e) {
          console.error(e);
          itemsWrap.innerHTML = '<div class="orders-muted" style="font-weight:900;">Error loading order items.</div>';
          ordersCountBadge.textContent = '0 items';
          sumItems.textContent = '0';
          sumQty.textContent = '0';
          sumTotal.textContent = '₹0';
          confirmOrderBtn.disabled = true;
          returnBtn.disabled = true;
          setStatus('Error: ' + (e && e.message ? e.message : 'Unknown error'), false);
        }
      }

      confirmOrderBtn.addEventListener('click', async () => {
        try {
          confirmOrderBtn.disabled = true;
          setStatus('Sending order confirmation SMS…', true);

          const addrInput = document.getElementById('deliveryAddressWholeOrder');
          const address = (addrInput && addrInput.value ? String(addrInput.value) : '').trim();
          if (!address) {
            confirmOrderBtn.disabled = false;
            setStatus('Please enter a delivery address for the whole order.', false);
            return;
          }

          const res = await fetch('api/orders.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
              body: new URLSearchParams({ action: 'confirm', delivery_address: address, csrf_token: (document.querySelector('meta[name="csrf-token"]')?.content || '') }).toString(),
            });

          const rawText = await res.clone().text();
          let data;
          try {
            data = JSON.parse(rawText);
          } catch (e) {
            console.error('[orders] confirm response was not JSON:', rawText.slice(0, 500));
            throw new Error('Server returned an unexpected response. Please refresh and try again.');
          }
          if (!data.ok) throw new Error(data.error || 'Order confirmation failed');

          const orderId = data.order_id || data.orderId || '';
          orderIdInput.value = orderId;
          returnBtn.disabled = false;

          sessionStorage.setItem('sfs_last_order_confirmed', JSON.stringify({ ok: true, orderId }));

          setStatus('Order Confirmed. SMS sent successfully ✅', true);
        } catch (e) {
          console.error(e);
          setStatus('Order failed: ' + (e && e.message ? e.message : 'Unknown error'), false);
        } finally {
          confirmOrderBtn.disabled = false;
        }
      });

      returnBtn.addEventListener('click', async () => {
        const orderId = orderIdInput.value || 'local';
        window.location.href = 'returns.php?order_id=' + encodeURIComponent(orderId);
      });

      const yearEl = document.getElementById('year');
      if (yearEl) yearEl.textContent = new Date().getFullYear();
      load();

      document.querySelectorAll('.sidebar a').forEach((a) => a.addEventListener('click', () => document.body.classList.remove('nav-open')));
    })();
  </script>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'customer'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
        <a href="cart.php" class="nav-item" data-page="cart"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div><span class="nav-label">Cart</span></a>
        <a href="orders.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="calculator.php" class="nav-item" data-page="calculator"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div><span class="nav-label">Dosage Calc</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="provider_dashboard.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="logistics_route.php" class="nav-item" data-page="route"><div class="icon-container"><svg viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg></div><span class="nav-label">Route</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
</body>
</html>


