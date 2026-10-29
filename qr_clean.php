<?php
$embed = !empty($_GET['embed']) && $_GET['embed'] === '1';
if (!$embed) {
  header('Content-Type: text/html; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  header('Expires: 0');
}

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
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no" />
  <title>Verify QR - SafeFlock Solution</title>
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
  <script>window.__USER_ROLE__=<?php echo json_encode($role); ?>;</script>
  <script src="js/theme.js"></script>
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
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body {
      height: 100%;
      overflow: hidden;
      touch-action: manipulation;
    }
    body {
      font-family: "Comic Neue", "Comic Sans MS", sans-serif;
      background: #f4f4f4;
      color: #323232;
      display: flex;
      flex-direction: column;
    }

    .qr-clean-header {
      flex-shrink: 0;
      background: #fff;
      border-bottom: 2px solid #323232;
      padding: 10px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 2px 0 rgba(0,0,0,0.06);
    }
    .qr-clean-header .brand {
      font-family: "Gochi Hand", cursive;
      font-size: 18px;
      font-weight: bold;
      color: #323232;
    }
    .qr-clean-header .badge {
      font-size: 11px;
      font-weight: 900;
      background: #ffb703;
      color: #323232;
      padding: 4px 10px;
      border-radius: 999px;
      border: 2px solid #323232;
      box-shadow: 2px 2px 0 #323232;
    }

    .qr-clean-main {
      flex: 1;
      overflow-y: auto;
      overflow-x: hidden;
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .qr-card {
      background: #fff;
      border: 2px solid #323232;
      border-radius: 16px;
      padding: 14px;
      box-shadow: 4px 4px 0 rgba(0,0,0,0.08);
    }
    .qr-card h2 {
      font-family: "Gochi Hand", cursive;
      font-size: 18px;
      margin-bottom: 6px;
      color: #323232;
    }
    .qr-card p {
      font-size: 13px;
      color: #555;
      line-height: 1.4;
      margin-bottom: 10px;
    }

    .qr-actions {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 10px;
    }
    .qr-btn {
      font-family: "Comic Neue", sans-serif;
      font-weight: 900;
      border: 2px solid #323232;
      border-radius: 14px;
      padding: 10px 14px;
      font-size: 13px;
      cursor: pointer;
      background: #4ecdc4;
      color: #fff;
      box-shadow: 3px 3px 0 #323232;
      transition: all 0.15s ease;
    }
    .qr-btn:active {
      transform: translate(2px, 2px);
      box-shadow: 1px 1px 0 #323232;
    }
    .qr-btn:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none;
      box-shadow: 3px 3px 0 #323232;
    }
    .qr-btn-outline {
      background: #fff;
      color: #323232;
    }

    .qr-status {
      background: #f4f4f4;
      border: 2px solid #323232;
      border-radius: 12px;
      padding: 10px;
      margin-bottom: 10px;
    }
    .qr-status h3 {
      font-size: 12px;
      font-weight: 900;
      color: #323232;
      margin-bottom: 4px;
    }
    .qr-status p {
      font-size: 12px;
      color: #666;
      margin: 0;
    }

    #camera-scanner-view {
      width: 100%;
      min-height: 220px;
      background: #eaeaea;
      border: 2px solid #323232;
      border-radius: 14px;
      overflow: hidden;
    }

    .code-block {
      background: #1e293b;
      color: #e2e8f0;
      padding: 10px;
      border-radius: 10px;
      font-family: monospace;
      font-size: 12px;
      word-break: break-all;
      border: 2px solid #323232;
    }
    .camera-list {
      list-style: none;
      padding: 0;
      margin: 0;
      font-size: 12px;
      color: #444;
    }
    .camera-list li {
      padding: 3px 0;
    }

    .qr-modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      background: rgba(0,0,0,0.45);
    }
    .qr-modal-backdrop.show {
      display: flex;
    }
    .qr-modal {
      width: 100%;
      max-width: 92vw;
      max-height: 82vh;
      overflow-y: auto;
      background: #fffdf5;
      border: 2px solid #323232;
      border-radius: 16px;
      padding: 14px;
      box-shadow: 6px 6px 0 rgba(0,0,0,0.15);
    }
    .qr-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px dashed rgba(0,0,0,0.15);
      padding-bottom: 8px;
      margin-bottom: 10px;
    }
    .qr-modal-title {
      font-family: "Gochi Hand", cursive;
      font-size: 18px;
      font-weight: bold;
      color: #323232;
    }
    .qr-modal-close {
      width: 32px;
      height: 32px;
      border: 2px solid #323232;
      border-radius: 10px;
      background: #fff;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      box-shadow: 2px 2px 0 #323232;
    }
    .qr-modal-close:active {
      transform: translate(1px, 1px);
      box-shadow: 1px 1px 0 #323232;
    }
    .qr-modal-body {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .qr-modal-actions {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-top: 4px;
    }
    .qr-btn-link {
      background: transparent;
      color: #323232;
      border: 2px solid #323232;
      box-shadow: 2px 2px 0 #323232;
      text-align: center;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    @media (max-width: 767px) {
      .qr-clean-main { padding: 10px; gap: 8px; }
      #camera-scanner-view { min-height: 200px; }
      .qr-modal { max-width: 96vw; max-height: 86vh; padding: 10px; }
    }
  </style>
</head>
<body class="theme-bng"<?php echo $role ? ' data-role="' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
   <div class="qr-clean-main">
    <div class="qr-card">
      <h2>Scan QR Code</h2>
      <p>Point your camera at a product QR code to verify authenticity and check inventory.</p>
      <div class="qr-actions">
        <button id="btnStart" class="qr-btn">Start Scanning</button>
        <button id="btnStop" class="qr-btn qr-btn-outline" disabled>Stop Scanning</button>
        <button id="btnSwitch" class="qr-btn qr-btn-outline" disabled>Switch Camera</button>
      </div>
      <div class="qr-status">
        <h3>Status</h3>
        <p id="statusText">Idle. Tap Start Scanning to begin.</p>
      </div>
      <div id="camera-scanner-view"></div>
    </div>

    <div class="qr-card">
      <h2>Verification</h2>
      <p style="margin-bottom:10px;">Token extraction is based on URL query parameter <span style="background:#eaeaea;padding:2px 5px;border-radius:6px;border:2px solid #323232;font-family:monospace;font-size:11px;">token</span>.</p>
      <div style="margin-bottom:10px;">
        <h3 style="font-size:12px;font-weight:900;color:#323232;margin-bottom:4px;">Extracted token</h3>
        <div class="code-block" id="tokenText">—</div>
      </div>
      <div>
        <h3 style="font-size:12px;font-weight:900;color:#323232;margin-bottom:4px;">Available cameras</h3>
        <ul class="camera-list" id="cameraList">
          <li>Detecting cameras...</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="qr-modal-backdrop" id="modalBackdrop" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="qr-modal">
      <div class="qr-modal-header">
        <div class="qr-modal-title" id="modalTitle">Verification Result</div>
        <button class="qr-modal-close" id="modalClose" aria-label="Close">✕</button>
      </div>
      <div class="qr-modal-body" id="modalBody">
        <span class="spinner" id="modalSpinner" aria-hidden="true"></span>
      </div>
      <div class="qr-modal-body" id="modalDetail" style="margin-top:10px;padding:0;color:#666;font-size:12px;">
        <div class="code-block" id="modalToken" style="margin-bottom:8px;">—</div>
        <div id="modalDebug" style="font-size:11px;color:#888;"></div>
      </div>
      <div class="qr-modal-actions" id="modalActions">
        <button class="qr-btn qr-btn-link" id="btnVerifyClose" type="button">Continue Scanning</button>
      </div>
    </div>
  </div>

  <script src="https://unpkg.com/html5-qrcode"></script>
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
    const statusText = document.getElementById('statusText');

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
      const flash = document.getElementById('flash');
      if (!flash) return;
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
          b.className = a.className || 'qr-btn qr-btn-link';
          b.addEventListener('click', a.onClick, {once:true});
          modalActions.appendChild(b);
        });
      }else{
        modalActions.innerHTML = '';
        const btn = document.createElement('button');
        btn.className = 'qr-btn qr-btn-link';
        btn.type = 'button';
        btn.textContent = 'Continue Scanning';
        btn.onclick = () => {
          modalBackdrop.classList.remove('show');
          setTimeout(() => {
            freezing = false;
            if(active && scanner){
              try{
                if(typeof scanner.resume === 'function') scanner.resume();
                else if(typeof scanner.start === 'function') scanner.start();
              }catch(_e){}
            }
          }, 200);
        };
        modalActions.appendChild(btn);
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
        card.style.cssText = 'border:2px solid #323232;border-radius:14px;padding:10px;background:#f4f4f4;margin-bottom:8px;';

        if (productImage) {
          const img = document.createElement('img');
          img.src = productImage;
          img.alt = productName;
          img.style.cssText = 'width:100%;max-height:220px;object-fit:contain;border-radius:10px;margin-bottom:8px;background:rgba(0,0,0,0.25);border:2px solid #323232;';
          img.onerror = function () { img.style.display = 'none'; };
          card.appendChild(img);
        }

        const nameEl = document.createElement('div');
        nameEl.textContent = productName;
        nameEl.style.cssText = 'font-weight:900;font-size:14px;color:#323232;margin-bottom:6px;';
        card.appendChild(nameEl);

        if(v.price){
          const priceEl = document.createElement('div');
          priceEl.textContent = 'Price: ' + v.price;
          priceEl.style.cssText = 'font-size:12px;color:#d4af37;font-weight:900;margin-bottom:4px;';
          card.appendChild(priceEl);
        }

        if(v.category){
          const catEl = document.createElement('div');
          catEl.textContent = 'Category: ' + v.category;
          catEl.style.cssText = 'font-size:11px;color:#666;margin-bottom:4px;';
          card.appendChild(catEl);
        }

        if(v.description){
          const descEl = document.createElement('div');
          descEl.textContent = v.description;
          descEl.style.cssText = 'font-size:11px;color:#666;line-height:1.4;margin-top:4px;';
          card.appendChild(descEl);
        }

        modalBody.appendChild(card);
      }

      if(body){
        const msg = document.createElement('div');
        msg.textContent = body;
        msg.style.cssText = 'font-size:12px;color:#666;margin-top:4px;';
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
          cameraList.innerHTML = '<li>No cameras detected.</li>';
          return;
        }
        const lines = cams.map((c, i) => {
          const label = c.label ? c.label : `Camera ${i+1}`;
          return `<li>• ${label}</li>`;
        });
        cameraList.innerHTML = lines.join('');
      }catch(_e){
        cameraList.innerHTML = '<li>Camera list unavailable.</li>';
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
        if(dbg) dbg.textContent = JSON.stringify({ token, requestUrl: url, httpStatus: res.status, response: data }, null, 2);
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
      if(token === lastToken) return;
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

      const constraints = { video: { facingMode: facing } };

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
        cameraList.innerHTML = '<li>—</li>';
        setUIState({starting:false, stopping:false, switching:false});
        showModal({
          title: 'Camera unavailable',
          body: msg,
          token: '—',
          showSpinner: false,
          actions: [{
            label: 'Close',
            className: 'qr-btn qr-btn-link',
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
