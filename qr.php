<?php
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

$token = '';
if (function_exists('sfs_csrf_token')) {
  $token = sfs_csrf_token();
}
$role = '';
if (function_exists('sfs_current_role')) {
  $role = sfs_current_role();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Poultry - Verify QR</title>
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="stylesheet" href="css/doodle.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/loading.css" />
  <style>
    /* Iframe-embedded mode: cleaner layout for provider dashboard */
    .qr-embedded .doodle-header,
    .qr-embedded .web-dock-container {
      display: none !important;
    }
    .qr-embedded .mobile-app-container {
      max-width: 100% !important;
      margin: 0 !important;
      box-shadow: none !important;
      padding-bottom: 15px !important;
      min-height: 100vh !important;
    }
    .qr-embedded .view-section {
      flex: 1 !important;
      display: flex !important;
      flex-direction: column !important;
      gap: 12px !important;
    }
    .qr-embedded .view-section article {
      flex-shrink: 0 !important;
    }
    .qr-embedded #camera-scanner-view {
      flex: 1 1 auto !important;
      min-height: 240px !important;
      max-height: none !important;
    }
    .qr-embedded .modal-backdrop {
      padding: 20px !important;
    }
    .qr-embedded .modal {
      max-width: 100% !important;
      max-height: calc(100vh - 40px) !important;
    }

    @media (max-width: 767px) {
      .qr-embedded .mobile-app-container {
        padding-bottom: 15px !important;
      }
      .qr-embedded #camera-scanner-view {
        min-height: 220px !important;
      }
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
  <?php if ($token): ?>
  <meta name="csrf-token" content="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
  <script>
    (function(){
      var t = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').content;
      if (!t) return;
      var o = window.fetch;
      window.fetch = function(e, n) {
        n = n || {};
        var r = (n.method || 'GET').toUpperCase();
        if (r === 'POST') {
          n.headers = n.headers || {};
          if (!('X-CSRF-Token' in n.headers)) n.headers['X-CSRF-Token'] = t;
        }
        return o(e, n);
      };
    })();
  </script>
  <?php endif; ?>
  <script src="js/route-guard.js"></script>
  <script>window.__USER_ROLE__=<?php echo json_encode($role); ?>;</script>
  <script src="js/theme.js"></script>
  <script>
    (function(){
      try {
        if (window.self !== window.top) {
          document.body.classList.add('qr-embedded');
        }
      } catch(e) {
        document.body.classList.add('qr-embedded');
      }
    })();
  </script>
</head>
<body<?php echo $role ? ' data-role="' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
  <div class="mobile-app-container">
    <header class="doodle-header">
      <h1 class="brand-title">Poultry</h1>
    </header>

    <div class="view-section">
      <article class="doodle-card" style="background-color: rgba(78, 205, 196, 0.05); border-color: var(--secondary-btn);">
        <h2 style="margin: 0 0 8px 0; font-size: 20px; color: var(--text-main);">Verify by QR Code</h2>
        <p style="margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; color: var(--text-muted);">Point your camera at the QR code. The feed will freeze once detected, and we'll verify the token.</p>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 12px;">
          <button id="btnStart" class="doodle-btn" style="font-size: 11px; color: var(--app-bg); background-color: var(--secondary-btn); border-color: var(--secondary-btn);">Start Scanning</button>
          <button id="btnStop" class="doodle-btn doodle-btn-outline" style="font-size: 11px; opacity: 0.7;" disabled>Stop Scanning</button>
          <button id="btnSwitch" class="doodle-btn doodle-btn-outline" style="font-size: 11px;" disabled>Switch Camera</button>
        </div>

        <div style="background-color: var(--bg-color); border-radius: var(--sketch-radius-2); padding: 10px; border: var(--border-width) solid var(--ink);">
          <h3 style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-main);">Status</h3>
          <p id="statusText" style="margin: 0; font-size: 12px; color: var(--text-muted);">Idle. Tap <span style="background: var(--card-bg); padding: 2px 5px; border-radius: 8px; border: var(--border-width) solid var(--ink); font-size: 10px; color: var(--text-main);">Start Scanning</span> to begin.</p>
        </div>

        <div id="camera-scanner-view"></div>
      </article>

      <article class="doodle-card">
        <h2 style="margin: 0 0 8px 0; font-size: 18px; color: var(--text-main);">Verification</h2>
        <p style="margin: 0 0 12px 0; font-size: 12px; line-height: 1.4; color: var(--text-muted);">Token extraction is based on URL query parameter <span style="background: var(--bg-color); padding: 2px 4px; border: var(--border-width) solid var(--ink); border-radius: 4px; font-family: monospace; color: var(--text-main);">token</span>.</p>

        <div style="margin-bottom: 12px;">
          <h3 style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-main);">Extracted token</h3>
          <div class="code-block" id="tokenText">—</div>
        </div>

        <div>
          <h3 style="margin: 0 0 4px 0; font-size: 13px; color: var(--text-main);">Available cameras</h3>
          <ul class="camera-list" id="cameraList">
            <li>OnePlus Nord CE 3 Lite 5G</li>
            <li>Integrated Camera</li>
            <li>OBS Virtual Camera</li>
          </ul>
        </div>
      </article>
    </div>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
      <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
      <a href="cart.php" class="nav-item" data-page="cart"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div><span class="nav-label">Cart</span></a>
      <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
      <a href="qr.php" class="nav-item active" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
      <a href="calculator.php" class="nav-item" data-page="calculator"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div><span class="nav-label">Dosage Calc</span></a>
      <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
      <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
    </nav>
  </div>
  <div class="modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="modal">
      <div class="modal-inner">
        <div class="modal-head">
          <div class="modal-title" id="modalTitle">Verification Result</div>
          <div class="close-x" id="modalClose" aria-label="Close">✕</div>
        </div>
        <div class="modal-body" id="modalBody">
          <span class="spinner" id="modalSpinner" aria-hidden="true"></span>
        </div>

        <div class="modal-body" id="modalDetail" style="margin-top:12px;">
          <div class="mono" id="modalToken">—</div>
          <div class="modal-body" style="margin-top:10px; padding:0; color: var(--text-muted); font-size: 12px;" id="modalDebug"></div>
        </div>

        <div class="modal-actions" id="modalActions">
          <button class="btn-link" id="btnVerifyClose" type="button">Continue Scanning</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/html5-qrcode"></script>
  <script src="js/desktop-dock.js"></script>
  <script>
    const tokenText = document.getElementById('tokenText');
    const cameraList = document.getElementById('cameraList');

    const btnStart = document.getElementById('btnStart');
    const btnStop = document.getElementById('btnStop');
    const btnSwitch = document.getElementById('btnSwitch');

    const modalBackdrop = document.getElementById('modalBackdrop');
    const modalClose = document.getElementById('modalClose');
    const modalTitle = document.getElementById('modalTitle');
    const modalBody = document.getElementById('modalBody');
    const modalToken = document.getElementById('modalToken');
    const modalActions = document.getElementById('modalActions');
    const modalSpinner = document.getElementById('modalSpinner');
    const btnVerifyClose = document.getElementById('btnVerifyClose');
    const flash = document.getElementById('flash');

    let scanner = null;
    let currentFacing = 'environment';
    let active = false;
    let lastToken = null;
    let activeStream = null;
    let freezing = false;

    const sleep = (ms) => new Promise(r => setTimeout(r, ms));

    function setUIState({starting, stopping, switching}={}){
      btnStart.disabled = !!starting;
      btnStop.disabled = !stopping;
      btnSwitch.disabled = !switching;
    }

    function showFlash(){
      flash.classList.remove('show');
      void flash.offsetWidth;
      flash.classList.add('show');
    }

    function showModal({title, body, token, showSpinner=true, actions=null}){
      modalTitle.textContent = title || 'QR Code Detected';
      modalToken.textContent = token || '—';
      modalBody.innerHTML = '';
      if(showSpinner){
        modalBody.appendChild(modalSpinner);
      }
      modalBody.appendChild(document.createTextNode(body || 'Verifying token...'));

      if(actions){
        modalActions.innerHTML = '';
        actions.forEach(a => {
          const b = document.createElement('button');
          b.type = 'button';
          b.textContent = a.label;
          b.className = a.className || 'btn-link';
          b.addEventListener('click', a.onClick, {once:true});
          modalActions.appendChild(b);
        });
      }else{
        modalActions.innerHTML = '';
        modalActions.appendChild(btnVerifyClose);
        btnVerifyClose.onclick = null;
        btnVerifyClose.onclick = () => {
          modalBackdrop.classList.remove('show');
          setTimeout(() => {
            freezing = false;
            if(active && scanner){
              try{
                if(typeof scanner.resume === 'function'){
                  scanner.resume();
                }else if(typeof scanner.start === 'function'){
                  scanner.start();
                }
              }catch(_e){}
            }
          }, 200);
        };
      }

      modalBackdrop.classList.add('show');
    }

    function showResultModal({title, body, token, verification}){
      modalTitle.textContent = title || 'Verification Result';
      modalToken.textContent = token || '—';
      modalBody.innerHTML = '';
      modalActions.innerHTML = '';

      const v = verification || {};
      const productName = v.product || v.name || '';
      const productImage = v.product_image || v.logo_path || '';

      if(productName){
        const card = document.createElement('div');
        card.style.cssText = 'border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-2); padding: 10px; background: var(--bg-color); margin-bottom: 8px;';

        if (productImage) {
          const img = document.createElement('img');
          img.src = productImage;
          img.alt = productName;
          img.style.cssText = 'width: 100%; max-height: 220px; object-fit: contain; border-radius: 10px; margin-bottom: 8px; background: rgba(0,0,0,0.25); border: var(--border-width) solid var(--ink);';
          img.onerror = function () { img.style.display = 'none'; };
          card.appendChild(img);
        }

        const nameEl = document.createElement('div');
        nameEl.textContent = productName;
        nameEl.style.cssText = 'font-weight: 900; font-size: 14px; color: var(--text-main); margin-bottom: 6px;';
        card.appendChild(nameEl);

        if(v.price){
          const priceEl = document.createElement('div');
          priceEl.textContent = 'Price: ' + v.price;
          priceEl.style.cssText = 'font-size: 12px; color: var(--price-color); font-weight: 900; margin-bottom: 4px;';
          card.appendChild(priceEl);
        }

        if(v.category){
          const catEl = document.createElement('div');
          catEl.textContent = 'Category: ' + v.category;
          catEl.style.cssText = 'font-size: 11px; color: var(--text-muted); margin-bottom: 4px;';
          card.appendChild(catEl);
        }

        if(v.description){
          const descEl = document.createElement('div');
          descEl.textContent = v.description;
          descEl.style.cssText = 'font-size: 11px; color: var(--text-muted); line-height: 1.4; margin-top: 4px;';
          card.appendChild(descEl);
        }

        modalBody.appendChild(card);
      }

      if(body){
        const msg = document.createElement('div');
        msg.textContent = body;
        msg.style.cssText = 'font-size: 12px; color: var(--text-muted); margin-top: 4px;';
        modalBody.appendChild(msg);
      }

      modalBackdrop.classList.add('show');
    }

    function hideModal(){
      modalBackdrop.classList.remove('show');
    }

    modalClose.addEventListener('click', hideModal);
    btnVerifyClose.addEventListener('click', hideModal);

    function parseTokenFromPayload(payload){
      const text = payload == null ? '' : String(payload).trim();
      if(!text) return null;

      const cleaned = text.replace(/^\ufeff/, '');

      try{
        const u = new URL(cleaned);
        const t = u.searchParams.get('token');
        return t ? decodeURIComponent(String(t)) : null;
      }catch(_e){}

      const m = cleaned.match(/[?&]token=([^&#]+)/i);
      if(m && m[1]) return decodeURIComponent(m[1]);

      if(cleaned.length >= 3 && !cleaned.includes(' ')) return cleaned;

      return null;
    }

    async function ensureCameraPermission(){
      try{
        const tmp = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
        tmp.getTracks().forEach(t => t.stop());
        return true;
      }catch(err){
        return err;
      }
    }

    async function listCameras(){
      if(!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) return;

      try{
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cams = devices.filter(d => d.kind === 'videoinput');
        if(!cams.length){
          cameraList.textContent = 'No cameras detected.';
          return;
        }
        const lines = cams.map((c, i) => {
          const label = c.label ? c.label : `Camera ${i+1}`;
          return `• ${label}`;
        });
        cameraList.textContent = lines.join('\n');
      }catch(_e){
        // ignore
      }
    }

    async function getFacingConstraint(facing){
      return { facingMode: facing };
    }

    function stopScanner(){
      active = false;

      try{
        if(scanner && typeof scanner.stop === 'function') scanner.stop();
      }catch(_e){}

      if(activeStream){
        activeStream.getTracks().forEach(t => t.stop());
        activeStream = null;
      }

      scanner = null;
      setUIState({starting:false, stopping:false, switching:false});
      statusText.textContent = 'Idle. Tap Start Scanning to begin.';
    }

    async function postVerifyToken(token){
      const url = `api/verify_qr.php?token=${encodeURIComponent(token)}`;

      const res = await fetch(url, { method: 'GET' });
      let data = null;
      try{ data = await res.json(); }catch(_e){ data = null; }

      if(!res.ok){
        const msg = (data && (data.error || data.message)) ? (data.error || data.message) : `HTTP ${res.status}`;
        const e = new Error(msg);
        e.data = data;
        const dbg = document.getElementById('modalDebug');
        if(dbg){
          dbg.textContent = JSON.stringify({ token, requestUrl: url, httpStatus: res.status, response: data }, null, 2);
        }
        throw e;
      }

      return data;
    }

    async function handleDecoded(payload){
      if(!active) return;

      const token = parseTokenFromPayload(payload);
      if(!token){
        statusText.textContent = 'QR detected but token not found in payload.';
        return;
      }

      if(token === lastToken){
        return;
      }

      lastToken = token;
      tokenText.textContent = token;

      try{
        const data = await postVerifyToken(token);
        const ok = !!(data && data.ok);
        const v = data && data.verification ? data.verification : {};
        const productName = v.product || v.name || '';

        showResultModal({
          title: ok ? '✅ Verified' : '⚠️ Verification failed',
          body: ok
            ? `${productName ? productName + ' verified successfully.' : 'QR token verified successfully.'}`
            : 'Verification failed for this QR code.',
          token: token,
          verification: v
        });

        showFlash();
        statusText.textContent = ok ? 'Verified successfully.' : 'Verification failed.';
      }catch(err){
        showResultModal({
          title: '⚠️ Verification error',
          body: 'Could not verify token.',
          token: token
        });
        statusText.textContent = 'Verification error.';
      }
    }

    function startScanner(){
      if(!navigator.mediaDevices || !window.Html5QrcodeScanner){
        statusText.textContent = 'QR scanner library unavailable.';
        return;
      }

      if(scanner && typeof scanner.stop === 'function'){
        try{ scanner.stop(); }catch(_e){}
      }

      setUIState({starting:true, stopping:false, switching:false});

      const facing = currentFacing;
      statusText.textContent = `Requesting camera (${facing === 'environment' ? 'back' : 'front'})...`;

      const view = document.getElementById('camera-scanner-view');
      if(view) view.innerHTML = '';

      const qrbox = Math.max(170, Math.min(260, Math.floor((view ? view.clientWidth : 260) * 0.6 || 220)));

      const constraints = {
        video: {
          facingMode: facing
        }
      };

      try{
        scanner = new Html5QrcodeScanner('camera-scanner-view', {
          qrbox: { width: qrbox, height: qrbox },
          fps: 12,
          rememberLastScan: true,
          mobile: true
        });
      }catch(e){
        scanner = new Html5QrcodeScanner('camera-scanner-view', { qrbox: { width: qrbox, height: qrbox }, fps: 12, rememberLastScan: true });
      }

      try{
        scanner.render((decodedText) => handleDecoded(decodedText));
      }catch(_e){
        try{
          scanner.render();
          scanner.callback = (decodedText) => handleDecoded(decodedText);
        }catch(_e2){
          statusText.textContent = 'Failed to start QR decoding callback.';
        }
      }

      try{
        if(typeof scanner.start === 'function') scanner.start();
      }catch(_e){}

      active = true;
      freezing = false;
      lastToken = null;
      tokenText.textContent = '—';

      setUIState({starting:false, stopping:true, switching:true});
      statusText.textContent = 'Scanning...';

      ensureCameraPermission().then(async (permRes) => {
        if(permRes === true) {
          await listCameras();
        }
      }).catch(()=>{});

      navigator.mediaDevices.getUserMedia(constraints).then(stream => {
        activeStream = stream;
      }).catch(err => {
        if(err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')){
          statusText.textContent = 'Camera access blocked. Allow permission and try again.';
        }
      });
    }

    btnStart.addEventListener('click', async () => {
      lastToken = null;
      tokenText.textContent = '—';

      const perm = await ensureCameraPermission();
      if(perm !== true){
        const err = perm;
        const msg = (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError'))
          ? 'Camera permission blocked. Please enable camera access in browser settings.'
          : 'Unable to access camera. Please check device and permissions.';
        statusText.textContent = msg;
        cameraList.textContent = '—';
        setUIState({starting:false, stopping:false, switching:false});
        showModal({
          title: 'Camera unavailable',
          body: msg,
          token: '—',
          showSpinner: false,
          actions: [{
            label: 'Close',
            className: 'btn-link',
            onClick: () => hideModal()
          }]
        });
        return;
      }

      currentFacing = 'environment';
      await listCameras();
      startScanner();
    });

    btnStop.addEventListener('click', () => {
      stopScanner();
    });

    btnSwitch.addEventListener('click', async () => {
      currentFacing = (currentFacing === 'environment') ? 'user' : 'environment';
      stopScanner();
      await sleep(150);
      startScanner();
    });

    (async () => {
      try{
        if(navigator.mediaDevices && navigator.mediaDevices.getUserMedia){
          await listCameras();
        }
      }catch(_e){}
    })();

    window.addEventListener('beforeunload', () => {
      try{ stopScanner(); }catch(_e){}
    });
  </script>
</body>
</html>
