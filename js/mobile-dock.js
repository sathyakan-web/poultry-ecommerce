/* Mobile Bottom Navigation Dock — role-aware on mobile */
(function () {
  'use strict';

  if (window.innerWidth >= 768) return;

  const PILL_ID = 'mobile-dock-pill';
  const CONTAINER_CLASS = 'dock-inner';

  function getCurrentRole() {
    if (window.__USER_ROLE__) return window.__USER_ROLE__;
    if (document.body && document.body.dataset && document.body.dataset.role) return document.body.dataset.role;
    try {
      var raw = localStorage.getItem('safeFlock_session');
      if (raw) {
        var s = JSON.parse(raw);
        if (s && s.role && s.expires_at && Date.now() < s.expires_at) return s.role;
      }
    } catch (e) {}
    return 'customer';
  }

  var ROLE_PAGES = {
    customer: [
      { id: 'home', label: 'Home', href: 'index.php', icon: 'fa-house' },
      { id: 'shop', label: 'Shop', href: 'products.php', icon: 'fa-pills' },
      { id: 'cart', label: 'Cart', href: 'cart.php', icon: 'fa-cart-shopping' },
      { id: 'orders', label: 'Orders', href: 'orders.php', icon: 'fa-box-open' },
      { id: 'verify', label: 'Verify QR', href: 'qr.php', icon: 'fa-qrcode' },
      { id: 'calculator', label: 'Dosage Calc', href: 'calculator.php', icon: 'fa-calculator' },
      { id: 'settings', label: 'Settings', href: 'settings.php', icon: 'fa-gear' },
    ],
    provider: [
      { id: 'home', label: 'Home', href: 'index.php', icon: 'fa-house' },
      { id: 'verify', label: 'Verify QR', href: 'qr.php', icon: 'fa-qrcode' },
      { id: 'orders', label: 'Orders', href: 'order_dispatch.php', icon: 'fa-box-open' },
      { id: 'logistics', label: 'Logistics', href: 'logistics_dashboard_spa.php', icon: 'fa-truck-fast' },
      { id: 'settings', label: 'Settings', href: 'settings.php', icon: 'fa-gear' },
    ],
    logistics: [
      { id: 'home', label: 'Home', href: 'index.php', icon: 'fa-house' },
      { id: 'orders', label: 'Orders', href: 'order_dispatch.php', icon: 'fa-box-open' },
      { id: 'logistics', label: 'Logistics', href: 'logistics_dashboard_spa.php', icon: 'fa-truck-fast' },
      { id: 'settings', label: 'Settings', href: 'settings.php', icon: 'fa-gear' },
    ],
    guest: [
      { id: 'home', label: 'Home', href: 'index.php', icon: 'fa-house' },
      { id: 'shop', label: 'Shop', href: 'products.php', icon: 'fa-pills' },
      { id: 'orders', label: 'Orders', href: 'orders.php', icon: 'fa-box-open' },
      { id: 'verify', label: 'Verify QR', href: 'qr.php', icon: 'fa-qrcode' },
      { id: 'settings', label: 'Settings', href: 'settings.php', icon: 'fa-gear' },
    ]
  };

  var FIXED_PAGES = ROLE_PAGES.customer;

  function getCurrentPageId() {
    const path = window.location.pathname;
    if (path.includes('products.php') || path.includes('products.html')) return 'shop';
    if (path.includes('orders.php') || path.includes('order_details.php') || path.includes('past_orders.php') || path.includes('returns.php') || path.includes('order_dispatch.php')) return 'orders';
    if (path.includes('qr.php') || path.includes('qr.html') || path.includes('print_qr_labels.php')) return 'verify';
    if (path.includes('settings.php')) return 'settings';
    if (path.includes('cart.php')) return 'cart';
    if (path.includes('calculator.php')) return 'calculator';
    if (path.includes('logistics_dashboard_spa.php') || path.includes('logistics_route.php') || path.includes('logistics.php')) return 'logistics';
    return 'home';
  }

  function movePill(btn, immediate) {
    const pill = document.getElementById(PILL_ID);
    if (!pill) return;
    const leftPos = btn.offsetLeft;
    const btnWidth = btn.offsetWidth;
    if (immediate) {
      pill.style.transition = 'none';
    } else {
      pill.style.transition = '';
    }
    pill.style.transform = 'translateX(' + leftPos + 'px)';
    pill.style.width = btnWidth + 'px';
    if (immediate) {
      pill.offsetHeight;
      pill.style.transition = '';
    }
    btn.scrollIntoView({ behavior: immediate ? 'auto' : 'smooth', inline: 'center', block: 'nearest' });
  }

  function setActive(pageId) {
    var container = document.querySelector('.' + CONTAINER_CLASS);
    if (!container) return;
    var buttons = container.querySelectorAll('.dock-btn');
    buttons.forEach(function (btn) {
      if (btn.dataset.page === pageId) {
        btn.classList.add('active');
      } else {
        btn.classList.remove('active');
      }
    });
    var activeBtn = container.querySelector('.dock-btn.active');
    if (activeBtn) {
      movePill(activeBtn, true);
    }
  }

  function buildDock() {
    var container = document.querySelector('.' + CONTAINER_CLASS);
    if (!container) return;

    var currentPage = getCurrentPageId();
    var role = getCurrentRole();
    var pages = ROLE_PAGES[role] || ROLE_PAGES.customer;

    container.innerHTML = '<div id="' + PILL_ID + '"></div>';

    pages.forEach(function (page) {
      var btn = document.createElement('a');
      btn.href = page.href;
      btn.className = 'dock-btn';
      btn.dataset.page = page.id;
      btn.setAttribute('aria-label', page.label);
      btn.innerHTML = '<i class="fa-solid ' + page.icon + '"></i><span>' + page.label + '</span>';

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        movePill(btn, false);
        setTimeout(function () {
          window.location.href = page.href;
        }, 350);
      });

      container.appendChild(btn);
    });

    var logoutBtn = document.createElement('a');
    logoutBtn.href = 'auth/logout.php';
    logoutBtn.className = 'dock-btn dock-btn-logout';
    logoutBtn.dataset.page = 'logout';
    logoutBtn.setAttribute('aria-label', 'Logout');
    logoutBtn.innerHTML = '<i class="fa-solid fa-arrow-right-from-bracket"></i><span>Logout</span>';

    logoutBtn.addEventListener('click', function (e) {
      e.preventDefault();
      movePill(logoutBtn, false);
      try { localStorage.removeItem('safeFlock_session'); } catch (e) {}
      setTimeout(function () {
        window.location.href = 'auth/logout.php';
      }, 150);
    });

    container.appendChild(logoutBtn);

    setActive(currentPage);
  }

  function init() {
    if (!document.querySelector('link[href*="font-awesome"], link[href*="fontawesome"]')) {
      var link = document.createElement('link');
      link.rel = 'stylesheet';
      link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css';
      document.head.appendChild(link);
    }
    buildDock();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
