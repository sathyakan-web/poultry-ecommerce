<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['customer']);
require_once __DIR__ . '/auth/csrf.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?php echo sfs_csrf_token(); ?>" />
  <title>SafeFlock Solution — Flock Dosage Calculator</title>
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
  <link rel="stylesheet" href="css/doodle.css" />
  <script>window.__USER_ROLE__=<?php echo json_encode(sfs_current_role()); ?>;</script>
  <script src="js/theme.js"></script>
  <style>
    :root {
      --ink: #23272f;
      --paper: #ffffff;
      --paper-tint: #fbf9f4;
      --desk-bg: #eceae4;
      --primary-accent: #00d28d;
      --primary-hover: #00b97b;
      --sticky-yellow: #ffec85;
      --sticky-shadow: #e2cc57;
      --teal-accent: #3ad3c5;
      --coral-accent: #ff6e6e;
      --washi-teal: rgba(78, 205, 196, 0.45);
      --washi-pink: rgba(255, 110, 110, 0.4);
      --grid-line: rgba(0, 0, 0, 0.04);
      --border-ink: 2.5px solid var(--ink);
      --hard-shadow: 4px 4px 0px var(--ink);
      --hard-shadow-sm: 2px 2px 0px var(--ink);
      --hard-shadow-lg: 6px 6px 0px var(--ink);
    }

    body.doodle-dark {
      --ink: #f5f5f7;
      --paper: #1e2128;
      --paper-tint: #252932;
      --desk-bg: #121418;
      --primary-accent: #05dca0;
      --sticky-yellow: #323528;
      --sticky-shadow: #212319;
      --teal-accent: #45e3d6;
      --coral-accent: #ff7b7b;
      --washi-teal: rgba(69, 227, 214, 0.3);
      --washi-pink: rgba(255, 123, 123, 0.3);
      --grid-line: rgba(255, 255, 255, 0.04);
    }

    body {
      font-family: 'Fredoka', 'Comic Neue', sans-serif;
      background-color: var(--desk-bg);
      color: var(--ink);
      min-height: 100vh;
      background-image:
        linear-gradient(var(--grid-line) 1.5px, transparent 1.5px),
        linear-gradient(90deg, var(--grid-line) 1.5px, transparent 1.5px);
      background-size: 24px 24px;
      transition: background-color 0.25s ease, color 0.25s ease;
    }

    .calc-wrapper {
      width: 100%;
      max-width: 1100px;
      margin: 0 auto;
      padding: 18px 16px 110px;
    }

    .calc-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }

    .calc-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      text-decoration: none;
    }

    .calc-logo {
      width: 56px;
      height: 56px;
      border-radius: 14px;
      background: #ffffff;
      border: 2px solid var(--ink);
      padding: 6px;
      box-shadow: var(--hard-shadow-sm);
      object-fit: contain;
    }

    .calc-brand-text {
      display: flex;
      flex-direction: column;
    }

    .calc-brand-main {
      font-family: 'Gochi Hand', 'Marker Felt', cursive;
      font-size: 26px;
      font-weight: bold;
      color: var(--ink);
      line-height: 1;
      letter-spacing: 0.5px;
    }

    .calc-brand-sub {
      font-size: 11px;
      font-weight: 900;
      color: #ff6b6b;
      letter-spacing: 2px;
      text-transform: uppercase;
      line-height: 1;
      margin-top: 2px;
    }

    .cart-pill {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: var(--sticky-yellow);
      border: var(--border-ink);
      box-shadow: var(--hard-shadow-sm);
      border-radius: 12px;
      padding: 8px 12px;
      font-weight: 700;
      text-decoration: none;
      color: #111;
    }

    .cart-pill .badge {
      background: var(--coral-accent);
      color: #fff;
      font-size: 11px;
      padding: 2px 8px;
      border-radius: 999px;
      border: 1.5px solid #111;
      font-family: monospace;
    }

    .calc-card {
      background: var(--paper);
      border: var(--border-ink);
      box-shadow: var(--hard-shadow);
      border-radius: 16px;
      padding: 22px;
      position: relative;
    }

    .washi-tape {
      position: absolute;
      height: 22px;
      width: 100px;
      border: 1px dashed rgba(0,0,0,0.25);
      pointer-events: none;
      z-index: 10;
    }

    .washi-teal {
      background: var(--washi-teal);
      transform: rotate(-2deg);
      top: -11px;
      left: 24px;
    }

    .washi-pink {
      background: var(--washi-pink);
      transform: rotate(3deg);
      top: -11px;
      right: 24px;
    }

    .section-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 10px;
      margin-bottom: 14px;
      border-bottom: 2px dashed rgba(0,0,0,0.18);
    }

    .section-title-left {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .section-title h2 {
      margin: 0;
      font-size: 18px;
      font-weight: 900;
    }

    .pill {
      font-size: 12px;
      font-weight: 900;
      padding: 6px 10px;
      border-radius: 999px;
      background: #d1fae5;
      color: #065f46;
      border: 1.5px solid rgba(6, 95, 70, 0.35);
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 18px;
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }

    .form-group label {
      font-size: 12px;
      font-weight: 900;
    }

    .form-input {
      background: var(--paper-tint);
      border: 2px solid var(--ink);
      box-shadow: var(--hard-shadow-sm);
      border-radius: 12px;
      padding: 10px 12px;
      font-size: 15px;
      font-weight: 700;
      color: var(--ink);
      width: 100%;
      box-sizing: border-box;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--teal-accent);
      box-shadow: 3px 3px 0px var(--teal-accent);
      transform: translateY(-1px);
    }

    .select-wrap {
      position: relative;
    }

    .select-wrap select {
      appearance: none;
    }

    .select-arrow {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      pointer-events: none;
      font-size: 11px;
      font-weight: 900;
    }

    .dropdown {
      position: relative;
    }

    .dropdown-menu {
      position: absolute;
      left: 0;
      right: 0;
      top: calc(100% + 8px);
      z-index: 30;
      background: var(--paper);
      border: var(--border-ink);
      box-shadow: var(--hard-shadow-lg);
      border-radius: 14px;
      padding: 8px;
      display: none;
      max-height: 260px;
      overflow-y: auto;
    }

    .dropdown-menu.open {
      display: block;
    }

    .dropdown-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      padding: 10px;
      border-radius: 10px;
      cursor: pointer;
    }

    .dropdown-item:hover {
      background: #d1fae5;
    }

    .dropdown-item img {
      width: 38px;
      height: 38px;
      object-fit: contain;
      border-radius: 8px;
      border: 2px solid var(--ink);
      background: var(--paper-tint);
      flex-shrink: 0;
    }

    .summary-card {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 12px;
      border: 2px dashed rgba(5, 150, 105, 0.45);
      background: #d1fae5;
    }

    .summary-icon {
      font-size: 26px;
    }

    .calc-btn {
      width: 100%;
      background: var(--primary-accent);
      color: #042f2e;
      border: var(--border-ink);
      box-shadow: var(--hard-shadow);
      border-radius: 12px;
      padding: 12px;
      font-weight: 900;
      font-size: 16px;
      cursor: pointer;
    }

    .calc-btn:hover {
      background: var(--primary-hover);
    }

    .calc-btn:active {
      transform: translate(3px, 3px);
      box-shadow: 1px 1px 0px var(--ink);
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.55);
      backdrop-filter: blur(3px);
      z-index: 50;
      display: none;
      align-items: flex-start;
      justify-content: center;
      padding: 12px;
      overflow-y: auto;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-sheet {
      width: 100%;
      max-width: 520px;
      max-height: 70vh;
      overflow-y: auto;
      background: var(--paper);
      border: var(--border-ink);
      box-shadow: var(--hard-shadow-lg);
      border-radius: 16px;
      padding: 16px;
      position: relative;
      margin-top: 0;
    }

    .modal-sheet::-webkit-scrollbar {
      width: 8px;
    }
    .modal-sheet::-webkit-scrollbar-track {
      background: transparent;
    }
    .modal-sheet::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
      border: 2px solid var(--paper);
    }
    .modal-sheet {
      scrollbar-width: thin;
      scrollbar-color: #cbd5e1 transparent;
    }

    .modal-topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 10px;
      margin-bottom: 12px;
      border-bottom: 2px dashed rgba(0,0,0,0.18);
    }

    .modal-topbar h3 {
      margin: 0;
      font-size: 17px;
      font-weight: 900;
    }

    .ghost-btn {
      background: var(--paper-tint);
      border: 2px solid var(--ink);
      box-shadow: var(--hard-shadow-sm);
      border-radius: 10px;
      padding: 6px 10px;
      font-weight: 900;
      cursor: pointer;
      color: var(--ink);
    }

    .result-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 0;
      border-bottom: 1px solid rgba(0,0,0,0.08);
      font-weight: 700;
      font-size: 14px;
    }

    .cost-banner {
      margin: 14px 0;
      padding: 14px;
      border-radius: 12px;
      background: #0f172a;
      color: #fff;
      border: 2px solid #000;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 3px 3px 0px var(--primary-accent);
    }

    .math-card {
      background: var(--sticky-yellow);
      color: #1f2937;
      border: 2px solid #000;
      border-radius: 12px;
      padding: 14px;
      box-shadow: 3px 3px 0px rgba(0,0,0,0.15);
      position: relative;
      margin-bottom: 14px;
    }

    .math-card ul {
      margin: 0;
      padding-left: 18px;
      font-size: 13px;
      font-weight: 700;
      line-height: 1.5;
    }

    .toast {
      position: fixed;
      bottom: 18px;
      left: 50%;
      transform: translateX(-50%);
      background: #0f172a;
      color: #fff;
      border: 2px solid #000;
      box-shadow: var(--hard-shadow);
      border-radius: 12px;
      padding: 10px 14px;
      font-weight: 900;
      z-index: 60;
      display: none;
      gap: 8px;
      align-items: center;
    }

    .toast.show {
      display: inline-flex;
    }

    @media (max-width: 640px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
      .calc-header {
        flex-wrap: wrap;
      }
    }

    @media (min-width: 641px) and (max-width: 900px) {
      .form-grid {
        grid-template-columns: repeat(2, 1fr);
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
      .flock-dock {
        width: 100%;
        height: 65px;
        padding: 8px 10px;
        overflow-x: auto;
        overflow-y: hidden;
        gap: 6px;
        scrollbar-width: none;
        -ms-overflow-style: none;
        justify-content: flex-start;
      }
      .flock-dock::-webkit-scrollbar {
        display: none;
      }
      .nav-item {
        flex: 0 0 auto;
        width: 72px;
        min-width: 64px;
        height: 100%;
      }
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

    @keyframes popUpIn {
      0% { opacity: 0; transform: scale(0.92) translateY(20px); }
      70% { transform: scale(1.02) translateY(-4px); }
      100% { opacity: 1; transform: scale(1) translateY(0); }
    }

    @keyframes floatingCartEffect {
      0% { transform: translate(0, 0) scale(1); opacity: 1; }
      50% { transform: translate(40px, -60px) scale(1.3) rotate(15deg); opacity: 0.9; }
      100% { transform: translate(120px, -120px) scale(0.3) rotate(35deg); opacity: 0; }
    }

    .animate-modal-pop {
      animation: popUpIn 0.3s cubic-bezier(0.18, 0.89, 0.32, 1.28) forwards;
    }

    .flying-badge {
      animation: floatingCartEffect 0.8s ease-in-out forwards;
    }
  </style>
</head>
<body data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
  <div class="calc-wrapper">
    <header class="calc-header">
      <a href="index.php" class="calc-brand">
        <img src="images/sfs_icon.png" alt="Poultry Logo" class="calc-logo">
        <div class="calc-brand-text">
          <span class="calc-brand-main">SafeFlock Solution</span>
          <span class="calc-brand-sub">SafeFlock Solution</span>
        </div>
      </a>
      <a href="cart.php" class="cart-pill">
        <span>🛒</span>
        <span>Cart:</span>
        <span id="cartCountBadge" class="badge">0</span>
      </a>
    </header>

    <main class="calc-card">
      <div class="washi-tape washi-teal"></div>
      <div class="washi-tape washi-pink"></div>

      <div class="section-title">
        <div class="section-title-left">
          <span>📋</span>
          <h2>Flock Details</h2>
        </div>
        <span id="categoryCounterPill" class="pill">4 Categories Ready</span>
      </div>

      <form id="dosageForm" class="space-y-4">
        <div class="form-grid">
          <div>
            <label class="block text-xs font-bold mb-1.5">Product Category</label>
            <div class="select-wrap">
              <select id="categorySelect" class="form-input">
                <option value="" disabled selected>Select category</option>
                <option value="vaccines">Vaccines</option>
                <option value="layer_meds">Layer Medicines</option>
                <option value="feed_meds">Feed Medicines</option>
                <option value="water_sanitation">Water Sanitation</option>
              </select>
              <span class="select-arrow">▼</span>
            </div>
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="block text-xs font-bold">Select Product</label>
              <span id="productCountBadge" class="text-[11px] font-bold text-teal-600"></span>
            </div>
            <div class="dropdown">
              <button type="button" id="productDropdownBtn" class="form-input text-left flex items-center justify-between">
                <span id="selectedProductText" class="truncate text-slate-500">— Select category first —</span>
                <span class="text-xs font-bold ml-2">▼</span>
              </button>
              <div id="productDropdownList" class="dropdown-menu"></div>
            </div>
          </div>

          <div>
            <label class="block text-xs font-bold mb-1.5">Total Number of Birds</label>
            <input type="number" id="birdCountInput" value="1000" min="1" step="1" required class="form-input" placeholder="e.g. 1000">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-bold flex items-center gap-1">Dosage / 100 Birds <span id="dosageUnitLabel">(ml)</span></label>
              <span class="text-[11px] bg-teal-100 border border-teal-800 text-teal-800 rounded-full w-4 h-4 inline-flex items-center justify-center font-bold" title="Dosage prescribed per 100 birds daily">?</span>
            </div>
            <input type="number" id="dosageRateInput" value="10" min="0.1" step="0.1" required class="form-input" placeholder="10">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-bold flex items-center gap-1">Water Cons. (ml/bird)</label>
              <span class="text-[11px] bg-teal-100 border border-teal-800 text-teal-800 rounded-full w-4 h-4 inline-flex items-center justify-center font-bold" title="Average daily water intake per bird">?</span>
            </div>
            <input type="number" id="waterIntakeInput" value="250" min="1" step="1" required class="form-input" placeholder="250">
          </div>

          <div>
            <div class="flex items-center justify-between mb-1.5">
              <label class="text-xs font-bold flex items-center gap-1">Number of Treatment Days</label>
              <span class="text-[11px] bg-teal-100 border border-teal-800 text-teal-800 rounded-full w-4 h-4 inline-flex items-center justify-center font-bold" title="Full course duration of medicine">?</span>
            </div>
            <input type="number" id="treatmentDaysInput" value="5" min="1" max="60" required class="form-input" placeholder="5">
          </div>
        </div>

        <div class="summary-card">
          <span id="quickProductIcon" class="summary-icon">💊</span>
          <div>
            <div id="quickProductName" class="font-bold text-sm">No product chosen</div>
            <div id="quickProductMeta" class="text-slate-500 text-xs font-medium">Select product to unlock calculation</div>
          </div>
        </div>

        <div style="margin-top: 20px;">
          <button type="submit" id="calculateSubmitBtn" class="calc-btn flex items-center justify-center gap-2">
            <span>🧮</span>
            <span>Calculate Cost & Dosage</span>
          </button>
        </div>
      </form>
    </main>
  </div>

  <div id="calcResultModal" class="modal-overlay" onclick="if(event.target===this)closeResultModal()">
    <div class="modal-sheet" onclick="event.stopPropagation()">
      <div class="washi-tape washi-pink" style="top:-11px; left:50%; transform:translateX(-50%) rotate(-2deg);"></div>
      <div class="modal-topbar">
        <div class="flex items-center gap-2">
          <span>📊</span>
          <h3>Final Calculation</h3>
        </div>
        <button type="button" onclick="closeResultModal()" class="ghost-btn">← Edit Details</button>
      </div>

      <div class="space-y-2.5 text-sm font-semibold">
        <div class="result-row">
          <span class="text-slate-500">Flock Size</span>
          <span id="resFlockSize" class="font-bold text-base">100,000 birds</span>
        </div>
        <div class="result-row">
          <span class="text-slate-500">Daily Water Need</span>
          <span id="resWaterNeed" class="font-bold">100.0 L (100,000 ml)</span>
        </div>
        <div class="result-row">
          <span class="text-teal-600 font-bold">Required Dosage / Day</span>
          <span id="resDosageDay" class="font-bold text-teal-600 text-base">500.0 ml</span>
        </div>
        <div class="result-row">
          <span class="text-slate-500">Total Packages Needed</span>
          <span id="resPackagesNeeded" class="font-bold">1 × 500g pack</span>
        </div>
      </div>

      <div class="cost-banner">
        <span class="font-bold tracking-wide text-xs uppercase text-slate-300">Total Estimated Cost</span>
        <span id="resTotalCost" class="text-2xl font-black text-amber-300 font-mono">₹600.00</span>
      </div>

      <div class="math-card">
        <div class="w-3 h-3 rounded-full bg-rose-500 border border-black absolute top-2 right-3 shadow-xs"></div>
        <h4 class="font-bold text-xs uppercase tracking-wider mb-2 flex items-center gap-1.5 text-slate-800">
          <span>💡</span> The Math Breakdown:
        </h4>
        <ul id="mathStepsList" class="text-xs space-y-1 font-medium list-disc list-inside leading-relaxed text-slate-800"></ul>
      </div>

      <button id="addToCartBtn" onclick="handleAddToCartAnimation(event)" class="calc-btn flex items-center justify-center gap-2 mt-4" style="background:var(--sticky-yellow); color:#1f2937;">
        <span>🛒</span>
        <span class="font-bold">Add to Cart & Order</span>
      </button>
    </div>
  </div>

  <div id="doodleToast" class="toast">
    <span id="toastIcon">✨</span>
    <span id="toastMsg">Notification message</span>
  </div>

  <script>
    let allProducts = [];
    let selectedProduct = null;
    let cartItemsCount = 0;
    let lastCalculation = null;

    const categorySelect = document.getElementById('categorySelect');
    const productDropdownBtn = document.getElementById('productDropdownBtn');
    const productDropdownList = document.getElementById('productDropdownList');
    const selectedProductText = document.getElementById('selectedProductText');
    const productCountBadge = document.getElementById('productCountBadge');
    const dosageUnitLabel = document.getElementById('dosageUnitLabel');
    const dosageRateInput = document.getElementById('dosageRateInput');
    const productSummaryCard = document.querySelector('.summary-card');
    const dosageForm = document.getElementById('dosageForm');

    dosageForm?.addEventListener('submit', (e) => {
      e.preventDefault();
      calculateDosage();
    });

    function csrf() {
      return document.querySelector('meta[name="csrf-token"]')?.content || '';
    }

    function inferType(size) {
      if (!size) return 'powder';
      const s = String(size).toLowerCase();
      if (s.includes('g') || s.includes('kg') || s.includes('powder')) return 'powder';
      if (s.includes('ml') || s.includes('litre') || s.includes('vial') || s.includes('dose') || s.includes('liquid')) return 'liquid';
      return 'powder';
    }

    function getIcon(category) {
      const icons = {
        vaccines: '💉',
        layer_meds: '📦',
        feed_meds: '🌾',
        water_sanitation: '🧴'
      };
      return icons[category] || '💊';
    }

    function getDefaultDosage(category) {
      const defaults = {
        vaccines: 1,
        layer_meds: 10,
        feed_meds: 15,
        water_sanitation: 4
      };
      return defaults[category] || 10;
    }

    async function loadProducts() {
      try {
        const res = await fetch('api/products.php?category=all', { credentials: 'same-origin' });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to load products');
        allProducts = (data.products || []).map(p => {
          const mappedCategory = p.category === 'water' ? 'water_sanitation' : p.category;
          const localLogo = (p.logo_path || p.image_path || p.image || '').trim();
          const usableLogo = localLogo && !localLogo.toLowerCase().startsWith('http') ? localLogo : '';
          const raw = usableLogo || guessProductImage(p.name, mappedCategory) || '';
          return {
            id: p.id,
            name: p.name,
            category: mappedCategory,
            pack: p.size || '',
            price: Number(p.price || 0),
            type: inferType(p.size),
            icon: getIcon(mappedCategory),
            defaultDosage: getDefaultDosage(mappedCategory),
            image: raw
          };
        });
      } catch (e) {
        showToast('Failed to load products: ' + e.message);
      }
    }

    function guessProductImage(name, category) {
      if (!name) return '';
      const n = name.toLowerCase();
      const map = [
        ['amprolium', 'uploads/MED-AMPROLIUM-001.jpg'],
        ['bacitracin', 'uploads/MED-BMD-001.jpg'],
        ['bmd', 'uploads/MED-BMD-001.jpg'],
        ['chlortetracycline', 'uploads/MED-CTC-001.jpg'],
        ['ctc', 'uploads/MED-CTC-001.jpg'],
        ['fenbendazole', 'uploads/MED-FENBENDAZOLE-001.jpg'],
        ['hygromycin', 'uploads/MED-HYGROMYCINB-0014.jpg'],
        ['oxytetracycline', 'uploads/MED-OXY-001.jpg'],
        ['avian influenza', 'uploads/VCX-H5-005.jpg'],
        ['h5n1', 'uploads/VCX-H5-005.jpg'],
        ['newcastle', 'uploads/VCX-ND-003.jpg'],
        ['lasota', 'uploads/VCX-ND-003.jpg'],
        ['gumboro', 'uploads/VCX-GUMB-001.jpg'],
        ['ibd', 'uploads/VCX-IBD-002.jpg'],
        ['marek', 'uploads/VCX-REV-004.jpg'],
        ['hvt', 'uploads/VCX-REV-004.jpg'],
        ['choline', 'uploads/FEED-CHL-001.jpg'],
        ['toxin binder', 'uploads/FMD-TAX-001.jpg'],
        ['acidifier', 'uploads/FEED-ELECTRO-001.jpg'],
        ['vitamin', 'uploads/FEED-AD3E-001.jpg'],
        ['ad3e', 'uploads/FEED-AD3E-001.jpg'],
        ['quat', 'uploads/WTR-QAC-001.jpg'],
        ['aquatabs', 'uploads/WTR-CHL-TAB-001.jpg'],
        ['chlorine', 'uploads/WTR-CHL-TAB-001.jpg'],
        ['hydrogen peroxide', 'uploads/WTR-H2O2-001.jpg'],
        ['h2o2', 'uploads/WTR-H2O2-001.jpg']
      ];
      for (const [key, path] of map) {
        if (n.includes(key)) return path;
      }
      if (category === 'vaccines') return 'uploads/VCX-NEW-001.jpg';
      if (category === 'layer_meds') return 'uploads/MED-AMPROLIUM-001.jpg';
      if (category === 'feed_meds') return 'uploads/FEED-CALCIUM-001.jpg';
      if (category === 'water_sanitation') return 'uploads/WTR-QAC-001.jpg';
      return '';
    }

  async function refreshCartCount() {
      try {
        const res = await fetch('api/cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': csrf() },
          body: new URLSearchParams({ action: 'get' }).toString(),
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (data.ok) {
          cartItemsCount = (data.cart || []).reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
          const badge = document.getElementById('cartCountBadge');
          if (badge) badge.textContent = cartItemsCount;
        }
      } catch (e) {
        console.error('Failed to refresh cart count', e);
      }
    }

    categorySelect.addEventListener('change', () => {
      const cat = categorySelect.value;
      const matching = allProducts.filter(p => p.category === cat);
      productCountBadge.textContent = matching.length + ' products';
      renderDropdownProducts(matching);
      productDropdownList.classList.add('open');
      selectedProductText.textContent = 'Select a product (' + matching.length + ')';
      selectedProduct = null;
      if (productSummaryCard) productSummaryCard.style.opacity = '0.5';
    });

    productDropdownBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      if (!categorySelect.value) {
        showToast('Please pick a category first! 📋');
        categorySelect.focus();
        return;
      }
      productDropdownList.classList.toggle('open');
    });

    document.addEventListener('click', (e) => {
      if (!productDropdownBtn.contains(e.target) && !productDropdownList.contains(e.target)) {
        productDropdownList.classList.remove('open');
      }
    });

    function renderDropdownProducts(products) {
      productDropdownList.innerHTML = '';
      products.forEach(p => {
        const item = document.createElement('div');
        item.className = 'dropdown-item';
        const imgHtml = p.image ? `<img src="${p.image}" alt="" onerror="this.style.display='none'" />` : '';
        item.innerHTML = `
          <div class="flex items-center gap-2">
            ${imgHtml}
            <div>
              <div class="font-bold text-xs sm:text-sm">${p.name}</div>
              <div class="text-[11px] text-slate-500 font-medium">${p.pack} • ${p.category}</div>
            </div>
          </div>
          <div class="text-right">
            <span class="font-bold text-xs sm:text-sm text-emerald-600">₹${p.price.toFixed(2)}</span>
          </div>
        `;
        item.addEventListener('click', () => selectProductItem(p));
        productDropdownList.appendChild(item);
      });
    }

    function selectProductItem(prod) {
      selectedProduct = prod;
      selectedProductText.innerHTML = '<span class="font-bold">' + prod.icon + ' ' + prod.name + '</span><span class="text-xs text-emerald-600 ml-1.5 font-bold">(₹' + prod.price + ')</span>';
      productDropdownList.classList.remove('open');
      dosageUnitLabel.textContent = prod.type === 'powder' ? '(g)' : '(ml)';
      dosageRateInput.value = prod.defaultDosage;
      if (productSummaryCard) productSummaryCard.style.opacity = '1';
      document.getElementById('quickProductIcon').textContent = prod.icon;
      document.getElementById('quickProductName').textContent = prod.name + ' (' + prod.pack + ')';
      document.getElementById('quickProductMeta').textContent = 'Cost: ₹' + prod.price + ' | Pack Unit: ' + prod.type;
    }

    function calculateDosage() {
      if (!selectedProduct) {
        showToast('Please pick a medicine/product first! 🐔');
        return;
      }

      const birds = parseFloat(document.getElementById('birdCountInput').value) || 0;
      const dosageRate = parseFloat(document.getElementById('dosageRateInput').value) || 0;
      const waterIntakeMl = parseFloat(document.getElementById('waterIntakeInput').value) || 0;
      const days = parseInt(document.getElementById('treatmentDaysInput').value) || 1;

      if (birds <= 0) {
        showToast('Please enter a valid bird count.');
        return;
      }

      const totalWaterMlDay = birds * waterIntakeMl;
      const totalWaterLDay = totalWaterMlDay / 1000;
      const dailyDosageAmount = (birds / 100) * dosageRate;
      const totalDosageCourse = dailyDosageAmount * days;

      let packCapacity = 500;
      const match = selectedProduct.pack.match(/(\d+)\s*(g|ml|kg|litres|tabs|doses|dose)/i);
      if (match) {
        let val = parseFloat(match[1]);
        let unit = match[2].toLowerCase();
        if (unit === 'kg' || unit === 'litres') val *= 1000;
        packCapacity = val;
      }

      const packagesNeeded = Math.max(1, Math.ceil(totalDosageCourse / packCapacity));
      const totalCost = packagesNeeded * selectedProduct.price;
      const unitStr = selectedProduct.type === 'powder' ? 'g' : 'ml';

      lastCalculation = {
        product: selectedProduct,
        birds,
        days,
        packagesNeeded,
        totalCost
      };

      document.getElementById('resFlockSize').textContent = birds.toLocaleString() + ' birds';
      document.getElementById('resWaterNeed').textContent = totalWaterLDay.toFixed(1) + ' L (' + totalWaterMlDay.toLocaleString() + ' ml)';
      document.getElementById('resDosageDay').textContent = dailyDosageAmount.toFixed(1) + ' ' + unitStr;
      document.getElementById('resPackagesNeeded').textContent = packagesNeeded + ' × ' + selectedProduct.pack;
      document.getElementById('resTotalCost').textContent = '₹' + totalCost.toFixed(2);

      const mathList = document.getElementById('mathStepsList');
      mathList.innerHTML = `
        <li><strong>Flock Base:</strong> ${birds.toLocaleString()} birds ÷ 100 × ${dosageRate} ${unitStr} = <strong>${dailyDosageAmount.toFixed(1)} ${unitStr}/day</strong></li>
        <li><strong>Treatment Span:</strong> ${days} day(s) course required</li>
        <li><strong>Total Required:</strong> ${dailyDosageAmount.toFixed(1)} ${unitStr} × ${days} days = <strong>${totalDosageCourse.toFixed(1)} ${unitStr} total</strong></li>
        <li><strong>Package Unit:</strong> 1 pack (${selectedProduct.pack}) @ ₹${selectedProduct.price.toFixed(2)}</li>
      `;

      document.getElementById('calcResultModal').classList.add('open');
    }

    function closeResultModal() {
      document.getElementById('calcResultModal').classList.remove('open');
    }

    async function handleAddToCartAnimation(event) {
      if (!lastCalculation) return;

      const btn = document.getElementById('addToCartBtn');
      const cartBadge = document.getElementById('cartCountBadge');
      const cartPill = document.querySelector('.cart-pill');

      try {
        const form = new URLSearchParams();
        form.set('action', 'add');
        form.set('product_id', String(lastCalculation.product.id));
        form.set('qty', String(lastCalculation.packagesNeeded));

        const res = await fetch('api/cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-CSRF-Token': csrf() },
          body: form.toString(),
          credentials: 'same-origin'
        });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Failed to add to cart');

        await refreshCartCount();

        const rect = btn.getBoundingClientRect();
        const fly = document.createElement('div');
        fly.className = 'fixed font-bold text-sm bg-rose-500 text-white px-2 py-0.5 rounded-full border border-black z-50 flying-badge pointer-events-none';
        fly.textContent = '+' + lastCalculation.packagesNeeded + ' Pack';
        fly.style.left = (rect.left + rect.width / 2) + 'px';
        fly.style.top = rect.top + 'px';
        document.body.appendChild(fly);

        if (cartPill) {
          cartPill.style.transform = 'scale(1.08)';
          setTimeout(() => cartPill.style.transform = '', 200);
        }

        setTimeout(() => fly.remove(), 800);

        closeResultModal();
        showToast('Added ' + lastCalculation.packagesNeeded + 'x ' + lastCalculation.product.name + ' to cart! 🛒');
      } catch (e) {
        showToast('Failed to add to cart: ' + e.message);
      }
    }

    function showToast(msg) {
      const toast = document.getElementById('doodleToast');
      document.getElementById('toastMsg').textContent = msg;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    window.addEventListener('DOMContentLoaded', async () => {
      await loadProducts();
      if (allProducts.length > 0) {
        const defaultProduct = allProducts.find(p => p.category === 'layer_meds') || allProducts[0];
        if (defaultProduct) {
          categorySelect.value = defaultProduct.category;
          categorySelect.dispatchEvent(new Event('change'));
          selectProductItem(defaultProduct);
        }
      }
      refreshCartCount();
    });
  </script>

  <div class="web-dock-container">
    <nav class="flock-dock" id="bottomNav">
      <div class="active-indicator" id="activeIndicator"></div>
      <?php $role = htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>
      <?php if ($role === 'customer'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="products.php" class="nav-item" data-page="shop"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"></path><path d="M12 11v4"></path><path d="M10 13h4"></path></svg></div><span class="nav-label">Shop</span></a>
        <a href="cart.php" class="nav-item" data-page="cart"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path></svg></div><span class="nav-label">Cart</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="calculator.php" class="nav-item active" data-page="calculator"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div><span class="nav-label">Dosage Calc</span></a>
        <a href="settings.php" class="nav-item" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1 2-2v-.09a1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="provider_dashboard.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item active" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1 2-2v-.09a1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="logistics_route.php" class="nav-item" data-page="route"><div class="icon-container"><svg viewBox="0 0 24 24"><polygon points="3 11 22 2 13 21 11 13 3 11"></polygon></svg></div><span class="nav-label">Route</span></a>
        <a href="settings.php" class="nav-item active" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
  <script src="js/desktop-dock.js"></script>
</body>
</html>


