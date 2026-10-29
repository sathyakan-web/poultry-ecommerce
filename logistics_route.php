<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['logistics']);
require_once __DIR__ . '/auth/csrf.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <meta name="description" content="Delivery route map for SafeFlock Solution logistics. Visualize dispatch batches and optimize poultry product delivery routes." />
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <title>Delivery Route Map | SafeFlock Solution</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/rbac.css" />
  <script src="js/route-guard.js"></script>
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
  <style>
    .route-map {
      position: relative;
      border-radius: 18px;
      border: 1px solid rgba(212,175,55,0.3);
      background:
        radial-gradient(600px circle at 20% 25%, rgba(16,185,129,0.12), transparent 55%),
        radial-gradient(500px circle at 78% 65%, rgba(245,158,11,0.1), transparent 50%),
        rgba(4,7,5,0.6);
      min-height: 420px;
      overflow: hidden;
      margin-top: 16px;
    }
    .route-map svg { width: 100%; height: 100%; display: block; }
    .route-stop {
      position: absolute;
      display: flex;
      align-items: center;
      gap: 8px;
      font-weight: 900;
      font-size: 0.82rem;
      color: #e2e8f0;
      background: rgba(0,0,0,0.55);
      border: 1px solid rgba(212,175,55,0.35);
      border-radius: 999px;
      padding: 6px 12px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.4);
    }
    .route-stop .dot { width: 10px; height: 10px; border-radius: 50%; background: #fbbf24; box-shadow: 0 0 12px rgba(251,191,36,0.8); }

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
      <button class="hamburger" type="button" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
      <nav class="top-nav" aria-label="Primary">
        <a class="top-nav-link" href="index.php">🏠 Home</a>
        <a class="top-nav-link" href="logistics_dashboard_spa.php">🚚 Logistics Orders</a>
        <a class="top-nav-link" href="logistics_route.php">🗺️ Delivery Route Map</a>
        <a class="top-nav-link" href="auth/logout.php">🚪 Logout</a>
      </nav>
    </div>
  </header>

  <main id="main" class="page">
    <section class="section">
      <div class="container">
        <div class="sfs-logistics-hero">
          <h2 style="margin:0; font-size:1.5rem; font-weight:1000; color:rgba(255,255,255,0.95);">🗺️ Delivery Route Map</h2>
          <p class="muted" style="margin-top:6px;">Visual delivery staging area for the current dispatch batch.</p>
        </div>

        <div class="route-map">
          <svg viewBox="0 0 900 420" preserveAspectRatio="none" aria-hidden="true">
            <path d="M90 90 C 260 60, 360 160, 520 130 S 760 180, 830 320" fill="none"
                  stroke="rgba(212,175,55,0.6)" stroke-width="3" stroke-dasharray="10 8" />
            <path d="M120 330 C 300 300, 420 260, 600 290 S 760 300, 830 320" fill="none"
                  stroke="rgba(16,185,129,0.5)" stroke-width="3" stroke-dasharray="6 8" />
          </svg>

          <div class="route-stop" style="top:12%; left:6%;">
            <span class="dot"></span> Dispatch Hub
          </div>
          <div class="route-stop" style="top:30%; left:38%;">
            <span class="dot"></span> Farm Gate A
          </div>
          <div class="route-stop" style="top:18%; left:72%;">
            <span class="dot"></span> Shed 3 · Layer Unit
          </div>
          <div class="route-stop" style="top:66%; left:22%;">
            <span class="dot"></span> Broiler Shed 5
          </div>
          <div class="route-stop" style="top:76%; left:62%;">
            <span class="dot"></span> Hatchery Block
          </div>
        </div>

        <div style="margin-top:16px;">
          <a class="sfs-btn primary" href="logistics_dashboard_spa.php">← Back to Logistics Orders</a>
        </div>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>
        <div class="footer-brand">SafeFlock Solution</div>
        <div class="muted">Poultry e-commerce, healthcare, and logistics management platform.</div>
      </div>
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
      <?php if ($role === 'logistics'): ?>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item active" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="logistics_route.php" class="nav-item" data-page="route"><div class="icon-container"><svg viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg></div><span class="nav-label">Route</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
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
            try { localStorage.removeItem('safeFlock_session'); } catch (er) {}
            window.location.href = 'auth/logout.php';
            return;
          }
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
  <script src="js/route-guard.js"></script>
</body>
</html>





