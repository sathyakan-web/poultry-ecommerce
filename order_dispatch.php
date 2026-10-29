<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['provider', 'logistics']);
require_once __DIR__ . '/auth/csrf.php';

$user = sfs_current_user();
$role = sfs_current_role();
$csrfToken = sfs_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
  <title>SafeFlock Solution - Order Dispatch</title>
  <meta name="description" content="Order dispatch management for SafeFlock Solution. Assign drivers, track packages, and manage delivery operations." />
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/doodle.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/loading.css" />
  <script src="js/theme.js"></script>
  <style>
    body { font-family: 'Inter', sans-serif; background-color: #0a0a0a; color: #f3f4f6; -webkit-tap-highlight-color: transparent; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

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
  </style>
</head>
<body class="doodle-dark" data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">

  <header class="doodle-header">
    <h1 class="brand-title">Poultry</h1>
  </header>
  <div class="doodle-container">

  <a class="skip-link" href="#main">Skip</a>

  <header class="site-header">
    <div class="container header-inner">
      <div class="brand">
        <span class="brand-name" aria-label="SAFEFLOCK SOLUTION">SAFEFLOCK SOLUTION</span>
      </div>
      <button class="hamburger" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav class="top-nav" aria-label="Primary">
        <a class="top-nav-link" href="index.php">🏠 Home</a>
        <a class="top-nav-link" href="products.php">🛒 Products</a>
        <a class="top-nav-link" href="orders.php">📦 Orders</a>
        <a class="top-nav-link" href="auth/logout.php">🚪 Logout</a>
      </nav>
    </div>
  </header>

  <main id="main" class="page">
    <section class="w-full pb-10">
      <div class="flex items-center gap-3 mb-5">
        <a href="index.php" class="w-10 h-10 rounded-full border border-white/10 flex items-center justify-center text-gray-300 hover:text-white transition-colors">
          <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <h1 class="text-xl font-bold tracking-wide">Order Dispatch</h1>
      </div>
      <div class="bg-[#121212] border border-[#b5c43d]/20 rounded-3xl p-5">
        <p class="text-xs text-gray-400 mb-4">Monitor all customer orders and their current status.</p>
        <div id="dispatch-loading" class="hidden text-center text-gray-400 py-8">Loading orders...</div>
        <div id="dispatch-error" class="hidden text-center text-red-400 py-8"></div>
        <div id="dispatch-list" class="space-y-3"></div>
      </div>
    </section>
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
  </div>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="order_dispatch.php" class="nav-item active" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item active" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="order_dispatch.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var navItems = document.querySelectorAll('.nav-item');
      var indicator = document.getElementById('activeIndicator');
      var dock = document.getElementById('bottomNav');

      function updateIndicator(targetElement, instant) {
        if (!dock || !indicator || !targetElement) return;
        var dockRect = dock.getBoundingClientRect();
        var targetRect = targetElement.getBoundingClientRect();
        var leftPos = targetRect.left - dockRect.left;
        var itemWidth = targetRect.width;
        if (instant) {
          indicator.style.transition = 'none';
        } else {
          indicator.style.transition = 'left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
        }
        indicator.style.left = leftPos + 'px';
        indicator.style.width = itemWidth + 'px';
        if (instant) {
          indicator.offsetHeight;
          indicator.style.transition = '';
        }
      }

      var initialActive = document.querySelector('.nav-item.active');
      if (initialActive) {
        setTimeout(function() { updateIndicator(initialActive, true); }, 50);
      }

      navItems.forEach(function(item) {
        item.addEventListener('click', function(e) {
          if (item.classList.contains('logout')) {
            e.preventDefault();
            return;
          }
          if (item.classList.contains('active')) return;
          navItems.forEach(function(nav) { nav.classList.remove('active'); });
          item.classList.add('active');
          updateIndicator(item);
        });
      });

      window.addEventListener('resize', function() {
        var currentActive = document.querySelector('.nav-item.active');
        if (currentActive) {
          updateIndicator(currentActive, true);
        }
      });
    });
  </script>
  <script src="js/doodle.js"></script>
</body>
</html>












