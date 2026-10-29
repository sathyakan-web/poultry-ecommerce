/* ============================================================
 * Safe Flock Solutions — Client-Side Route Guard
 *
 * Prefers the cached session in localStorage, then falls back
 * to the server-side auth_session API so navigation does not
 * redirect logged-in users back to login.php when the local
 * mirror has not been populated yet.
 * ============================================================ */
(function () {
  'use strict';

  var LS_KEY = 'safeFlock_session';

  var PAGE_ROLES = {
    'index.php':            ['customer', 'provider', 'logistics'],
    'products.php':         ['customer', 'provider'],
    'cart.php':             ['customer', 'provider'],
    'orders.php':           ['customer', 'provider', 'logistics'],
    'order_dispatch.php':   ['provider', 'logistics'],
    'calculator.php':       ['customer', 'provider'],
    'qr.php':               ['customer', 'provider', 'logistics'],
    'inventory.php':        ['customer', 'provider', 'logistics'],
    'print_qr_labels.php':  ['provider'],
    'logistics.php':        ['logistics'],
    'logistics_route.php':  ['logistics'],
    'logistics_hub.php':    ['logistics'],
    'logistics_dashboard_spa.php': ['logistics'],
    'iot.php':              ['customer', 'provider', 'logistics'],
    'past_orders.php':      ['customer', 'provider', 'logistics'],
    'order_details.php':    ['customer', 'provider', 'logistics'],
    'returns.php':          ['customer', 'provider', 'logistics'],
    'verification.php':     ['customer', 'provider', 'logistics'],
    'verified_product.php': ['customer', 'provider', 'logistics']
  };

  function safeGet(key) {
    try { return JSON.parse(localStorage.getItem(key) || 'null'); } catch (e) { return null; }
  }

  function currentPage() {
    var path = window.location.pathname || '';
    var parts = path.split('/');
    return parts.pop() || 'index.php';
  }

  function guard() {
    var page = currentPage();
    var required = PAGE_ROLES[page];
    if (!required) return;

    var session = safeGet(LS_KEY);
    if (session && session.role) {
      if (required.indexOf(session.role) === -1) {
        window.location.href = '403.php';
      }
      return;
    }

    fetch('api/auth_session.php', { credentials: 'same-origin' })
      .then(function (res) {
        if (!res.ok) throw new Error('unauthorized');
        return res.json();
      })
      .then(function (data) {
        if (!data.ok || !data.session) throw new Error('unauthorized');
        var role = data.session.role;
        if (required.indexOf(role) === -1) {
          window.location.href = '403.php';
        }
      })
      .catch(function () {
        window.location.href = 'login.php';
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', guard);
  } else {
    guard();
  }
})();
