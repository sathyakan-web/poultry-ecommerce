<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

require_once __DIR__ . '/auth/csrf.php';

// NOTE: intentionally NOT auto-redirecting logged-in users here.
// The landing page should always land on role selection first.
// After successful auth, role-specific redirects still apply in JS/PHP below.

$login_error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

$register_error = $_SESSION['register_error'] ?? '';
unset($_SESSION['register_error']);

$register_success = $_SESSION['register_success'] ?? '';
unset($_SESSION['register_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>Safe Flock Solutions | Login Portal</title>
  
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  
  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700;900&family=Gochi+Hand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  
  <style>
    :root {
      --ink: #2b2b2b;
      --paper: #ffffff;
      --paper-alt: #fdfbf7;
      --bg-body: #e9ecef;
      --primary: #ffe66d;
      --secondary: #4ecdc4;
      --accent: #ff6b6b;
      --logistic: #9b5de5;
      --guest: #06d6a0;
      --border-width: 3px;
      --shadow-offset: 6px;
      --sketch-r1: 8px 24px 8px 24px / 24px 8px 24px 8px;
      --sketch-r2: 24px 8px 24px 8px / 8px 24px 8px 24px;
      --sketch-btn: 12px 18px 12px 18px / 18px 12px 18px 12px;
      --font-ui: 'Comic Neue', 'Comic Sans MS', sans-serif;
      --font-display: 'Gochi Hand', 'Marker Felt', cursive;
    }
    body.dark-theme {
      --ink: #f0f0f0;
      --paper: #1a1a2e;
      --paper-alt: #16213e;
      --bg-body: #0f172a;
    }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: var(--font-ui);
      background-color: var(--bg-body);
      color: var(--ink);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      align-items: center;
      padding-top: 30px;
      overflow-x: hidden;
      transition: background-color 0.4s ease, color 0.4s ease;
      background-image: repeating-linear-gradient(transparent, transparent 38px, rgba(0,0,0,0.04) 38px, rgba(0,0,0,0.04) 40px);
      background-position: 0 10px;
    }
    body.dark-theme {
      background-image: repeating-linear-gradient(transparent, transparent 38px, rgba(255,255,255,0.03) 38px, rgba(255,255,255,0.03) 40px);
    }
    h1, h2, h3, .font-display { font-family: var(--font-display); }

    @keyframes slide-in-left {
      0% { opacity: 0; transform: translateX(-50px); }
      100% { opacity: 1; transform: translateX(0); }
    }
    @keyframes slide-in-right {
      0% { opacity: 0; transform: translateX(50px); }
      100% { opacity: 1; transform: translateX(0); }
    }
    @keyframes pop-text {
      0% { transform: scale(0.95); opacity: 0; }
      50% { transform: scale(1.02); }
      100% { transform: scale(1); opacity: 1; }
    }
    @keyframes float-bob {
      0%, 100% { transform: translateY(0) rotate(0deg); }
      50% { transform: translateY(-10px) rotate(5deg); }
    }

    .bg-doodle { position: absolute; z-index: 0; pointer-events: none; opacity: 0.08; stroke: var(--ink); stroke-width: 2; fill: none; }
    .bg-doodle.d1 { top: 10%; left: 5%; width: 80px; animation: float-bob 6s infinite ease-in-out; }
    .bg-doodle.d2 { bottom: 10%; right: 5%; width: 120px; animation: float-bob 8s infinite ease-in-out reverse; }

    .top-header {
      width: 100%;
      max-width: 1200px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 30px;
      margin-bottom: 15px;
      z-index: 100;
      animation: slide-in-left 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    .theme-toggle-btn {
      position: relative;
      background: var(--paper); border: var(--border-width) solid var(--ink); border-radius: 30px;
      padding: 5px; display: flex; align-items: center; cursor: pointer; width: 64px; height: 34px;
      transition: background 0.35s ease, box-shadow 0.35s ease;
      box-shadow: 3px 3px 0px var(--ink);
    }
    .theme-knob {
      width: 20px; height: 20px; background: var(--primary); border: 2px solid var(--ink); border-radius: 50%;
      position: absolute; left: 4px; top: 50%; transform: translateY(-50%);
      transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), background 0.35s ease;
    }
    body.dark-theme .theme-toggle-btn { background: var(--paper-alt); box-shadow: 3px 3px 0px var(--ink); }
    body.dark-theme .theme-knob { transform: translateY(-50%) translateX(30px); background: var(--secondary); }

    .split-layout {
      display: flex;
      width: 100%;
      max-width: 1200px;
      height: 680px;
      gap: 40px;
      padding: 20px;
      position: relative;
      z-index: 10;
    }

    .left-panel {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: flex-start;
      padding-top: 20px;
      animation: slide-in-left 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    .brand-wrapper {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .brand-logo {
      width: 56px;
      height: 56px;
      object-fit: contain;
      flex-shrink: 0;
    }
    .brand-text {
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 2px;
    }
    .brand-title {
      font-size: 26px;
      line-height: 1.1;
      color: var(--ink);
      font-weight: 900;
      letter-spacing: 0.3px;
    }
    .brand-sub {
      font-family: var(--font-ui);
      font-size: 12px;
      font-weight: 900;
      color: var(--accent);
      letter-spacing: 1.8px;
      text-transform: uppercase;
    }
    .role-selector-title { font-size: 20px; font-weight: bold; color: #666; margin-bottom: 15px; }
    .role-stack { display: flex; flex-direction: column; gap: 15px; }
    
    .role-ticket {
      background: var(--paper); border: var(--border-width) solid var(--ink);
      border-radius: 12px 25px 12px 25px; padding: 16px 25px;
      display: flex; align-items: center; gap: 15px; cursor: pointer;
      box-shadow: 4px 4px 0px rgba(0,0,0,0.1); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative; transform-origin: left center; overflow: hidden;
    }
    .role-ticket:hover { transform: scale(1.02) translateX(10px); box-shadow: 6px 6px 0px rgba(0,0,0,0.15); }
    .role-ticket.active {
      background: var(--primary); transform: scale(1.05) translateX(15px);
      box-shadow: 6px 6px 0px var(--ink); z-index: 5; border-radius: 25px 12px 25px 12px;
    }
    .role-ticket.active[data-role="Customer"] { background: var(--primary); }
    .role-ticket.active[data-role="Provider"] { background: var(--secondary); color: var(--ink);}
    .role-ticket.active[data-role="Logistic"] { background: var(--logistic); color: white; border-color: var(--ink); }
    .role-ticket.active[data-role="Logistic"] svg { stroke: white; }
    .role-ticket.active[data-role="Guest"] { background: var(--guest); }

    .role-icon { width: 28px; height: 28px; stroke: var(--ink); stroke-width: 2.5; fill: none; stroke-linecap: round; stroke-linejoin: round; transition: stroke 0.3s; }
    .role-name { font-family: var(--font-display); font-size: 22px; font-weight: bold; flex-grow: 1; transition: color 0.3s;}
    .role-arrow { font-size: 20px; font-weight: bold; opacity: 0; transition: opacity 0.3s, transform 0.3s; transform: translateX(-10px); }
    .role-ticket.active .role-arrow { opacity: 1; transform: translateX(0); }

    .right-panel {
      flex: 1.2;
      background: var(--paper);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-r1);
      box-shadow: 12px 12px 0px var(--ink);
      position: relative;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      animation: slide-in-right 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    .washi-tape {
      position: absolute; top: -15px; right: 40px; width: 120px; height: 35px;
      background: rgba(78, 205, 196, 0.4); border: 1.5px dashed var(--ink);
      transform: rotate(4deg); z-index: 20;
    }

    .auth-toggle-wrapper {
      padding: 30px 40px 10px 40px;
      display: flex; justify-content: center;
    }
    .auth-toggle-box {
      display: flex; position: relative; background: var(--bg-body);
      border: 2.5px solid var(--ink); border-radius: 40px; padding: 5px; width: 100%; max-width: 350px;
      box-shadow: inset 2px 2px 5px rgba(0,0,0,0.05); cursor: pointer;
    }
    .toggle-pill {
      position: absolute; top: 5px; bottom: 5px; left: 5px; width: calc(50% - 5px);
      background: var(--ink); border-radius: 30px; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .auth-toggle-box.is-register .toggle-pill { transform: translateX(100%); }
    
    .toggle-label {
      flex: 1; text-align: center; padding: 10px 0; font-family: var(--font-display); font-size: 20px;
      font-weight: bold; color: var(--ink); position: relative; z-index: 2; transition: color 0.3s;
    }
    .toggle-label.active { color: var(--paper); }

    .dynamic-header { padding: 20px 40px; text-align: center; border-bottom: 2px dashed rgba(0,0,0,0.1); min-height: 110px; }
    .portal-title { font-size: 32px; color: var(--ink); line-height: 1; margin-bottom: 8px; }
    .portal-desc { font-size: 14px; font-weight: bold; color: #666; }

    .slider-window { flex-grow: 1; overflow: hidden; position: relative; }
    .slider-track { display: flex; width: 200%; height: 100%; transition: transform 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .is-register .slider-track { transform: translateX(-50%); }

    .form-pane { width: 50%; padding: 20px 40px; display: flex; flex-direction: column; justify-content: center; }

    .form-group { display: flex; flex-direction: column; gap: 8px; margin-bottom: 15px; }
    .form-label { font-size: 14px; font-weight: 900; color: var(--ink); }
    .doodle-input {
      width: 100%; padding: 14px 18px; font-family: var(--font-ui); font-size: 16px; font-weight: bold;
      color: var(--ink); background-color: var(--paper-alt); border: 2.5px solid var(--ink);
      border-radius: 12px; outline: none; transition: 0.2s; box-shadow: 2px 2px 0px rgba(0,0,0,0.05);
    }
    .doodle-input:focus { border-color: var(--secondary); background-color: var(--paper); transform: translateY(-2px); box-shadow: 4px 4px 0px var(--secondary); }
    .doodle-input.error { border-color: var(--accent); box-shadow: 4px 4px 0px var(--accent); animation: shake 0.4s; }

    @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }
    
    .error-text { color: var(--accent); font-size: 12px; font-weight: bold; display: none; margin-top: -5px; }

    .doodle-btn {
      width: 100%; font-family: var(--font-display); font-size: 22px; padding: 14px;
      color: var(--ink); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-btn);
      cursor: pointer; transition: all 0.15s ease; box-shadow: 4px 4px 0px var(--ink);
      display: flex; align-items: center; justify-content: center; gap: 10px; letter-spacing: 1px;
    }
    .doodle-btn:active { transform: translate(4px, 4px); box-shadow: 0px 0px 0px transparent; }
    .btn-red { background: var(--accent); color: white; }
    .btn-teal { background: var(--secondary); font-size: 18px; padding: 10px;}
    .btn-yellow { background: var(--primary); font-size: 18px; padding: 10px;}

    .divider {
      display: flex; align-items: center; text-align: center; color: #888; font-weight: 900; font-size: 12px; letter-spacing: 2px; margin: 20px 0; text-transform: uppercase;
    }
    .divider::before, .divider::after { content: ''; flex: 1; border-bottom: 2px dashed rgba(0,0,0,0.15); }
    .divider::before { margin-right: 15px; } .divider::after { margin-left: 15px; }

    .quick-auth-row { display: flex; gap: 15px; }

    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px);
      display: flex; justify-content: center; align-items: center; z-index: 1000;
      opacity: 0; pointer-events: none; transition: opacity 0.3s ease; padding: 20px;
    }
    .modal-overlay.active { opacity: 1; pointer-events: all; }

    .modal-card {
      background: var(--paper); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-r2);
      padding: 40px 30px; width: 100%; max-width: 450px; box-shadow: 8px 8px 0px var(--ink);
      transform: translateY(30px) scale(0.95); transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); position: relative; text-align: center;
    }
    .modal-overlay.active .modal-card { transform: translateY(0) scale(1); }

    .close-btn {
      position: absolute; top: 15px; right: 20px; font-family: var(--font-display); font-size: 32px; font-weight: bold; cursor: pointer; color: var(--ink); line-height: 1; transition: transform 0.2s; background: transparent; border: none;
    }
    .close-btn:hover { transform: scale(1.2) rotate(10deg); color: var(--accent); }

    .otp-inputs { display: flex; gap: 10px; justify-content: center; margin: 25px 0; }
    .otp-box {
      width: 50px; height: 60px; font-family: var(--font-display); font-size: 32px; text-align: center;
      border: 2.5px solid var(--ink); border-radius: 12px; background: var(--bg-body); color: var(--ink);
      box-shadow: 2px 2px 0px var(--ink); outline: none; transition: 0.2s;
    }
    .otp-box:focus { border-color: var(--secondary); box-shadow: 4px 4px 0px var(--secondary); transform: translateY(-4px); background: var(--paper); }

    .passkey-icon {
      width: 80px; height: 80px; background: var(--secondary); border: 3px solid var(--ink); border-radius: 25px;
      display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; box-shadow: 4px 4px 0px var(--ink); animation: float-bob 3s ease-in-out infinite;
    }

    .toast {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px);
      background: var(--ink); color: var(--paper); padding: 12px 25px; border-radius: 20px;
      font-family: var(--font-display); font-size: 20px; font-weight: bold; z-index: 2000;
      box-shadow: 4px 4px 0px var(--secondary); transition: 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      display: flex; align-items: center; gap: 10px;
    }
    .toast.show { transform: translateX(-50%) translateY(0) rotate(-1deg); }

    .php-error {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 3000;
      padding: 12px 20px; border-radius: 12px; font-weight: 900; font-size: 13px;
      border: 2px solid var(--accent); background: rgba(255,107,107,0.95); color: white;
      box-shadow: 4px 4px 0px var(--ink); animation: slide-in-left 0.4s ease forwards;
    }
    .php-success {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 3000;
      padding: 12px 20px; border-radius: 12px; font-weight: 900; font-size: 13px;
      border: 2px solid var(--secondary); background: rgba(78,205,196,0.95); color: white;
      box-shadow: 4px 4px 0px var(--ink); animation: slide-in-left 0.4s ease forwards;
    }

    .hidden { display: none !important; }

    .passcode-modal-card {
      position: relative;
      background: var(--paper);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-r1);
      box-shadow: 8px 8px 0px var(--ink);
      padding: 22px 18px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .passcode-close-btn {
      position: absolute;
      top: 15px;
      right: 20px;
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: 2px solid #1e293b;
      background: #ff6b6b;
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      box-shadow: 2px 2px 0px #1e293b;
      cursor: pointer;
      transition: transform 0.2s;
    }
    .passcode-close-btn:hover {
      transform: translate(1px, 1px);
      box-shadow: 1px 1px 0px #1e293b;
    }

    .passcode-utility-row { display: flex; align-items: center; gap: 12px; }
    .passcode-utility-icon {
      width: 44px; height: 44px; border-radius: 50%;
      border: 2px solid #1e293b; display: flex; align-items: center; justify-content: center;
      box-shadow: 2px 2px 0px #1e293b; flex-shrink: 0;
    }
    .passcode-utility-title { font-family: var(--font-display); text-transform: uppercase; letter-spacing: 0.05em; color: #1e293b; }
    .passcode-utility-desc { color: #1e293b; opacity: 0.75; }

    @media (max-width: 900px) {
      .split-layout { flex-direction: column; height: auto; align-items: center; }
      .left-panel { width: 100%; text-align: center; padding-top: 0; }
      .role-stack { flex-direction: row; flex-wrap: wrap; justify-content: center; }
      .role-ticket { flex: 1; min-width: 150px; justify-content: center; }
      .right-panel { width: 100%; }
    }
  </style>
</head>
<body>

  <svg class="bg-doodle d1" viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" /></svg>
  <svg class="bg-doodle d2" viewBox="0 0 24 24"><path d="M3 12 C 3 5 10 5 16 5 C 20 5 21 9 18 12 C 15 15 10 13 12 9 C 14 5 22 9 21 16" /></svg>

  <div id="toast" class="toast"><span id="toastIcon">✨</span> <span id="toastMsg">Message</span></div>

  <header class="top-header">
    <div class="brand-wrapper">
      <img src="images/sfs_icon-152x152.png" alt="SafeFlock Solution" class="brand-logo" onerror="this.style.display='none'" />
      <div class="brand-text">
        <h1 class="brand-title">SafeFlock Solution</h1>
        <div class="brand-sub">Poultry Ecommerce</div>
      </div>
    </div>
    <div class="theme-toggle-btn" onclick="toggleTheme()" title="Toggle Dark Mode">
      <div class="theme-knob"></div>
    </div>
  </header>

  <main class="split-layout">
    
    <div class="left-panel">
      <h3 class="role-selector-title font-ui">Choose your access level:</h3>
      <div class="role-stack">
        <div class="role-ticket active" data-role="Customer" onclick="selectRole('Customer')">
          <svg class="role-icon" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          <span class="role-name">Customer</span>
          <span class="role-arrow">→</span>
        </div>
        <div class="role-ticket" data-role="Provider" onclick="selectRole('Provider')">
          <svg class="role-icon" viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9L12 2 3 9z"></path></svg>
          <span class="role-name">Provider</span>
          <span class="role-arrow">→</span>
        </div>
        <div class="role-ticket" data-role="Logistic" onclick="selectRole('Logistic')">
          <svg class="role-icon" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
          <span class="role-name">Logistics</span>
          <span class="role-arrow">→</span>
        </div>
        <div class="role-ticket" data-role="Guest" onclick="selectRole('Guest')">
          <svg class="role-icon" viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
          <span class="role-name">Guest</span>
          <span class="role-arrow">→</span>
        </div>
      </div>
    </div>

    <div class="right-panel" id="auth-panel-container">
      <div class="washi-tape"></div>

      <div class="auth-toggle-wrapper">
        <div class="auth-toggle-box" onclick="toggleAuthMode()" id="auth-toggle-box">
          <div class="toggle-pill"></div>
          <div class="toggle-label active" id="label-signin">Sign In</div>
          <div class="toggle-label" id="label-register">Register</div>
        </div>
      </div>

      <div class="dynamic-header" id="dynamic-header">
        <h2 class="portal-title font-display" id="portal-title">CUSTOMER PORTAL</h2>
        <p class="portal-desc" id="portal-desc">Order premium poultry supplies, track deliveries, and manage your flock.</p>
      </div>

      <div class="slider-window" id="slider-window">
        <div class="slider-track" id="slider-track">
          
          <div class="form-pane">
            <form onsubmit="event.preventDefault(); requestOTP();">
              <div class="form-group">
                <label class="form-label">Mobile Number (10 digits)</label>
                <input type="tel" id="signin-phone" class="doodle-input" placeholder="e.g. 9876543210" maxlength="10" autocomplete="off">
                <span class="error-text" id="err-signin-phone">Enter a valid 10-digit number.</span>
              </div>
              <button type="submit" class="doodle-btn btn-red" style="margin-top: 10px;">Request OTP</button>
            </form>
            
            <div class="divider">OR QUICK AUTH</div>
            
            <div class="quick-auth-row">
              <button type="button" class="doodle-btn btn-teal" onclick="triggerBiometricScan()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><circle cx="12" cy="11" r="3"/></svg> 
                Passkey
              </button>
              <button type="button" class="doodle-btn btn-yellow" onclick="openPasscodeForm()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg> 
                Passcode
              </button>
            </div>
            <div id="passkeyStatus" style="min-height:20px; margin-top:10px; font-size:12px; font-weight:bold; text-align:center;"></div>
          </div>

          <div class="form-pane">
            <form id="registerForm" method="POST" action="auth/register.php" autocomplete="off" onsubmit="event.preventDefault(); validateRegister();">
              <input type="hidden" name="csrf_token" value="<?php echo sfs_csrf_token(); ?>" />
              <input type="hidden" id="registerRole" name="role" value="customer" />
              <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">Full Name / Farm Name</label>
                  <input type="text" id="reg-name" name="full_name" class="doodle-input" placeholder="John Doe" required>
                  <span class="error-text" id="err-reg-name">Required.</span>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">Mobile Number</label>
                  <input type="tel" id="reg-phone" name="phone" class="doodle-input" placeholder="9876543210" maxlength="10" required>
                  <span class="error-text" id="err-reg-phone">10 digits required.</span>
                </div>
              </div>
              <div style="display: flex; gap: 15px;">
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">Set Passcode</label>
                  <input type="password" id="reg-pass" name="password" class="doodle-input" placeholder="Min 4 chars" required autocomplete="new-password">
                  <span class="error-text" id="err-reg-pass">Too short.</span>
                </div>
                <div class="form-group" style="flex: 1;">
                  <label class="form-label">Confirm Passcode</label>
                  <input type="password" id="reg-confirm" name="confirm_password" class="doodle-input" placeholder="Repeat" required autocomplete="new-password">
                  <span class="error-text" id="err-reg-confirm">No match.</span>
                </div>
              </div>
              <button type="submit" class="doodle-btn btn-teal" style="margin-top: 15px;">Create Secure Account</button>
            </form>
          </div>

        </div>
      </div>
    </div>

  </main>

  <?php if ($login_error): ?>
    <div class="php-error"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($register_error): ?>
    <div class="php-error"><?php echo htmlspecialchars($register_error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>
  <?php if ($register_success): ?>
    <div class="php-success"><?php echo htmlspecialchars($register_success, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endif; ?>

  <div class="modal-overlay" id="otp-modal" onclick="closeModal(event, 'otp-modal')">
    <div class="modal-card" onclick="event.stopPropagation()">
      <button class="close-btn" onclick="closeModal(null, 'otp-modal')">&times;</button>
      <h2 class="font-display" style="font-size: 32px; margin-bottom: 8px; color: var(--ink);">Verify Mobile</h2>
      <p style="font-weight: bold; color: #666;">We sent a 6-digit code to<br><span id="display-phone" style="color: var(--ink); font-weight: 900;"></span></p>
      <div class="otp-inputs">
        <input type="tel" maxlength="1" class="otp-box">
        <input type="tel" maxlength="1" class="otp-box">
        <input type="tel" maxlength="1" class="otp-box">
        <input type="tel" maxlength="1" class="otp-box">
        <input type="tel" maxlength="1" class="otp-box">
        <input type="tel" maxlength="1" class="otp-box">
      </div>
      <p id="otpTimerDisplay" style="font-size: 12px; font-weight: bold; color: #666; margin-bottom: 10px;"></p>
      <button class="doodle-btn btn-red" style="margin-top: 10px;" onclick="verifyOTP()">Verify & Secure Login</button>
    </div>
  </div>

  <div id="passcodeModal" class="hidden" style="position:fixed; inset:0; z-index:9999; display:flex; align-items:center; justify-content:center; padding:16px;">
    <div style="position:absolute; inset:0; background:rgba(0,0,0,0.6); backdrop-filter:blur(4px);" onclick="closePasscodeForm()"></div>
    <div style="position:relative; z-index:10; width:100%; max-width:320px;">
      <div class="passcode-modal-card">
        <button type="button" onclick="closePasscodeForm()" class="passcode-close-btn">
          <i class="fa-solid fa-xmark" style="font-size:14px;"></i>
        </button>
        <div class="passcode-utility-row" style="gap:12px; margin-bottom:20px; margin-top:4px; padding-right:32px;">
          <div class="passcode-utility-icon" style="background:#ffe66d;">
            <i class="fa-solid fa-lock" style="font-size:14px; color:#1e293b;"></i>
          </div>
          <div>
            <h3 class="passcode-utility-title" style="font-family: var(--font-display);">Passcode Login</h3>
            <p class="passcode-utility-desc" style="font-size:11px; font-weight:bold; margin-top:2px;">Secure SafeFlock Access</p>
          </div>
        </div>
        <form method="POST" action="auth/login.php" onsubmit="event.preventDefault(); submitPasscode();">
          <input type="hidden" name="csrf_token" value="<?php echo sfs_csrf_token(); ?>" />
          <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:10px;">
            <label style="font-size:12px; font-weight:900; color:#1e293b; text-transform:uppercase; letter-spacing:0.05em;">Account ID / Mobile</label>
            <input class="doodle-input" id="backupPhone" name="phone" placeholder="Enter ID or Mobile number" type="text" required autocomplete="off" />
          </div>
          <div style="display:flex; flex-direction:column; gap:6px; margin-bottom:10px;">
            <label style="font-size:12px; font-weight:900; color:#1e293b; text-transform:uppercase; letter-spacing:0.05em;">Security Passcode</label>
            <input class="doodle-input" id="backupPass" name="password" placeholder="Enter security passcode" type="password" required autocomplete="off" />
          </div>
          <div id="passcodeStatus" style="min-height:18px; text-align:center; font-size:12px; font-weight:900; margin-bottom:8px;"></div>
          <button type="submit" class="doodle-btn" style="margin-top:10px;">
            <i class="fa-solid fa-lock-open"></i><span>Authenticate Account</span>
          </button>
          <button type="button" onclick="closePasscodeForm()" style="width:100%; text-align:center; font-size:12px; font-weight:900; opacity:0.7; padding:6px 0; cursor:pointer; color:#1e293b; background:transparent; border:none;">
            Cancel & Return
          </button>
        </form>
      </div>
    </div>
  </div>

  <script>
    const roles = {
      Customer: { title: "CUSTOMER PORTAL", desc: "Order premium poultry supplies, track deliveries, and manage your flock." },
      Provider: { title: "PROVIDER PORTAL", desc: "Manage incoming orders, authorize logistics dispatch, and monitor inventory." },
      Logistic: { title: "LOGISTICS PORTAL", desc: "Track active routes, scan supply packages, and update delivery status." },
      Guest: { title: "GUEST PORTAL", desc: "Explore our catalog of poultry supplies and learn about our smart farm solutions." }
    };

    let currentRole = 'Customer';
    let isRegisterMode = false;
    let otpSent = false;
    let otpTimer = null;

    function showToast(msg, icon = '✨') {
      const toast = document.getElementById('toast');
      document.getElementById('toastIcon').textContent = icon;
      document.getElementById('toastMsg').textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    function updateBadge() {
      const badge = document.getElementById('current-role-badge-verify');
      if (!badge) return;
      const map = { Customer: 'fa-bag-shopping', Provider: 'fa-shop', Logistic: 'fa-truck', Guest: 'fa-eye' };
      const icon = map[currentRole] || 'fa-user';
      badge.innerHTML = '<i class="fa-solid ' + icon + '" style="font-size:10px;"></i> ' + currentRole.toUpperCase();
      badge.style.transform = 'scale(1.15)';
      setTimeout(() => { badge.style.transform = 'scale(1)'; }, 200);
    }

    function selectRole(role) {
      currentRole = role;
      document.querySelectorAll('.role-ticket').forEach(card => card.classList.remove('active'));
      document.querySelector('.role-ticket[data-role="' + role + '"]').classList.add('active');
      updatePortalText();
      updateBadge();
      const registerRoleInput = document.getElementById('registerRole');
      if (registerRoleInput) {
        const roleMap = { 'Customer': 'customer', 'Provider': 'provider', 'Logistic': 'logistics', 'Guest': 'guest' };
        registerRoleInput.value = roleMap[role] || role.toLowerCase();
      }
    }

    function updatePortalText() {
      const header = document.getElementById('dynamic-header');
      const titleEl = document.getElementById('portal-title');
      const descEl = document.getElementById('portal-desc');
      header.style.animation = 'none';
      header.offsetHeight;
      header.style.animation = 'pop-text 0.3s cubic-bezier(0.34, 1.56, 0.64, 1) forwards';
      if (!isRegisterMode) {
        titleEl.textContent = roles[currentRole].title;
        descEl.textContent = roles[currentRole].desc;
      } else {
        titleEl.textContent = "REGISTER ACCOUNT";
        descEl.textContent = 'Create your new secure ' + currentRole + ' account on Safe Flock.';
      }
    }

    function toggleAuthMode() {
      isRegisterMode = !isRegisterMode;
      const toggleBox = document.getElementById('auth-toggle-box');
      const sliderWindow = document.getElementById('slider-window');
      const txtSign = document.getElementById('label-signin');
      const txtReg = document.getElementById('label-register');
      if (isRegisterMode) {
        toggleBox.classList.add('is-register');
        sliderWindow.classList.add('is-register');
        txtSign.classList.remove('active');
        txtReg.classList.add('active');
      } else {
        toggleBox.classList.remove('is-register');
        sliderWindow.classList.remove('is-register');
        txtReg.classList.remove('active');
        txtSign.classList.add('active');
      }
      updatePortalText();
      clearErrors();
    }

    function toggleTheme() {
      document.body.classList.toggle('dark-theme');
      document.body.classList.toggle('doodle-dark');
      try {
        const isDark = document.body.classList.contains('dark-theme');
        const role = (window.__USER_ROLE__ || currentRole || '').toString().toLowerCase();
        const key = role ? 'sfs_theme_' + role : 'sfs_theme';
        if (isDark) {
          localStorage.setItem(key, '1');
        } else {
          localStorage.removeItem(key);
        }
      } catch (e) {}
    }

    function showError(inputId, msg) {
      const input = document.getElementById(inputId);
      const err = document.getElementById('err-' + inputId);
      if (input) input.classList.add('error');
      if (err) { err.textContent = msg; err.style.display = 'block'; }
      setTimeout(() => { if (input) input.classList.remove('error'); }, 400);
    }

    function clearErrors() {
      document.querySelectorAll('.error-text').forEach(err => err.style.display = 'none');
      document.querySelectorAll('.doodle-input').forEach(inp => inp.classList.remove('error'));
    }

    async function requestOTP() {
      console.log('requestOTP called');
      clearErrors();
      const phone = document.getElementById('signin-phone').value;
      const phoneRegex = /^\d{10}$/;
      if (!phoneRegex.test(phone)) {
        showError('signin-phone', 'Enter a valid 10-digit number.');
        return;
      }

      const submitBtn = document.querySelector('#signin-phone').closest('form').querySelector('.doodle-btn');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Sending...';
      submitBtn.disabled = true;

      try {
        const formData = new FormData();
        formData.append('phone', phone);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('role', currentRole.toLowerCase());

        const resp = await fetch('auth/request_otp.php', { method: 'POST', body: formData });
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); } catch (e) { data = {}; }

        console.log('request_otp status', resp.status, 'body', data);

        if (!resp.ok || !data.ok) throw new Error(data.error || 'Failed to send OTP');

        otpSent = true;
        document.getElementById('display-phone').textContent = '+91 ' + phone;
        const modal = document.getElementById('otp-modal');
        console.log('showing otp modal', modal);
        modal.classList.add('active');
        startOtpTimer(30);
        setTimeout(() => document.querySelector('.otp-box').focus(), 100);

        if (data.demo_otp) {
          showToast('Demo OTP: ' + data.demo_otp + ' (SMS gateway unavailable)', '⚠️');
        } else if (data.sms_sent === false) {
          showToast('OTP SMS was not sent. Use passcode or fingerprint login.', '⚠️');
        } else {
          showToast('OTP sent successfully!', '✅');
        }
      } catch (err) {
        console.error('requestOTP failed:', err);
        showToast('Failed: ' + err.message, '⚠️');
      } finally {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      }
    }

    async function verifyOTP() {
      let filled = true;
      document.querySelectorAll('.otp-box').forEach(box => { if (!box.value) filled = false; });
      if (!filled) { showToast('Please enter the complete 6-digit OTP.', '⚠️'); return; }

      const phone = document.getElementById('signin-phone').value;
      const code = Array.from(document.querySelectorAll('.otp-box')).map(i => i.value).join('');

      try {
        const formData = new FormData();
        formData.append('phone', phone);
        formData.append('otp', code);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

        const resp = await window.sfsWithLoading(fetch('auth/verify_otp.php', { method: 'POST', body: formData }));
        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || !data.ok) throw new Error(data.error || 'Invalid OTP');

        if (navigator.vibrate) navigator.vibrate([50, 30, 50]);
        const role = (data.session && data.session.role) ? data.session.role : 'customer';
        if (role === 'provider') {
          window.location.href = 'provider_dashboard.php';
        } else if (role === 'logistics') {
          window.location.href = 'logistics_dashboard_spa.php';
        } else {
          window.location.href = 'index.php';
        }
      } catch (err) {
        showToast('OTP failed: ' + err.message, '⚠️');
      }
    }

    function startOtpTimer(seconds) {
      let left = seconds;
      const timerDisplay = document.getElementById('otpTimerDisplay');
      clearInterval(otpTimer);
      otpTimer = setInterval(() => {
        left--;
        const secStr = left < 10 ? '0' + left : left;
        if (timerDisplay) timerDisplay.textContent = 'Resend in 00:' + secStr;
        if (left <= 0) {
          clearInterval(otpTimer);
          if (timerDisplay) timerDisplay.textContent = 'Resend Passcode';
        }
      }, 1000);
    }

    async function triggerBiometricScan() {
      const statusEl = document.getElementById('passkeyStatus');
      if (statusEl) statusEl.textContent = 'Starting passkey...';
      if (navigator.vibrate) navigator.vibrate(20);

      const phoneInput = document.getElementById('signin-phone');
      const phone = phoneInput ? phoneInput.value.trim() : '';
      if (!phone || phone.length < 10) {
        if (statusEl) { statusEl.textContent = 'Enter 10-digit Phone/ID first.'; statusEl.style.color = '#ef4444'; }
        showToast('Please enter your 10-digit Phone/ID first.', '⚠️');
        return;
      }
      if (typeof window.triggerPasskeyLogin !== 'function') {
        if (statusEl) { statusEl.textContent = 'Passkey function missing.'; statusEl.style.color = '#ef4444'; }
        showToast('Passkey login is not available right now.', '⚠️');
        return;
      }
      try {
        if (statusEl) { statusEl.textContent = 'Opening scanner...'; statusEl.style.color = '#059669'; }
        await window.triggerPasskeyLogin(phone);
        if (statusEl) { statusEl.textContent = 'Passkey success.'; statusEl.style.color = '#059669'; }
      } catch (err) {
        console.error('Biometric login error', err);
        const msg = (err && err.message) ? err.message : JSON.stringify(err);
        if (statusEl) { statusEl.textContent = 'Passkey failed: ' + msg; statusEl.style.color = '#ef4444'; }
        showToast('Biometric login failed: ' + msg, '⚠️');
      }
    }

    function openPasscodeForm() {
      const modal = document.getElementById('passcodeModal');
      if (!modal) return;
      if (navigator.vibrate) navigator.vibrate(15);
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
      setTimeout(() => document.getElementById('backupPhone')?.focus(), 150);
    }

    function closePasscodeForm() {
      const modal = document.getElementById('passcodeModal');
      if (!modal) return;
      if (navigator.vibrate) navigator.vibrate(10);
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }

    async function submitPasscode() {
      const statusEl = document.getElementById('passcodeStatus');
      const phone = document.getElementById('backupPhone').value.trim();
      const password = document.getElementById('backupPass').value;

      if (!phone || phone.length < 10) {
        if (statusEl) { statusEl.textContent = 'Enter a valid 10-digit mobile number.'; statusEl.style.color = '#ef4444'; }
        return;
      }
      if (!password) {
        if (statusEl) { statusEl.textContent = 'Enter your security passcode.'; statusEl.style.color = '#ef4444'; }
        return;
      }

      if (statusEl) { statusEl.textContent = 'Authenticating...'; statusEl.style.color = '#666'; }

      try {
        const formData = new FormData();
        formData.append('phone', phone);
        formData.append('password', password);
        formData.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('json', '1');

        const resp = await window.sfsWithLoading(fetch('auth/login.php', { method: 'POST', body: formData }));
        const data = await resp.json().catch(() => ({}));

        if (!resp.ok || !data.ok) {
          const msg = (data && data.error) ? data.error : 'Passcode login failed.';
          if (statusEl) { statusEl.textContent = msg; statusEl.style.color = '#ef4444'; }
          return;
        }

        if (navigator.vibrate) navigator.vibrate([40, 30, 40]);
        const role = (data.session && data.session.role) ? data.session.role : 'customer';
        if (role === 'provider') {
          window.location.href = 'provider_dashboard.php';
        } else if (role === 'logistics') {
          window.location.href = 'logistics_dashboard_spa.php';
        } else {
          window.location.href = 'index.php';
        }
      } catch (err) {
        if (statusEl) { statusEl.textContent = 'Passcode login failed: ' + err.message; statusEl.style.color = '#ef4444'; }
      }
    }

    function closeModal(event, modalId) {
      if (event && event.target.id !== modalId && !event.target.classList.contains('close-btn')) return;
      document.getElementById(modalId).classList.remove('active');
    }

    function validateRegister() {
      clearErrors();
      let isValid = true;
      const name = document.getElementById('reg-name').value.trim();
      const phone = document.getElementById('reg-phone').value;
      const pass = document.getElementById('reg-pass').value;
      const confirm = document.getElementById('reg-confirm').value;
      if (!name) { showError('reg-name', 'Name is required.'); isValid = false; }
      if (!/^\d{10}$/.test(phone)) { showError('reg-phone', 'Enter a valid 10-digit number.'); isValid = false; }
      if (pass.length < 4) { showError('reg-pass', 'Min 4 digits.'); isValid = false; }
      if (pass !== confirm || !confirm) { showError('reg-confirm', 'Passcodes do not match.'); isValid = false; }
      if (isValid) {
        const roleMap = { 'Customer': 'customer', 'Provider': 'provider', 'Logistic': 'logistics', 'Guest': 'guest' };
        document.getElementById('registerRole').value = roleMap[currentRole] || 'customer';
        document.getElementById('registerForm').submit();
      }
    }

    const otpInputs = document.querySelectorAll('.otp-box');
    otpInputs.forEach((input, index) => {
      input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        if (this.value.length === 1 && index < otpInputs.length - 1) { otpInputs[index + 1].focus(); }
      });
      input.addEventListener('keydown', function(e) {
        if (e.key === 'Backspace' && !this.value && index > 0) { otpInputs[index - 1].focus(); }
      });
      input.addEventListener('paste', function(e) {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '').split('');
        let currentIdx = index;
        pastedData.forEach(digit => {
          if(currentIdx < otpInputs.length) { otpInputs[currentIdx].value = digit; currentIdx++; }
        });
        if(currentIdx < otpInputs.length) { otpInputs[currentIdx].focus(); } else { otpInputs[otpInputs.length - 1].focus(); }
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closePasscodeForm();
    });

    window.addEventListener('DOMContentLoaded', () => {
      updateBadge();
    });
  </script>
  <script src="js/webauthn.js"></script>
  <script src="js/loading.js"></script>
  <script src="js/theme.js"></script>
</body>
</html>



