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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <title>Past Orders - SafeFlock Solution</title>
  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/doodle.css" />
  <script src="js/route-guard.js"></script>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
  <style>
    :root {
      --ink: #2b2b2b;
      --paper-front: #ffffff;
      --paper-back: #f8f9fa;
      --paper-yellow: #fffdf5;
      --bg-body: #f0f0f0;
      --primary-btn: #ffe66d;
      --secondary-btn: #4ecdc4;
      --action-btn: #ff6b6b;
      --success: #06d6a0;
      --dock-bg: #ffffff;
      --border-width: 3px;
      --shadow-offset: 6px;
      --grid-line-color: rgba(0,0,0,0.05);
      --sketch-r1: 8px 24px 8px 24px / 24px 8px 24px 8px;
      --sketch-r2: 24px 8px 24px 8px / 8px 24px 8px 24px;
      --sketch-btn: 12px 18px 12px 18px / 18px 12px 18px 12px;
      --font-ui: 'Comic Neue', 'Comic Sans MS', sans-serif;
      --font-display: 'Gochi Hand', 'Marker Felt', cursive;
      --warning: #f5d76c;
    }

    body.dark-theme {
      --ink: #fdfbf7;
      --paper-front: #1a1a1c;
      --paper-back: #2b2b2e;
      --paper-yellow: #2a2820;
      --bg-body: #0f0f11;
      --primary-btn: #dcb846;
      --dock-bg: #1a1a1c;
      --grid-line-color: rgba(255,255,255,0.05);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; transition: background-color 0.4s ease, border-color 0.4s ease, color 0.4s ease; }

    body {
      font-family: var(--font-ui);
      background-color: var(--bg-body);
      color: var(--ink);
      min-height: 100vh;
      overflow-x: hidden;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding-bottom: 120px;
      background-image:
        linear-gradient(var(--grid-line-color) 2px, transparent 2px),
        linear-gradient(90deg, var(--grid-line-color) 2px, transparent 2px);
      background-size: 30px 30px;
    }

    .container {
      width: 100%;
      max-width: 900px;
      padding: 30px 20px;
      display: flex;
      flex-direction: column;
      gap: 25px;
    }

    .top-header {
      width: 100%;
      max-width: 900px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px;
      margin-top: 20px;
      background-color: var(--paper-front);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-r1);
      box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink);
      z-index: 50;
    }

    .brand-wrapper {
      display: flex;
      align-items: center;
      gap: 15px;
      text-decoration: none;
    }

    .logo-box {
      width: 40px; height: 50px;
      background-color: #ffffff;
      border-radius: 12px;
      display: flex; justify-content: center; align-items: center;
      border: 2px solid var(--ink);
      padding: 5px;
      transform: rotate(-3deg);
      transition: transform 0.3s;
    }
    .brand-wrapper:hover .logo-box { transform: rotate(5deg) scale(1.1); }
    .logo-box img { width: 100%; height: 100%; object-fit: contain; }

    .brand-text-stack { display: flex; flex-direction: column; }
    .brand-title-main { font-family: var(--font-display); font-size: 28px; font-weight: bold; color: var(--ink); line-height: 1; margin-bottom: 2px; }
    .brand-title-sub { font-size: 11px; font-weight: 900; color: var(--action-btn); text-transform: uppercase; letter-spacing: 2px; line-height: 1; }

    .page-title { font-family: var(--font-display); font-size: 42px; color: var(--ink); text-shadow: 2px 2px 0px rgba(0,0,0,0.1); transform: rotate(-1deg); }

    .toolbar {
      display: flex; justify-content: space-between; align-items: center; padding-bottom: 15px; border-bottom: 2px dashed var(--ink); margin-bottom: 10px;
    }

    .doodle-btn {
      background: var(--paper-front); color: var(--ink); font-family: inherit; font-size: 16px; font-weight: bold;
      padding: 8px 20px; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-btn);
      cursor: pointer; box-shadow: 3px 3px 0px var(--ink); transition: all 0.1s ease;
      display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none;
    }
    .doodle-btn:active { transform: translate(3px, 3px); box-shadow: 0px 0px 0px transparent; }
    .doodle-btn:hover { background: var(--paper-yellow); }

    .orders-container { display: flex; flex-direction: column; gap: 20px; }

    .order-card {
      background: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-r1);
      padding: 25px; display: flex; justify-content: space-between; align-items: center;
      box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink);
      transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      position: relative; cursor: pointer; animation: slide-up 0.5s ease backwards; flex-wrap: wrap; gap: 20px;
    }
    .order-card:nth-child(even) { border-radius: var(--sketch-r2); transform: rotate(0.5deg); background-color: var(--paper-yellow);}
    .order-card:nth-child(odd) { transform: rotate(-0.5deg); }
    .order-card:hover { transform: translateY(-5px) scale(1.02) rotate(0deg); box-shadow: 8px 8px 0px var(--ink); }

    .order-info { display: flex; flex-direction: column; gap: 8px; }
    .o-id { font-family: var(--font-display); font-size: 24px; color: var(--ink); }
    .o-loc { font-size: 16px; color: #888; font-weight: bold;}

    .order-actions { display: flex; flex-direction: column; align-items: flex-end; gap: 15px; }
    .status-text { font-family: var(--font-display); font-size: 22px; font-weight: bold; letter-spacing: 1px;}

    .status-pending { color: var(--warning); }
    .status-transit { color: var(--secondary-btn); }
    .status-delivered { color: var(--success); }

    .modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
      display: flex; justify-content: center; align-items: center;
      z-index: 2000; opacity: 0; pointer-events: none; transition: opacity 0.3s ease; padding: 20px;
    }
    .modal-overlay.active { opacity: 1; pointer-events: all; }

    .receipt-paper {
      background-color: #fdfbf7;
      color: #2b2b2b;
      width: 100%; max-width: 500px; max-height: 90vh;
      border: 3px solid #2b2b2b; border-radius: 4px; padding: 30px; position: relative;
      transform: translateY(-50px) rotate(-2deg); transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      box-shadow: 10px 10px 0px rgba(0,0,0,0.8); overflow-y: auto;
      background-image:
        linear-gradient(to right, #fdfbf7 100%, transparent 100%),
        radial-gradient(circle at 10px 0, transparent 10px, #fdfbf7 11px);
      background-size: 100% 100%, 20px 20px; background-repeat: no-repeat, repeat-x;
      background-position: 0 0, 0 100%; padding-bottom: 50px;
    }

    .modal-overlay.active .receipt-paper { transform: translateY(0) rotate(1deg); }

    .close-btn {
      position: absolute; top: 15px; right: 20px; font-family: var(--font-display);
      font-size: 32px; font-weight: bold; cursor: pointer; color: #2b2b2b; transition: transform 0.2s; line-height: 1;
    }
    .close-btn:hover { transform: scale(1.2) rotate(10deg); color: var(--action-btn); }

    .receipt-header { text-align: center; border-bottom: 2px dashed #888; padding-bottom: 15px; margin-bottom: 10px; }
    .r-title { font-family: var(--font-display); font-size: 32px; font-weight: bold; line-height: 1; margin-bottom: 10px;}
    .r-meta { font-size: 14px; font-weight: bold; color: #666; }

    .product-list { display: flex; flex-direction: column; gap: 15px; margin-bottom: 10px; }

    .product-item {
      display: flex; align-items: center; gap: 15px; padding: 10px;
      border: 2px solid #2b2b2b; border-radius: 12px; background: #fff; box-shadow: 3px 3px 0px #2b2b2b;
      opacity: 0; transform: translateY(20px);
    }
    .modal-overlay.active .product-item { animation: item-slide-up 0.4s ease forwards; }

    .p-icon-box { width: 60px; height: 60px; flex-shrink: 0; background: #f4f6f8; border-radius: 8px; border: 2px solid #2b2b2b; display: flex; justify-content: center; align-items: center; overflow: hidden;}
    .p-icon-box img { width: 100%; height: 100%; object-fit: contain; }
    .p-icon-box svg { width: 80%; height: 80%; }

    .p-details { flex-grow: 1; }
    .p-name { font-weight: 900; font-size: 16px; margin-bottom: 2px; color: #2b2b2b;}
    .p-desc { font-size: 12px; color: #666; font-weight: bold; margin-bottom: 4px; }
    .p-qty { font-size: 13px; color: #ff6b6b; font-weight: bold;}
    .p-price { font-family: var(--font-display); font-size: 22px; font-weight: bold; color: #2b2b2b;}

    .receipt-total {
      border-top: 3px solid #2b2b2b; padding-top: 15px; display: flex; justify-content: space-between;
      align-items: center; font-family: var(--font-display); font-size: 28px; font-weight: bold; color: #2b2b2b;
    }

    .dock-container {
      position: fixed; bottom: 10px; left: 0; right: 0; z-index: 1000;
      display: flex; justify-content: center; pointer-events: none;
    }

    .flock-dock {
      pointer-events: auto; background-color: var(--dock-bg); border: var(--border-width) solid var(--ink);
      border-radius: 50px; box-shadow: 0px 6px 0px var(--ink), 0px 8px 20px rgba(0,0,0,0.1);
      display: flex; align-items: center; padding: 8px 16px; gap: 10px; height: 65px; position: relative;
    }

    .nav-item {
      display: flex; flex-direction: column; align-items: center; justify-content: center; text-decoration: none; color: var(--ink);
      width: 52px; height: 100%; position: relative; z-index: 2; cursor: pointer; transition: transform 0.2s;
    }

    .nav-item svg { width: 20px; height: 20px; fill: none; stroke: var(--ink); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); margin-bottom: 2px;}
    .nav-label { font-size: 11px; font-weight: 900; }

    .active-indicator {
      position: absolute; width: 52px; height: 45px; background-color: var(--primary-btn);
      border: 2px dashed var(--ink); border-radius: 35px 40px 35px 40px / 40px 35px 40px 35px;
      top: 10px; left: 20px; z-index: 1; transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      animation: squish 4s infinite alternate;
    }

    .nav-item.active { color: var(--ink); }
    .nav-item.active svg { transform: translateY(-4px) scale(1.15); filter: drop-shadow(2px 2px 0px rgba(0,0,0,0.1)); }

    @keyframes slide-up { 0% { opacity: 0; transform: translateY(30px); } 100% { opacity: 1; transform: translateY(0); } }
    @keyframes item-slide-up { to { opacity: 1; transform: translateY(0); } }
    @keyframes squish { 0% { border-radius: 35px 40px 35px 40px / 40px 35px 40px 35px; } 100% { border-radius: 40px 35px 40px 35px / 35px 40px 35px 40px; } }

    @media (max-width: 768px) {
      .flock-dock { gap: 5px; padding: 10px 5px; border-radius: 25px; width: 95%;}
      .nav-item { width: 40px; }
      .nav-item svg { width: 20px; height: 20px; }
      .nav-label { font-size: 9px; }
      .active-indicator { width: 40px; left: 5px;}
    }
  </style>
</head>
<body data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="doodle-container">

    <div class="top-header">
      <a href="index.php" class="brand-wrapper">
        <div class="logo-box">
          <img src="images/sfs_icon.png" alt="SafeFlock Solution Logo" onerror="this.src='https://placehold.co/100x100/161616/fbbf24?text=SF'">
        </div>
        <div class="brand-text-stack">
          <span class="brand-title-main">SafeFlock Solution</span>
          <span class="brand-title-sub">SafeFlock Solution</span>
        </div>
      </a>
    </div>

    <main id="main" class="page">
      <section class="past-orders-hero">
        <div class="container">
          <div>
            <h1 class="page-title">Past Orders</h1>
            <p style="font-weight: bold; font-size: 16px; color: #888; margin-top: 5px;">Showing DISPATCHED orders only.</p>
          </div>

          <div class="toolbar">
            <a class="doodle-btn" href="orders.php">✅ Back to Orders</a>
            <button type="button" class="doodle-btn" id="refreshPastOrdersBtn" title="Refresh order history">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
              Refresh
            </button>
          </div>

          <div class="orders-container" id="pastOrdersContainer">
            <div class="orders-muted" style="font-weight:900;">Loading past orders…</div>
          </div>
        </div>
      </section>
    </main>

    <div class="modal-overlay" id="orderModal">
      <div class="receipt-paper" id="receiptContent">
        <div class="close-btn" id="modalCloseBtn">×</div>
        <div class="receipt-header">
          <div class="r-title">Order Receipt</div>
          <div class="r-meta" id="modalOrderId">ORD-XXXXXX</div>
          <div class="r-meta" id="modalDate" style="margin-top: 5px;">Date: --</div>
        </div>
        <div class="product-list" id="productList"></div>
        <div class="receipt-total">
          <span>Total</span>
          <span id="modalTotal">₹0.00</span>
        </div>
      </div>
    </div>

    <script src="js/page-transition.js"></script>
    <script>
      (function(){
      const container = document.getElementById('pastOrdersContainer');
      const refreshBtn = document.getElementById('refreshPastOrdersBtn');
      const modal = document.getElementById('orderModal');
      const productList = document.getElementById('productList');
      const modalCloseBtn = document.getElementById('modalCloseBtn');

      function escapeHtml(str) {
        return String(str).replace(/[&<>"']/g, (m) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m]));
      }

      function closeModal(){
        modal.classList.remove('active');
      }

      async function loadPastOrders(){
        try{
          container.innerHTML = '<div class="orders-muted" style="font-weight:900;">Loading past orders…</div>';

          const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const res = await fetch('api/orders.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body:new URLSearchParams({action:'get_my_orders', csrf_token: token}).toString(),
          });
          const data = await res.json();
          if(!data.ok) throw new Error(data.error || 'Failed to load past orders');

          const orders = data.orders || [];
          if(!orders.length){
            container.innerHTML = '<div class="orders-muted" style="font-weight:900;">No past orders found.</div>';
            return;
          }

          container.innerHTML = orders.map((o, idx) => {
            const orderId = o.order_id || '';
            const addr = o.delivery_address || '';
            const status = o.status || 'DISPATCHED';
            const statusLabel = o.status_label || status;
            const statusClass = status === 'DELIVERED' ? 'status-delivered'
                              : status === 'IN_TRANSIT' ? 'status-transit'
                              : status === 'ASSIGNED' ? 'status-transit'
                              : status === 'RETURN_REQUESTED' ? 'status-transit'
                              : status === 'RETURNED' ? 'status-delivered'
                              : 'status-pending';
            const delay = (0.1 + (idx * 0.1)) + 's';
            return `
              <div class="order-card" style="animation-delay: ${delay};" data-order-id="${escapeHtml(orderId)}">
                <div class="order-info">
                  <span class="o-id">${escapeHtml(orderId)}</span>
                  <span class="o-loc">${escapeHtml(addr).slice(0, 80)}${addr.length > 80 ? '…' : ''}</span>
                </div>
                <div class="order-actions">
                  <span class="status-text ${statusClass}">${escapeHtml(statusLabel)}</span>
                  <button type="button" class="doodle-btn view-details-btn" style="background-color: transparent;" data-order-id="${escapeHtml(orderId)}">View details</button>
                </div>
              </div>
            `;
          }).join('');

          container.querySelectorAll('.view-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
              e.stopPropagation();
              openOrderDetails(btn.getAttribute('data-order-id'));
            });
          });

          container.querySelectorAll('.order-card').forEach(card => {
            card.addEventListener('click', () => {
              openOrderDetails(card.getAttribute('data-order-id'));
            });
          });
        }catch(e){
          console.error(e);
          container.innerHTML = '<div class="orders-muted" style="font-weight:900; color:#f87171;">Error loading past orders.</div>';
        }
      }

      async function openOrderDetails(orderId){
        try{
          const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const res = await fetch('api/orders.php',{
            method:'POST',
            headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
            body:new URLSearchParams({action:'get_order_details', order_id: orderId, csrf_token: token}).toString(),
          });
          const data = await res.json();
          if(!data.ok) throw new Error(data.error || 'Failed to load order details');

          const items = data.items || [];
          document.getElementById('modalOrderId').textContent = orderId || 'ORD-XXXXXX';
          const firstUpdatedAt = items.length ? (items[0].updated_at || '--') : '--';
          document.getElementById('modalDate').textContent = firstUpdatedAt !== '--' ? ('Date: ' + firstUpdatedAt) : 'Date: --';

          productList.innerHTML = items.map((item, index) => {
            const iconSvg = `<img class="p-icon-box-img" src="${escapeHtml(item.logo_path || '')}" alt="" onerror="this.style.display='none'" />`;
            const fallbackSvg = `<svg class="svg-bottle" viewBox="0 0 80 120"><rect x="20" y="40" width="40" height="70" rx="5" /><rect x="30" y="10" width="20" height="15" fill="#4ecdc4" stroke="none"/><rect x="25" y="25" width="30" height="15" /><rect x="25" y="60" width="30" height="30" fill="#fff" /><text x="40" y="75" font-family="Arial" font-weight="bold" font-size="12" text-anchor="middle" stroke="none" fill="#333">Vax</text></svg>`;
            return `
              <div class="product-item" style="animation-delay: ${0.1 + (index * 0.1)}s">
                <div class="p-icon-box">
                  ${item.logo_path ? iconSvg : fallbackSvg}
                </div>
                <div class="p-details">
                  <div class="p-name">${escapeHtml(item.name || 'Product')}</div>
                  <div class="p-desc">${escapeHtml(item.sku || '')}</div>
                  <div class="p-qty">Qty: ${escapeHtml(String(item.qty || 0))}</div>
                </div>
                <div class="p-price">₹${Number(item.price || 0).toFixed(2)}</div>
              </div>
            `;
          }).join('');

          const total = items.reduce((a, x) => a + Number(x.price || 0) * Number(x.qty || 0), 0);
          document.getElementById('modalTotal').textContent = '₹' + total.toFixed(2);
          modal.classList.add('active');
        }catch(e){
          console.error(e);
          alert('Failed to load order details.');
        }
      }

      modalCloseBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
      });

      refreshBtn.addEventListener('click', loadPastOrders);
      loadPastOrders();
      })();
    </script>
  </div>
</body>
</html>

