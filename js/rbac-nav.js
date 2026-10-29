/* ============================================================
 * Safe Flock Solutions — Role-Based Navigation + Session Mirror
 *
 * Injected into every wrapper page. Responsibilities:
 *  1. Render a role-specific navigation dock (customer / provider /
 *     logistics) at the bottom of every authenticated page.
 *  2. Mirror the server session into localStorage (`safeFlock_session`)
 *     so the SPA and other pages can read { userId, phone, fullName, role }.
 *  3. Show the user's full_name + role badge + logout.
 *  4. Auto-logout (clear mirror + redirect) when the server session dies.
 * ============================================================ */
(function () {
  'use strict';

  // CSRF: attach token to all POST fetch requests
  (function () {
    var token = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!token) return;
    var originalFetch = window.fetch;
    window.fetch = function (url, options) {
      options = options || {};
      var method = (options.method || 'GET').toUpperCase();
      if (method === 'POST') {
        options.headers = options.headers || {};
        if (!('X-CSRF-Token' in options.headers)) {
          options.headers['X-CSRF-Token'] = token;
        }
      }
      return originalFetch(url, options);
    };
  })();

  var LS_KEY = 'safeFlock_session';

  var NAV = {
    customer: [
      { href: 'products.php',        label: 'Products',     icon: 'shopping-bag' },
      { href: 'qr.php',              label: 'Verify QR',    icon: 'scan-line' },
      { href: 'cart.php',            label: 'Cart',         icon: 'shopping-cart' },
      { href: 'orders.php',          label: 'Orders',       icon: 'package' },
      { href: 'calculator.php',      label: 'Dosage Calc',  icon: 'calculator' }
    ],
    provider: [
      { href: 'qr.php',              label: 'Verify QR',    icon: 'scan-line' },
      { href: 'iot.php',             label: 'IoT Monitoring', icon: 'activity' },
      { href: 'inventory.php',       label: 'Inventory',    icon: 'boxes' }
    ],
    logistics: [
      { href: 'orders.php',          label: 'Orders',       icon: 'package' },
      { href: 'logistics_dashboard_spa.php', label: 'Logistics', icon: 'truck' },
      { href: 'inventory.php',       label: 'Stock',        icon: 'boxes' }
    ],
    guest: [
      { href: 'products.php',        label: 'Products',     icon: 'shopping-bag' },
      { href: 'qr.php',              label: 'Verify QR',    icon: 'scan-line' },
      { href: 'cart.php',            label: 'Cart',         icon: 'shopping-cart' },
      { href: 'orders.php',          label: 'Orders',       icon: 'package' },
      { href: 'calculator.php',      label: 'Dosage Calc',  icon: 'calculator' },
      { href: 'iot.php',             label: 'IoT Monitoring', icon: 'activity' },
      { href: 'inventory.php',       label: 'Inventory',    icon: 'boxes' }
    ]
  };

  function currentPage() {
    var parts = (window.location.pathname.split('/').pop() || 'index.php');
    return parts;
  }

  function safeGet(key) {
    try {
      var raw = localStorage.getItem(key);
      if (!raw) return null;
      var s = JSON.parse(raw);
      if (s && s.expires_at && Date.now() >= s.expires_at) {
        safeRemove(key);
        return null;
      }
      return s;
    } catch (e) { return null; }
  }
  function safeSet(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (e) {}
  }
  function safeRemove(key) {
    try { localStorage.removeItem(key); } catch (e) {}
  }

  function mirrorSession(s) {
    safeSet(LS_KEY, Object.assign({}, s, { expires_at: Date.now() + 8 * 60 * 60 * 1000 }));
  }

  /* ---------- render the nav dock ---------- */
  function renderNav(role, fullName, phone) {
    if (document.getElementById('rbac-nav')) return;

    var links = NAV[role] || NAV.customer;
    var page = currentPage();

    var dock = document.createElement('div');
    dock.id = 'rbac-nav';
    dock.setAttribute('data-role', role || '');
    dock.style.cssText = [
      'position:fixed', 'left:50%', 'transform:translateX(-50%)', 'bottom:14px',
      'z-index:2147483000', 'display:flex', 'align-items:center', 'gap:10px',
      'max-width:min(96vw, 860px)', 'padding:10px 14px',
      'border-radius:20px', 'border:1px solid rgba(251,191,36,0.35)',
      'background:rgba(6,8,18,0.88)',
      'backdrop-filter:blur(18px)', '-webkit-backdrop-filter:blur(18px)',
      'box-shadow:0 18px 50px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.05) inset',
      'font-family:"Plus Jakarta Sans","Segoe UI",system-ui,sans-serif'
    ].join(';');

    var wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;gap:6px;flex-wrap:wrap;align-items:center;';

    links.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item.href;
      a.title = item.label;
      a.innerHTML = icn(item.icon) + '<span>' + item.label + '</span>';
      a.style.cssText = [
        'display:flex','align-items:center','gap:6px','text-decoration:none',
        'padding:8px 11px','border-radius:12px','font-size:0.78rem','font-weight:800',
        'color:#e2e8f0','border:1px solid rgba(255,255,255,0.09)',
        'background:rgba(255,255,255,0.04)','transition:all 0.15s ease',
        'letter-spacing:0.2px'
      ].join(';');
      if (page === item.href || (item.href === 'index.php' && page === 'index.html')) {
        a.style.background = 'linear-gradient(135deg,#fbbf24,#f59e0b)';
        a.style.color = '#0c0a00';
        a.style.borderColor = 'rgba(251,191,36,0.7)';
        a.style.boxShadow = '0 8px 22px rgba(251,191,36,0.3)';
      }
      a.addEventListener('mouseenter', function () {
        a.style.background = 'rgba(251,191,36,0.16)';
        a.style.color = '#fbbf24';
      });
      a.addEventListener('mouseleave', function () {
        var isActive = page === item.href;
        a.style.background = isActive ? 'linear-gradient(135deg,#fbbf24,#f59e0b)' : 'rgba(255,255,255,0.04)';
        a.style.color = isActive ? '#0c0a00' : '#e2e8f0';
      });
      wrap.appendChild(a);
    });

    dock.appendChild(wrap);

    /* user chip */
    var chip = document.createElement('div');
    chip.style.cssText = 'display:flex;flex-direction:column;align-items:flex-start;gap:2px;padding:4px 10px;border-left:1px solid rgba(255,255,255,0.12);min-width:0;';
    var nameEl = document.createElement('span');
    nameEl.textContent = fullName || phone || 'User';
    nameEl.style.cssText = 'font-size:0.74rem;font-weight:900;color:#fff;max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;';
    var roleEl = document.createElement('span');
    roleEl.textContent = roleLabel(role);
    roleEl.style.cssText = 'font-size:0.62rem;font-weight:900;letter-spacing:0.8px;text-transform:uppercase;color:#fbbf24;';
    chip.appendChild(nameEl);
    chip.appendChild(roleEl);

    var logout = document.createElement('button');
    logout.type = 'button';
    logout.title = 'Logout';
    logout.innerHTML = icn('log-out');
    logout.style.cssText = [
      'display:flex','align-items:center','justify-content:center',
      'width:38px','height:38px','border-radius:12px','border:1px solid rgba(244,63,94,0.4)',
      'background:rgba(244,63,94,0.12)','color:#fca5a5','cursor:pointer',
      'transition:all 0.15s ease'
    ].join(';');
    logout.addEventListener('mouseenter', function () {
      logout.style.background = 'rgba(244,63,94,0.25)';
    });
    logout.addEventListener('mouseleave', function () {
      logout.style.background = 'rgba(244,63,94,0.12)';
    });
    logout.addEventListener('click', function () {
      fetch('auth/logout.php?json=1', { credentials: 'same-origin' })
        .catch(function () {})
        .then(function () {
          safeRemove(LS_KEY);
          window.location.href = 'login.php';
        });
    });

    dock.appendChild(chip);
    dock.appendChild(logout);
    document.body.appendChild(dock);

    /* keep page content clear of the dock */
    var pad = document.createElement('style');
    pad.id = 'rbac-nav-pad';
    pad.textContent = 'body{padding-bottom:86px !important;} @media (max-width:600px){body{padding-bottom:96px !important;}}';
    document.head.appendChild(pad);
  }

  function roleLabel(role) {
    if (role === 'provider') return 'Provider';
    if (role === 'logistics') return 'Logistics';
    if (role === 'guest') return 'Guest';
    return 'Customer';
  }

  function icn(name) {
    var paths = {
      home: 'M3 10.5 12 3l9 7.5M5 9.5V21h5v-6h4v6h5V9.5',
      'shopping-bag': 'M6 7h12l1 14H5L6 7Z M9 7a3 3 0 0 1 6 0',
      'shopping-cart': 'M2 3h2l2.5 13h11L20 8H6 M10 20a1.5 1.5 0 1 1-3 0M17 20a1.5 1.5 0 1 1-3 0',
      package: 'M12 2 3 7v10l9 5 9-5V7l-9-5Zm0 0v19M3 7l9 5 9-5',
      calculator: 'M5 3h14a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1ZM8 7h8M8 11h.01M12 11h.01M16 11h.01M8 15h.01M12 15h.01M16 15h.01M8 19h.01M12 19h.01M16 19h.01',
      'scan-line': 'M3 7V5a2 2 0 0 1 2-2h2M17 3h2a2 2 0 0 1 2 2v2M21 17v2a2 2 0 0 1-2 2h-2M7 21H5a2 2 0 0 1-2-2v-2M7 12h10',
      activity: 'M22 12h-4l-3 9L9 3l-3 9H2',
      boxes: 'M21 8 12 3 3 8v8l9 5 9-5V8ZM3 8l9 5 9-5M12 13v8',
      'qr-code': 'M3 3h7v7H3V3ZM14 3h7v7h-7V3ZM3 14h7v7H3v-7ZM14 14h3v3h-3v-3ZM21 14h-3v3h3v-3ZM17 17h3v4h-3v-4ZM14 20h-3M17 20v-3',
      truck: 'M1 5h14v12H1V5ZM1 13h14M15 9h4l3 4v4h-7M6 20a2 2 0 1 1 4 0M16 20a2 2 0 1 1 4 0',
      'log-out': 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9',
      plus: 'M12 5v14M5 12h14',
      map: 'M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zM7 9c0-2.76 2.24-5 5-5s5 2.24 5 5'
    };
    var d = paths[name] || paths.home;
    var svg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0;">';
    d.split('M').slice(1).forEach(function (seg, i) {
      svg += '<path d="M' + seg.trim() + '"></path>';
    });
    return svg + '</svg>';
  }

  /* ---------- boot ---------- */
  function bootstrap() {
    if (document.getElementById('rbac-nav')) return;

    var page = currentPage();

    // Authoritative state from the server
    fetch('api/auth_session.php', { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) throw new Error('unauthorized');
        return res.json();
      })
      .then(function (data) {
        if (!data.ok || !data.session) throw new Error('unauthorized');
        var s = data.session;
        mirrorSession(s);
        try {
          renderNav(
            s.role || 'customer',
            s.fullName || '',
            s.phone || ''
          );
        } catch (e) {}
      })
      .catch(function () {
        safeRemove(LS_KEY);
        var onAuthPage = page === 'login.php' || /^auth\//.test(window.location.pathname);
        if (!onAuthPage) {
          window.location.href = 'login.php';
        }
      });
  }

  // Intercept existing logout links so the local mirror is cleared too.
  document.addEventListener('click', function (e) {
    var a = e.target.closest ? e.target.closest('a[href*="logout"]') : null;
    if (!a) return;
    var href = (a.getAttribute('href') || '');
    if (href.indexOf('auth/logout.php') === -1) return;
    if (a.closest('.mobile-dock, .flock-dock, .dock-inner')) return;
    e.preventDefault();
    fetch('auth/logout.php?json=1', { credentials: 'same-origin' })
      .catch(function () {})
      .then(function () {
        safeRemove(LS_KEY);
        window.location.href = 'login.php';
      });
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap);
  } else {
    bootstrap();
  }
})();
