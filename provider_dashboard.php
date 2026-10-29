<?php
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['provider']);
require_once __DIR__ . '/auth/csrf.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Expires: 0');
header('Pragma: no-cache');

$user = sfs_current_user();
$role = sfs_current_role();
$csrfToken = sfs_csrf_token();
$providerName = $user['full_name'] ?? 'Provider';
$providerInitials = strtoupper(substr($providerName, 0, 2));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Provider Dashboard - SafeFlock Solution</title>
  <meta name="description" content="Provider dashboard for SafeFlock Solution. Manage orders, assign logistics, and track poultry product deliveries." />
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
  <link rel="icon" type="image/x-icon" href="images/favicon.ico">
  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">
  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">
  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#d4af37">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/loading.css" />
  <script src="js/theme.js"></script>
  <style>
    .theme-toggle {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      font-size: 12px;
      font-weight: 900;
      color: var(--ink);
      background: var(--paper-front);
      border: var(--border-width) solid var(--ink);
      border-radius: 9999px;
      padding: 4px 10px;
      box-shadow: 2px 2px 0px var(--ink);
      transform: rotate(-1deg);
      user-select: none;
    }
    .theme-toggle input {
      display: none;
    }
    .theme-toggle .toggle-track {
      width: 32px;
      height: 18px;
      background: var(--disabled-color);
      border-radius: 9999px;
      position: relative;
      transition: background 0.2s;
    }
    .theme-toggle input:checked + .toggle-track {
      background: var(--switch-bg);
    }
    .theme-toggle .toggle-thumb {
      position: absolute;
      top: 2px;
      left: 2px;
      width: 14px;
      height: 14px;
      background: #fff;
      border-radius: 50%;
      box-shadow: 0 1px 3px rgba(0,0,0,0.3);
      transition: transform 0.2s;
    }
    .theme-toggle input:checked + .toggle-track .toggle-thumb {
      transform: translateX(14px);
    }
    :root {
      --ink: #323232;
      --paper-front: #fff9e6;
      --paper-disabled: #f1f5f9;
      --bg-color: #ffffff;
      --action-color: #06d6a0;
      --action-hover: #05b889;
      --disabled-color: #94a3b8;
      --warning-color: #ff9f1c;
      --switch-bg: #4ecdc4;
      --accent-blue: #4ecdc4;
      --border-width: 2.5px;
      --shadow-offset: 4px;
      --sketch-radius-1: 15px 25px 15px 25px / 25px 15px 25px 15px;
      --sketch-radius-2: 25px 15px 25px 15px / 15px 25px 15px 25px;
      --sketch-radius-btn: 12px 18px 12px 18px / 18px 12px 18px 12px;
      --dock-radius: 45px 55px 45px 55px / 55px 45px 55px 45px;
      --indicator-radius: 35px 40px 35px 40px / 40px 35px 40px 35px;
      --dock-height: 76px;
      --icon-size: 26px;
      --font-family: "Gochi Hand", "Comic Sans MS", "Chalkboard SE", sans-serif;
      --font-ui: "Comic Neue", "Comic Sans MS", sans-serif;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    html, body { overflow-x: hidden; }

    body {
      font-family: var(--font-ui);
      background-color: #f0f0f0;
      color: var(--ink);
      display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; overflow-x: hidden;
    }

    .mobile-container {
      width: 100%; max-width: 414px; height: 100dvh; min-height: 100vh; min-height: 100dvh; position: relative;
      background-color: var(--bg-color); border: 3px solid var(--ink);
      display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
      background-image: radial-gradient(var(--ink) 1px, transparent 1px); background-size: 20px 20px;
    }

    .header {
      display: flex; justify-content: space-between; align-items: center; padding: 20px 20px 10px 20px;
      background-color: rgba(255, 255, 255, 0.9); border-bottom: 2px dashed var(--ink); z-index: 10;
    }

    .page-title {
      font-family: var(--font-family); font-size: 28px; color: var(--ink);
      transform: rotate(-2deg); letter-spacing: 1px; text-shadow: 1px 1px 0px rgba(0,0,0,0.1);
    }

    .avatar {
      width: 42px; height: 42px; background-color: var(--switch-bg); color: var(--ink);
      border: var(--border-width) solid var(--ink); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
      display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;
      font-family: var(--font-family); transform: rotate(3deg); box-shadow: 2px 2px 0px var(--ink);
    }

    .content-area { flex-grow: 1; padding: 20px; overflow-y: auto; padding-bottom: 140px; position: relative; }

    .spa-view { display: none; flex-direction: column; gap: 20px; animation: fade-in 0.3s ease; height: 100%; }
    .spa-view.active { display: flex; }
    @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    .doodle-card {
      background-color: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1);
      box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink); padding: 20px; position: relative;
      transition: all 0.2s ease; display: flex; flex-direction: column; gap: 15px;
      background-image: repeating-linear-gradient(transparent, transparent 28px, rgba(0, 0, 0, 0.05) 28px, rgba(0, 0, 0, 0.05) 30px);
      background-position: 0 10px;
    }
    .doodle-card:hover { transform: translateY(-2px) rotate(0deg) !important; }

    .doodle-card.completed {
      background-color: var(--paper-disabled); opacity: 0.8; box-shadow: 2px 2px 0px var(--ink); background-image: none;
    }

    .card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed rgba(50,50,50,0.2); padding-bottom: 10px; }
    .order-id { font-family: var(--font-family); font-size: 24px; color: var(--ink); font-weight: bold; }

    .badge {
      font-size: 13px; font-weight: bold; padding: 4px 10px; border-radius: 12px; border: 2px solid var(--ink);
      text-transform: uppercase; letter-spacing: 0.5px; transform: rotate(2deg);
    }
    .badge-pending { background-color: var(--warning-color); color: var(--ink); }
    .badge-done { background-color: var(--disabled-color); color: var(--ink); }
    .badge-transit { background-color: var(--accent-blue); color: var(--ink); transform: rotate(-1deg); }

    .card-details { display: flex; flex-direction: column; gap: 10px; font-size: 16px; }
    .detail-row { display: flex; justify-content: space-between; align-items: center; }
    .detail-label { font-family: var(--font-family); font-size: 18px; font-weight: bold; }
    .detail-value { font-weight: bold; text-align: right; font-size: 15px; }

    .doodle-select {
      width: 100%; padding: 10px 15px; font-family: var(--font-family); font-size: 18px; font-weight: bold; color: var(--ink);
      background-color: var(--bg-color); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn);
      box-shadow: 2px 2px 0px var(--ink); appearance: none;
      background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="%23323232" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>');
      background-repeat: no-repeat; background-position: right 10px center; cursor: pointer; outline: none; transition: all 0.2s ease; transform: rotate(-0.5deg);
    }
    .doodle-select:focus { background-color: #fffdf5; box-shadow: 4px 4px 0px var(--accent-blue); border-color: var(--accent-blue); }

    .doodle-btn {
      font-family: var(--font-family); font-size: 18px; font-weight: bold; padding: 12px 20px; border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-radius-btn); cursor: pointer; transition: all 0.15s ease;
      display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; margin-top: 5px; letter-spacing: 1px; color: var(--ink); box-shadow: 3px 3px 0px var(--ink);
    }
    .btn-authorize { background-color: var(--action-color); transform: rotate(-1deg); }
    .btn-authorize:hover { background-color: var(--action-hover); transform: translateY(-2px) rotate(-2deg); box-shadow: 5px 5px 0px var(--ink); }
    .btn-authorize:active { transform: translate(3px, 3px) rotate(0deg); box-shadow: 0px 0px 0px transparent; }
    .btn-sent { background-color: var(--disabled-color); cursor: not-allowed; transform: rotate(1deg); box-shadow: 2px 2px 0px var(--ink); opacity: 0.9; }

    .rubber-stamp {
      position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%) rotate(-15deg) scale(3);
      font-family: var(--font-family); font-size: 32px; font-weight: 900; color: #ef476f;
      border: 4px solid #ef476f; padding: 5px 15px; border-radius: 8px; opacity: 0; pointer-events: none; z-index: 10; text-transform: uppercase; letter-spacing: 2px;
    }
    .doodle-card.completed .rubber-stamp { animation: stamp-down 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards; }
    @keyframes stamp-down { 0% { opacity: 0; transform: translate(-50%, -50%) rotate(-15deg) scale(3); } 50% { opacity: 1; transform: translate(-50%, -50%) rotate(-15deg) scale(0.9); } 100% { opacity: 0.9; transform: translate(-50%, -50%) rotate(-15deg) scale(1); } }

    .toast-msg {
      position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px);
      background-color: var(--ink); color: var(--bg-color); padding: 12px 24px;
      border-radius: var(--sketch-radius-2); font-family: var(--font-family); font-size: 18px; font-weight: bold;
      z-index: 1000; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      box-shadow: 4px 4px 0px var(--switch-bg); text-align: center; border: 2px solid var(--bg-color);
    }
    .toast-msg.show { transform: translateX(-50%) translateY(0); }

    .driver-info-box {
      background: rgba(255,255,255,0.6); border: 2px dashed var(--disabled-color); padding: 10px; border-radius: 10px; display: flex; align-items: center; gap: 10px; margin-top: 10px;
    }

    .web-dock-container {
      position: fixed; bottom: 30px; left: 0; right: 0;
      display: flex; justify-content: center; pointer-events: none; z-index: 1000;
    }
    .flock-dock {
      pointer-events: auto;
      background-color: #ffffff;
      border: 3px solid #2b2b2b;
      border-radius: 40px 50px 45px 55px / 55px 45px 50px 40px;
      box-shadow: 0px 10px 0px #2b2b2b, 0px 20px 30px rgba(0,0,0,0.2);
      display: flex; align-items: center; padding: 10px 15px;
      height: 85px; width: 90%; max-width: 650px; position: relative;
    }
    .nav-item {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-decoration: none; color: #2b2b2b; flex: 1; height: 100%; position: relative;
      z-index: 2; cursor: pointer; transition: transform 0.2s;
    }
    .icon-container { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; margin-bottom: 4px; }
    .nav-item svg {
      width: 28px; height: 28px; fill: none; stroke: #2b2b2b; stroke-width: 2.5;
      stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .nav-label { font-size: 12px; font-weight: 900; }
    .active-indicator {
      position: absolute; height: 70px; background-color: #ffe66d;
      border: 2px dashed #2b2b2b; border-radius: 30px 40px 30px 40px / 40px 30px 40px 30px;
      box-shadow: 2px 2px 0px #2b2b2b; top: 5px; z-index: 3;
      transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      animation: squish-dock 4s infinite alternate;
    }
    .nav-item.active { color: #2b2b2b; }
    .nav-item.active svg {
      transform: translateY(-5px) scale(1.15) rotate(-5deg);
      filter: drop-shadow(2px 2px 0px rgba(0,0,0,0.1));
    }
    .nav-item.logout svg { stroke: #ef476f; }
    .nav-item.logout .nav-label { color: #ef476f; }
    @keyframes squish-dock {
      0% { border-radius: 30px 40px 30px 40px / 40px 30px 40px 30px; }
      100% { border-radius: 40px 30px 40px 30px / 30px 40px 30px 40px; }
    }
    @media (max-width: 768px) {
      .flock-dock { width: 95%; height: 75px; padding: 5px; }
      .nav-item svg { width: 24px; height: 24px; }
      .nav-label { font-size: 10px; }
      .active-indicator { height: 60px; top: 5px; }
    }

    .order-card {
      background-color: var(--paper-front);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-radius-1);
      box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink);
      padding: 18px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .order-card:hover {
      transform: translateY(-2px) rotate(0.5deg);
      box-shadow: 6px 6px 0px var(--ink);
    }

    #orders-list {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }
    @media (min-width: 640px) {
      #orders-list { grid-template-columns: repeat(2, 1fr); }
    }

    #logistics-list {
      display: grid;
      grid-template-columns: 1fr;
      gap: 20px;
    }
    @media (min-width: 640px) {
      #logistics-list { grid-template-columns: repeat(2, 1fr); }
    }

    .empty-state { text-align: center; padding: 24px; color: var(--disabled-color); font-weight: bold; }
    .hidden { display: none !important; }
    .muted { color: var(--disabled-color); }
    .order-product-thumb { width: 72px; height: 72px; object-fit: contain; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); background: var(--bg-color); box-shadow: 2px 2px 0px var(--ink); cursor: pointer; }

    /* --- Orders Page Search & Modal --- */
    .orders-search-wrap {
      position: sticky;
      top: 10px;
      z-index: 50;
      display: flex;
      justify-content: center;
      margin-bottom: 15px;
    }
    .orders-search-box {
      display: flex;
      gap: 10px;
      width: 100%;
      max-width: 520px;
    }
    #orderSearchInput {
      flex: 1;
      height: 46px;
      padding: 0 14px;
      font-family: var(--font-ui);
      font-size: 15px;
      font-weight: bold;
      color: var(--ink);
      background-color: #fffbf0;
      border: 2.5px solid var(--ink);
      border-radius: 12px;
      box-shadow: 3px 3px 0px var(--ink);
      outline: none;
      transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    #orderSearchInput:focus {
      transform: translate(-2px, -2px);
      box-shadow: 5px 5px 0px var(--accent-blue);
      border-color: var(--accent-blue);
    }
    #orderSearchInput::placeholder { color: #888; font-weight: normal; }
    .order-search-btn {
      height: 46px;
      padding: 0 18px;
      font-family: var(--font-ui);
      font-size: 15px;
      font-weight: bold;
      color: var(--ink);
      background-color: var(--accent-blue);
      border: 2.5px solid var(--ink);
      border-radius: 12px;
      box-shadow: 3px 3px 0px var(--ink);
      cursor: pointer;
      outline: none;
      transition: all 0.15s ease;
    }
    .order-search-btn:active {
      transform: translate(2px, 2px);
      box-shadow: 0px 0px 0px transparent;
    }

    .order-card {
      background-color: var(--paper-front);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-radius-1);
      box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink);
      padding: 18px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .order-card:hover {
      transform: translateY(-2px) rotate(0.5deg);
      box-shadow: 6px 6px 0px var(--ink);
    }

    .holo-bar {
      position: relative;
      overflow: hidden;
      border-radius: 12px;
      border: 2px solid var(--ink);
      padding: 14px 16px;
      color: var(--ink);
      background: linear-gradient(120deg, #f6d365 0%, #ffb142 30%, #ffffff 48%, #ffb6ff 52%, #ffdf00 70%, #f6d365 100%);
      background-size: 220% 100%;
      animation: holo-shift 4s linear infinite;
      box-shadow: 0 4px 0px var(--ink);
    }
    @keyframes holo-shift {
      0% { background-position: 0% 0%; }
      100% { background-position: 220% 0%; }
    }
    .holo-bar .holo-title {
      font-family: var(--font-family);
      font-size: 20px;
      font-weight: bold;
    }
    .holo-bar .holo-meta {
      font-size: 13px;
      font-weight: bold;
      opacity: 0.9;
      margin-top: 4px;
    }

    #orderDetailsModal {
      position: fixed;
      inset: 0;
      z-index: 2000;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
    }
    #orderDetailsModal.active { display: flex; }
    #orderDetailsModal .modal-backdrop {
      position: absolute;
      inset: 0;
      background: rgba(0,0,0,0.45);
      animation: fade-in 0.2s ease;
    }
    #orderDetailsModal .modal-panel {
      position: relative;
      width: 100%;
      max-width: 520px;
      max-height: 85vh;
      overflow-y: auto;
      background: var(--paper-front);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-radius-1);
      box-shadow: 6px 6px 0px var(--ink);
      padding: 18px;
      z-index: 2;
      animation: modal-in 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    @keyframes modal-in {
      from { opacity: 0; transform: translateY(30px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    #orderDetailsModal .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px dashed rgba(50,50,50,0.2);
      padding-bottom: 10px;
      margin-bottom: 12px;
    }
    #orderDetailsModal .modal-title {
      font-family: var(--font-family);
      font-size: 20px;
      font-weight: bold;
    }
    #orderDetailsModal .modal-close {
      background: var(--bg-color);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--sketch-radius-btn);
      width: 34px;
      height: 34px;
      font-size: 18px;
      cursor: pointer;
      box-shadow: 2px 2px 0px var(--ink);
    }
    #orderDetailsModal .modal-body {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .order-item-row {
      display: flex;
      gap: 12px;
      align-items: center;
      padding: 10px;
      background: #fffbf0;
      border-radius: 12px;
      border: 2px solid rgba(0,0,0,0.08);
    }
    .order-item-thumb {
      width: 56px;
      height: 56px;
      object-fit: contain;
      border: 2px solid var(--ink);
      border-radius: 10px;
      background: var(--bg-color);
      box-shadow: 2px 2px 0px var(--ink);
    }
    .order-item-thumb-fallback {
      width: 56px;
      height: 56px;
      background: var(--bg-color);
      border: 2px solid var(--ink);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 22px;
      box-shadow: 2px 2px 0px var(--ink);
    }
    .order-total-box {
      background: #fffbf0;
      border: var(--border-width) solid var(--ink);
      border-radius: 12px;
      padding: 12px;
      text-align: center;
      box-shadow: 3px 3px 0px var(--ink);
    }
    .order-total-box .total-label {
      font-size: 14px;
      font-weight: bold;
      color: #444;
    }
    .order-total-box .total-value {
      font-family: var(--font-family);
      font-size: 26px;
      font-weight: bold;
      color: var(--ink);
    }

    /* --- Desktop Layout Additions --- */
    .desktop-container { width: 100%; max-width: 1100px; padding: 30px 20px; display: flex; flex-direction: column; gap: 25px; }
    .dashboard-grid { display: grid; grid-template-columns: 1fr; gap: 25px; align-items: start; }
    @media (min-width: 800px) { .dashboard-grid { grid-template-columns: 350px 1fr; } }
    .welcome-banner { background-color: #ffd166; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1); padding: 25px 30px; box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink); transform: rotate(-0.5deg); }
    .welcome-banner h2 { font-family: var(--font-family); font-size: 28px; margin-bottom: 8px; }
    .welcome-banner p { font-size: 18px; font-weight: bold; opacity: 0.8; }
    .overview-panel { background-color: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-2); padding: 25px 30px; box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink); transform: rotate(0.5deg); }
    .overview-title { font-family: var(--font-family); font-size: 24px; margin-bottom: 15px; }
    .overview-list { display: flex; flex-direction: column; gap: 12px; }
    .overview-item { display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: bold; color: #555; padding-bottom: 12px; border-bottom: 2px dashed rgba(0,0,0,0.15); }
    .overview-item:last-child { border-bottom: none; padding-bottom: 0; }
    .stat-number { font-family: var(--font-family); font-size: 22px; color: var(--accent-blue); font-weight: bold; }
    .orders-container { display: flex; flex-direction: column; gap: 20px; }
    .section-header { font-family: var(--font-family); font-size: 28px; margin-bottom: 5px; display: flex; align-items: center; gap: 10px; }

    .modal-overlay { position: fixed; inset: 0; z-index: 9999; display: none; align-items: center; justify-content: center; padding: 16px; }
    .modal-overlay.active { display: flex; }
    .modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
    .modal-panel { position: relative; width: 100%; max-width: 420px; max-height: 85vh; overflow-y: auto; background: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1); box-shadow: 6px 6px 0px var(--ink); padding: 18px; z-index: 2; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed rgba(50,50,50,0.2); padding-bottom: 10px; margin-bottom: 10px; }
    .modal-title { font-family: var(--font-family); font-size: 20px; font-weight: bold; }
    .modal-close { background: var(--bg-color); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); width: 34px; height: 34px; font-size: 18px; cursor: pointer; box-shadow: 2px 2px 0px var(--ink); }
    .modal-body { display: flex; flex-direction: column; gap: 12px; }
    .modal-img { width: 100%; max-height: 220px; object-fit: contain; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); background: var(--bg-color); box-shadow: 3px 3px 0px var(--ink); }
    .modal-meta { display: flex; flex-direction: column; gap: 8px; }
    .modal-row { display: flex; justify-content: space-between; }
    .modal-label { font-family: var(--font-family); font-weight: bold; }
    .modal-value { font-weight: bold; }
    .modal-actions { display: flex; flex-direction: column; gap: 8px; margin-top: 4px; }
    /* --- STREAMING_CHUNK: Low Stock Alerts --- */
    .alert-card {
      background-color: #fff5f5;
      border: var(--border-width) solid var(--danger);
      border-radius: var(--sketch-radius-1);
      padding: 16px 20px;
      box-shadow: 4px 4px 0px var(--danger);
      display: flex;
      align-items: center;
      gap: 12px;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .alert-card:hover { transform: translateY(-2px) rotate(0.5deg); box-shadow: 6px 6px 0px var(--danger); }
    .alert-icon-box {
      width: 44px; height: 44px;
      background: #fff;
      border: 2px solid var(--danger);
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 22px;
      box-shadow: 2px 2px 0px var(--danger);
      transform: rotate(-5deg);
      flex-shrink: 0;
    }
    .alert-info { flex: 1; min-width: 0; }
    .alert-product-name {
      font-family: var(--font-family);
      font-size: 18px;
      font-weight: bold;
      color: var(--danger);
      margin-bottom: 2px;
    }
    .alert-meta { font-size: 14px; font-weight: bold; color: #666; }
    .alert-meta .qty-critical { color: var(--danger); font-size: 16px; }
    @media (min-width: 768px) {
      body {
        display: block;
        overflow: auto;
      }
      .mobile-container {
        max-width: 100%;
        max-height: none;
        height: auto;
        min-height: 100vh;
        border: none;
        box-shadow: none;
        border-radius: 0;
        overflow: visible;
      }
    }
  </style>
</head>
<body>
    <script>window.__USER_ROLE__=<?php echo json_encode($role); ?>;window.__USER_NAME__=<?php echo json_encode($user['full_name'] ?? ''); ?>;</script>

    <div id="toast" class="toast-msg">Action triggered!</div>

    <div class="mobile-container">
        <header class="header">
            <h1 class="page-title" id="headerTitle">Orders Hub</h1>
            <div style="display:flex;align-items:center;gap:10px;">
              <label class="theme-toggle" title="Toggle theme">
                <input type="checkbox" id="themeToggle">
                <span class="toggle-track"><span class="toggle-thumb"></span></span>
              </label>
              <div class="avatar" title="Provider Profile: <?php echo htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($providerInitials, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </header>

        <main class="content-area" id="main">
            <!-- View: Home -->
            <div id="view-home" class="spa-view active">
                <div class="desktop-container">
                    <section class="welcome-banner">
                        <h2>Welcome back, <?php echo htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8'); ?>!</h2>
                        <p>Here's what's happening with your supply chain today.</p>
                    </section>

                    <div class="dashboard-grid">
                        <div style="display: flex; flex-direction: column; gap: 25px;">
                            <aside class="overview-panel">
                                <h3 class="overview-title">Inventory Health</h3>
                                <div class="overview-list">
                                    <div class="overview-item">
                                        <span>Low Stock Alerts</span>
                                        <span class="stat-number" id="countAlerts" style="color: var(--danger);">0</span>
                                    </div>
                                    <div class="overview-item">
                                        <span>Healthy Products</span>
                                        <span class="stat-number" id="countHealthy" style="color: var(--primary);">0</span>
                                    </div>
                                    <div class="overview-item">
                                        <span>Total Categories</span>
                                        <span class="stat-number">4</span>
                                    </div>
                                </div>
                            </aside>

                            <aside class="overview-panel" style="background-color: var(--paper); transform: rotate(-0.5deg);">
                                <h3 class="overview-title">Orders Overview</h3>
                                <div class="overview-list">
                                    <div class="overview-item">
                                        <span>Pending Dispatch</span>
                                        <span class="stat-number" style="color: #ff9f1c;">0</span>
                                    </div>
                                    <div class="overview-item">
                                        <span>In Transit</span>
                                        <span class="stat-number" style="color: var(--action-blue);">0</span>
                                    </div>
                                    <div class="overview-item">
                                        <span>Delivered Today</span>
                                        <span class="stat-number" style="color: var(--primary);">0</span>
                                    </div>
                                </div>
                            </aside>
                        </div>

                        <main class="orders-container" id="alertsList">
                            <div id="low-stock-section">
                                <div class="empty-state" style="display: block;">Loading low stock alerts...</div>
                            </div>

                            <div id="home-stats" class="empty-state" style="display: none;">Loading stats...</div>
                        </main>
                    </div>
                </div>
            </div>

            <!-- View: Orders -->
            <div id="view-orders" class="spa-view">
                <div class="orders-search-wrap">
                    <div class="orders-search-box">
                        <input type="text" id="orderSearchInput" placeholder="�f°�.¸â�,��, Search Past Orders (Try ORD-20260705-114700-634)" autocomplete="off" spellcheck="false">
                        <button class="order-search-btn" onclick="searchOrder()">Search</button>
                    </div>
                </div>

                <div id="orders-loading" class="empty-state hidden">Loading pending orders...</div>
                <div id="orders-error" class="empty-state hidden" style="color:#ef4444;"></div>
                <div id="orders-list"></div>
            </div>

            <!-- View: Logistics -->
            <div id="view-logistics" class="spa-view">
                <div id="logistics-loading" class="empty-state">Loading tracking data...</div>
                <div id="logistics-error" class="empty-state" style="color:#ef4444;"></div>
                <div id="logistics-list"></div>
            </div>

            <!-- View: IoT -->
            <div id="view-iot" class="spa-view">
                <iframe src="iot_spa.html" title="IoT Monitoring" style="flex-grow: 1; border: none; border-radius: 0; box-shadow: none; background-color: var(--bg-color); width: 100%; height: 100%; min-height: 100vh; display: block;" sandbox="allow-scripts allow-same-origin allow-forms allow-modals allow-popups"></iframe>
            </div>

            <!-- View: Verify QR -->
            <div id="view-verify" class="spa-view">
                <div class="doodle-card" style="transform: rotate(-0.5deg);">
                    <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
                        <span class="order-id" style="font-size: 20px;">QR Scanner</span>
                    </div>
                    <p style="font-size: 14px; color: var(--disabled-color);">Point your camera at a product QR code to verify authenticity and check inventory.</p>
                </div>
                <iframe src="qr_clean.php" title="QR Scanner" style="flex-grow: 1; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1); box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink); background-color: var(--bg-color); width: 100%; height: 100%; min-height: 420px; display: block;" sandbox="allow-scripts allow-same-origin allow-forms allow-modals allow-popups" allow="camera"></iframe>
            </div>

            <!-- View: Settings -->
            <div id="view-settings" class="spa-view">
                <div class="doodle-card" style="transform: rotate(-1deg);">
                    <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
                        <span class="order-id" style="font-size: 20px;">Provider Profile</span>
                    </div>
                    <div class="card-details">
                        <div class="detail-row"><span class="detail-label">Name</span><span class="detail-value"><?php echo htmlspecialchars($providerName, ENT_QUOTES, 'UTF-8'); ?></span></div>
                        <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-value"><?php echo htmlspecialchars($user['phone'] ?? 'Not set', ENT_QUOTES, 'UTF-8'); ?></span></div>
                    </div>
                </div>
                <div class="doodle-card" style="transform: rotate(0.5deg);">
                    <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
                        <span class="order-id" style="font-size: 20px;">Preferences</span>
                    </div>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <a href="settings.php" class="doodle-btn" style="background-color: var(--bg-color); text-decoration: none; transform: rotate(-0.5deg);">
                            <i class="fa-solid fa-gear" style="font-size: 18px;"></i>
                            Open Full Settings
                        </a>
                        <a href="auth/logout.php" class="doodle-btn" style="background-color: #ef476f; color: white; text-decoration: none; transform: rotate(0.5deg);" onclick="event.preventDefault(); if(confirm('Log out?')) window.location.href='auth/logout.php';">
                            <i class="fa-solid fa-arrow-right-from-bracket" style="font-size: 18px;"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <div class="web-dock-container">
            <nav class="flock-dock" id="bottomNav">
                <div class="active-indicator" id="activeIndicator"></div>
                <a href="#" class="nav-item active" data-target="home">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <span class="nav-label">Home</span>
                </a>
                <a href="#" class="nav-item" data-target="verify">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg>
                    </div>
                    <span class="nav-label">Verify QR</span>
                </a>
                <a href="#" class="nav-item" data-target="orders">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <span class="nav-label">Orders</span>
                </a>
                <a href="#" class="nav-item" data-target="logistics">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                    </div>
                    <span class="nav-label">Logistics</span>
                </a>
                <a href="inventory.php" class="nav-item" data-target="inventory">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    </div>
                    <span class="nav-label">Inventory</span>
                </a>
                <a href="#" class="nav-item" data-target="iot">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path><path d="M12 6v6l4 2"></path></svg>
                    </div>
                    <span class="nav-label">IoT</span>
                </a>

                <a href="#" class="nav-item" data-target="settings">
                    <div class="icon-container">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2 v -.09 A 1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l-.06.06a1.65 1.65 0 0 0-.33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    </div>
                    <span class="nav-label">Settings</span>
                </a>
            </nav>
        </div>
    </div>

  <script src="js/loading.js"></script>
  <script>
    function showToast(message) {
      const toast = document.getElementById('toast');
      toast.innerText = message;
      toast.classList.add('show');
      setTimeout(() => toast.classList.remove('show'), 2500);
    }

    async function syncToLive() {
      showToast('Syncing to live...');
      try {
        const res = await fetch('api/sync_to_live.php', { method: 'POST', credentials: 'same-origin' });
        const data = await res.json();
        if (!data.ok) throw new Error(data.error || 'Sync failed');
        showToast(`Synced: ${data.created} created, ${data.skipped} skipped, ${data.qty_updated} qty updated`);
        if (data.errors && data.errors.length) console.warn('[syncToLive] errors:', data.errors);
      } catch (e) {
        showToast(e.message || 'Sync failed');
        console.error('[syncToLive] failed:', e);
      }
    }

    document.addEventListener('DOMContentLoaded', () => {
      const navItems = document.querySelectorAll('.nav-item');
      const indicator = document.getElementById('activeIndicator');
      const dock = document.getElementById('bottomNav');
      const headerTitle = document.getElementById('headerTitle');
      const views = document.querySelectorAll('.spa-view');

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

      const initialActive = document.querySelector('.nav-item.active');
      if (initialActive) setTimeout(() => updateIndicator(initialActive, true), 50);

      let logisticsRefreshInterval = null;

      function startLogisticsRefresh() {
        stopLogisticsRefresh();
        logisticsRefreshInterval = setInterval(() => {
          const activeItem = document.querySelector('.nav-item.active');
          if (activeItem && activeItem.getAttribute('data-target') === 'logistics') {
            loadProviderLogistics();
          }
        }, 10000);
      }

      function stopLogisticsRefresh() {
        if (logisticsRefreshInterval) {
          clearInterval(logisticsRefreshInterval);
          logisticsRefreshInterval = null;
        }
      }

      navItems.forEach(item => {
        item.addEventListener('click', function(e) {
          const href = this.getAttribute('href');
          const target = this.getAttribute('data-target');

          if (href && href !== '#') return;
          e.preventDefault();
          if(this.classList.contains('active')) return;

          navItems.forEach(nav => nav.classList.remove('active'));
          this.classList.add('active');
          updateIndicator(this);

           if(target === 'orders') headerTitle.innerText = 'Orders Hub';
           else if(target === 'logistics') headerTitle.innerText = 'Logistics Tracking';
           else if(target === 'verify') headerTitle.innerText = 'Verify QR';
           else if(target === 'iot') headerTitle.innerText = 'IoT Monitoring';
           else headerTitle.innerText = target.charAt(0).toUpperCase() + target.slice(1);

          const targetView = document.getElementById(`view-${target}`);
          if(targetView) {
            views.forEach(v => v.classList.remove('active'));
            targetView.classList.add('active');
            if (target === 'home') {
              loadHomeStats();
              loadLowStockAlerts();
            }
            if (target === 'orders') loadPendingOrders();
            if (target === 'logistics') loadProviderLogistics();
          }

          if (target === 'logistics') startLogisticsRefresh();
          else stopLogisticsRefresh();
        });
      });

      if (initialActive) {
        const target = initialActive.getAttribute('data-target');
        if (target === 'home') {
          loadHomeStats();
          loadLowStockAlerts();
        }
        if (target === 'orders') loadPendingOrders();
        if (target === 'logistics') loadProviderLogistics();
      }
    });

    function getCsrfToken() {
      const meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    function escapeHtml(str) {
      return String(str ?? '').replace(/[&<>"']/g, (m) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
    }

    async function loadHomeStats() {
      const container = document.getElementById('home-stats');
      if (!container) return;
      container.innerHTML = '<div class="empty-state">Loading stats...</div>';

      try {
        const form = new URLSearchParams();
        form.set('action', 'home_stats');
        form.set('csrf_token', getCsrfToken());

        const res = await window.sfsWithLoading(fetch('api/provider_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        }));

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

        container.innerHTML = `
          <div class="doodle-card" style="transform: rotate(0.5deg);">
            <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
              <span class="order-id" style="font-size: 20px;">Overview</span>
            </div>
            <div class="card-details">
              <div class="detail-row"><span class="detail-label">Pending Orders</span><span class="detail-value">${escapeHtml(String(json.pending_count ?? '0'))}</span></div>
              <div class="detail-row"><span class="detail-label">Your Assigned</span><span class="detail-value">${escapeHtml(String(json.assigned_count ?? '0'))}</span></div>
              <div class="detail-row"><span class="detail-label">Logistics Team</span><span class="detail-value">${escapeHtml(String(json.drivers_count ?? '0'))}</span></div>
            </div>
          </div>
        `;
      } catch (e) {
        container.innerHTML = '<div class="empty-state" style="color:#ef4444;">' + escapeHtml(e.message || 'Failed to load stats') + '</div>';
      }
    }

    window.__providerOrderItems = {};

    async function fetchOrderItems(orderId) {
      if (window.__providerOrderItems[orderId]) return window.__providerOrderItems[orderId];
      try {
        const form = new URLSearchParams();
        form.set('action', 'get_items');
        form.set('order_id', orderId);
        form.set('csrf_token', getCsrfToken());
        const res = await fetch('api/provider_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) return [];
        const items = json.items || [];
        window.__providerOrderItems[orderId] = items;
        return items;
      } catch (e) {
        return [];
      }
    }

    async function loadPendingOrders() {
      const listEl = document.getElementById('orders-list');
      const loadingEl = document.getElementById('orders-loading');
      const errorEl = document.getElementById('orders-error');
      listEl.innerHTML = '';
      loadingEl.classList.remove('hidden');
      errorEl.classList.add('hidden');

      try {
        const form = new URLSearchParams();
        form.set('action', 'list_pending');
        form.set('csrf_token', getCsrfToken());

        const res = await window.sfsWithLoading(fetch('api/provider_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        }));

        const rawText = await res.clone().text();
        console.log('[provider_dashboard] Raw API response:', rawText.slice(0, 300));
        
        const json = await res.json().catch((e) => {
          console.error('[provider_dashboard] JSON parse error:', e, 'raw:', rawText.slice(0, 300));
          return {};
        });
        if (!res.ok || !json.ok) {
          let bodyText = '';
          try { bodyText = await res.clone().text(); } catch (e) {}
          console.error('[provider_dashboard] API error response', {
            status: res.status,
            statusText: res.statusText,
            headers: Object.fromEntries(res.headers.entries()),
            json,
            bodyText: bodyText.slice(0, 500)
          });
          throw new Error(json.error || bodyText || rawText || ('HTTP ' + res.status));
        }
        const orders = json.orders || [];
        const drivers = json.drivers || [];

        if (!orders.length) {
          listEl.innerHTML = '<div class="empty-state">No pending orders right now.</div>';
          return;
        }

        const driverOptions = drivers.map(d => {
          const isOnDuty = (d.duty_status || 'OFF_DUTY') === 'ON_DUTY';
          const badge = isOnDuty ? ' �f°�.¸�.¸�,¢' : ' �f°�.¸â�,��,´';
          const disabled = isOnDuty ? '' : ' disabled';
          const label = (d.full_name || '').trim() || 'Unnamed';
          return `<option value="${escapeHtml(String(d.id))}"${disabled}>${escapeHtml(label)}${badge}</option>`;
        }).join('');

        orders.forEach(order => {
          const card = document.createElement('div');
          card.className = 'doodle-card order-card';
          card.style.transform = 'rotate(0.5deg)';
          card.setAttribute('data-order', JSON.stringify(order));
          card.setAttribute('data-order-id', escapeHtml(order.order_id));
          const imgSrc = (order.logo_path || '').trim();
          const imgHtml = imgSrc ? `<img src="${escapeHtml(imgSrc)}" alt="Product image" class="order-product-thumb" onclick="openProductModal(this.closest('.doodle-card').getAttribute('data-order'))" />` : '';
          card.innerHTML = `
            <div class="rubber-stamp">Approved</div>
            <div class="card-header">
              <span class="order-id">#${escapeHtml(order.order_id)}</span>
              <span class="badge badge-pending">Review</span>
            </div>
            <div class="card-details">
              <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value">${escapeHtml(order.delivery_address || order.customer_name || '�f¢â�?s¬â�,�')}</span></div>
              <div class="detail-row"><span class="detail-label">Item</span><span class="detail-value" style="text-decoration: underline; cursor: pointer;" onclick="openProductModal(this.closest('.doodle-card').getAttribute('data-order'))">${escapeHtml(order.product_name || 'Product')}</span></div>
            </div>
            <div class="card-products-row" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
              ${imgHtml ? `<div style="position: relative;">${imgHtml}</div>` : ''}
              <button class="doodle-btn btn-view-products" style="display: none; padding: 6px 10px; font-size: 12px;" onclick="openProviderProductsModal('${escapeHtml(order.order_id)}')">Products</button>
            </div>
            <div style="margin-top: 5px;">
              <select class="doodle-select driver-assignment" data-order="${escapeHtml(order.order_id)}">
                <option value="" disabled selected>Assign Logistics User...</option>
                ${driverOptions || '<option value="" disabled>No drivers available</option>'}
              </select>
            </div>
            <button class="doodle-btn btn-authorize" onclick="openAuthorizeModal(this)">
              <svg width="20" height="20" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><polyline points="22 4 12 14.01 9 11.01" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
              Authorize & Assign
            </button>
          `;
          listEl.appendChild(card);
        });

        // Fetch all items for each order and update cards with up to 2 thumbnails + Products button
        const pendingItemPromises = orders.map(order => fetchOrderItems(order.order_id));
        const pendingAllItems = await Promise.all(pendingItemPromises);
        orders.forEach((order, idx) => {
          const items = pendingAllItems[idx] || [];
          const card = listEl.querySelector(`[data-order-id="${escapeHtml(order.order_id)}"]`);
          if (!card || !items.length) return;
          const row = card.querySelector('.card-products-row');
          const viewBtn = card.querySelector('.btn-view-products');
          if (!row) return;
          const thumbs = items.slice(0, 2).map(it => {
            const src = (it.logo_path || '').trim();
            return src ? `<img src="${escapeHtml(src)}" alt="Product image" class="order-product-thumb" onclick="openProviderProductsModal('${escapeHtml(order.order_id)}')" />` : '';
          }).join('');
          row.innerHTML = thumbs;
          if (items.length > 2 && viewBtn) {
            viewBtn.style.display = 'inline-flex';
            viewBtn.textContent = 'Products (' + items.length + ')';
          }
        });
      } catch (e) {
        console.error('[provider_dashboard] loadPendingOrders failed:', e);
        errorEl.textContent = e.message || 'Failed to load orders';
        errorEl.classList.remove('hidden');
      } finally {
        loadingEl.classList.add('hidden');
      }
    }

    async function authorizeOrder(button) {
      const card = button.closest('.doodle-card');
      const select = card.querySelector('.driver-assignment');
      const orderId = select.getAttribute('data-order');

      if (!select.value) {
        showToast("�f¢�.¡�, �f¯�,¸�, Please select a Logistics User first!");
        select.style.borderColor = "var(--warning-color)";
        select.style.boxShadow = "4px 4px 0px var(--warning-color)";
        setTimeout(() => { select.style.borderColor = "var(--ink)"; select.style.boxShadow = "2px 2px 0px var(--ink)"; }, 2000);
        return;
      }

      const selectedOption = select.options[select.selectedIndex];
      const driverNameRaw = selectedOption ? (selectedOption.textContent || '') : '';
      const driverName = driverNameRaw.replace(/[\u{1F534}\u{1F7E2}]/u, '').trim() || driverNameRaw.trim();
      const isOffDuty = driverNameRaw.includes('�f°�.¸â�,��,´');
      if (isOffDuty) {
        showToast("�f¢�.¡�, �f¯�,¸�, Selected driver is off duty. Please choose an on-duty driver.");
        select.style.borderColor = "var(--warning-color)";
        select.style.boxShadow = "4px 4px 0px var(--warning-color)";
        setTimeout(() => { select.style.borderColor = "var(--ink)"; select.style.boxShadow = "2px 2px 0px var(--ink)"; }, 2000);
        return;
      }

      try {
        const form = new URLSearchParams();
        form.set('action', 'assign_driver');
        form.set('order_id', orderId);
        form.set('driver_id', select.value);
        form.set('driver_name', driverName);
        form.set('csrf_token', getCsrfToken());

        const res = await window.sfsWithLoading(fetch('api/provider_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        }));

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

        card.classList.add('completed');
        const badge = card.querySelector('.badge');
        badge.className = 'badge badge-done';
        badge.innerText = 'Assigned';
        if(card.style.backgroundColor === 'rgb(255, 209, 102)') badge.style.backgroundColor = 'var(--disabled-color)';

        button.className = 'doodle-btn btn-sent';
        button.innerHTML = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M5 12l5 5L20 7"></path></svg> Assigned to ${escapeHtml(driverName)}`;
        button.disabled = true;
        select.disabled = true;

        showToast(`�f¢�.�?oâ�,�¦ Order authorized & assigned to ${driverName}`);
        closeAuthorizeModal();
      } catch (e) {
        showToast('Assignment failed: ' + (e.message || 'Unknown error'));
      }
    }

    async function loadProviderLogistics() {
      const listEl = document.getElementById('logistics-list');
      const loadingEl = document.getElementById('logistics-loading');
      const errorEl = document.getElementById('logistics-error');
      listEl.innerHTML = '';
      loadingEl.classList.remove('hidden');
      errorEl.classList.add('hidden');

      try {
        const form = new URLSearchParams();
        form.set('action', 'list');
        form.set('csrf_token', getCsrfToken());

        const res = await window.sfsWithLoading(fetch('api/provider_logistics.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        }));

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));
        const orders = (json.orders || []).filter(order => {
          const status = (order.status || '').toUpperCase();
          return status !== 'DELIVERED' && status !== 'RETURNED';
        });

        if (!orders.length) {
          listEl.innerHTML = '<div class="empty-state">No active logistics orders yet.</div>';
          return;
        }

        orders.forEach(order => {
          const isDelivered = order.status === 'DELIVERED';
          const isTransit = order.status === 'IN_TRANSIT';
          const card = document.createElement('div');
          card.className = 'doodle-card order-card' + (isDelivered ? ' completed' : '');
          card.style.transform = 'rotate(0.5deg)';
          const imgSrc = (order.logo_path || '').trim();
          const imgHtml = imgSrc ? `<img src="${escapeHtml(imgSrc)}" alt="Product image" class="order-product-thumb" onclick="openProductModal(this.closest('.doodle-card').getAttribute('data-order'))" />` : '';
          card.setAttribute('data-order', JSON.stringify(order));
          card.setAttribute('data-order-id', escapeHtml(order.order_id));
          card.innerHTML = `
            <div class="card-header">
              <span class="order-id">#${escapeHtml(order.order_id)}</span>
              <span class="badge ${isDelivered ? 'badge-done' : (isTransit ? 'badge-transit' : 'badge-pending')}">${escapeHtml(order.status_label || order.status)}</span>
            </div>
            <div class="card-details">
              <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-value">${escapeHtml(order.delivery_address || '�f¢â�?s¬â�,�')}</span></div>
              <div class="detail-row"><span class="detail-label">Item</span><span class="detail-value" style="text-decoration: underline; cursor: pointer;" onclick="openProductModal(this.closest('.doodle-card').getAttribute('data-order'))">${escapeHtml(order.product_name || 'Product')}</span></div>
            </div>
            <div class="card-products-row" style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 6px;">
              ${imgHtml ? `<div style="position: relative;">${imgHtml}</div>` : ''}
              <button class="doodle-btn btn-view-products" style="display: none; padding: 6px 10px; font-size: 12px;" onclick="openProviderProductsModal('${escapeHtml(order.order_id)}')">Products</button>
            </div>
            <div class="driver-info-box" style="${isDelivered ? 'opacity:0.7' : ''}">
              <div style="font-size: 30px;">${isDelivered ? '�f¢�.�?oâ�,�¦' : (isTransit ? '�f°�.¸�.¡�.¡' : '�f°�.¸â�,��"�,¦')}</div>
              <div style="flex-grow: 1;">
                <div style="font-weight: bold; font-family: var(--font-family); font-size: 18px;">Driver: ${escapeHtml(order.assigned_driver_name || 'Unassigned')}</div>
                <div style="font-size: 14px; color: var(--disabled-color);">${isDelivered ? 'Delivered' : (isTransit ? 'In Transit' : 'Assigned')}</div>
              </div>
            </div>
          `;
          listEl.appendChild(card);
        });

        const logisticsItemPromises = orders.map(order => fetchOrderItems(order.order_id));
        const logisticsAllItems = await Promise.all(logisticsItemPromises);
        orders.forEach((order, idx) => {
          const items = logisticsAllItems[idx] || [];
          const card = listEl.querySelector(`[data-order-id="${escapeHtml(order.order_id)}"]`);
          if (!card || !items.length) return;
          const row = card.querySelector('.card-products-row');
          const viewBtn = card.querySelector('.btn-view-products');
          if (!row) return;
          const thumbs = items.slice(0, 2).map(it => {
            const src = (it.logo_path || '').trim();
            return src ? `<img src="${escapeHtml(src)}" alt="Product image" class="order-product-thumb" onclick="openProviderProductsModal('${escapeHtml(order.order_id)}')" />` : '';
          }).join('');
          row.innerHTML = thumbs;
          if (items.length > 2 && viewBtn) {
            viewBtn.style.display = 'inline-flex';
            viewBtn.textContent = 'Products (' + items.length + ')';
          }
        });
      } catch (e) {
        console.error('[provider_dashboard] loadProviderLogistics failed:', e);
        errorEl.textContent = e.message || 'Failed to load logistics data';
        errorEl.classList.remove('hidden');
      } finally {
        loadingEl.classList.add('hidden');
      }
    }

    window.authorizeOrder = authorizeOrder;
    window.startRoute = function() {};

    function openProductModal(orderJson) {
      const modal = document.getElementById('productModal');
      if (!modal) return;
      let order = orderJson;
      if (typeof orderJson === 'string') {
        try { order = JSON.parse(orderJson); } catch (e) { console.warn('Invalid order JSON'); return; }
      }
      const img = document.getElementById('modalProductImage');
      const title = document.getElementById('modalProductTitle');
      const category = document.getElementById('modalProductCategory');
      const sku = document.getElementById('modalProductSku');
      const size = document.getElementById('modalProductSize');
      const price = document.getElementById('modalProductPrice');
      const qty = document.getElementById('modalProductQty');
      const desc = document.getElementById('modalProductDesc');

      if (img) img.src = order.logo_path ? order.logo_path : '';
      if (title) title.textContent = order.product_name || 'Product';
      if (category) category.textContent = order.category || '';
      if (sku) sku.textContent = order.sku ? 'SKU: ' + order.sku : 'SKU: �f¢â�?s¬â�,�';
      if (size) size.textContent = order.size ? 'Size: ' + order.size : 'Size: �f¢â�?s¬â�,�';
      if (price) price.textContent = order.price ? '�f¢â�,�š�,¹' + Number(order.price).toFixed(2) : '�f¢â�,�š�,¹0.00';
      if (qty) qty.textContent = order.qty ? 'Qty: ' + order.qty : 'Qty: �f¢â�?s¬â�,�';
      if (desc) desc.textContent = order.description || '';

      modal.classList.add('active');
    }

    function closeProductModal() {
      const modal = document.getElementById('productModal');
      if (modal) modal.classList.remove('active');
    }

    function openAuthorizeModal(button) {
      const modal = document.getElementById('authorizeModal');
      if (!modal) return;
      const card = button.closest('.doodle-card');
      const select = card ? card.querySelector('.driver-assignment') : null;
      const driverNameRaw = select ? (select.options[select.selectedIndex]?.textContent || '') : '';
      const driverName = driverNameRaw.replace(/[\u{1F534}\u{1F7E2}]/u, '').trim() || driverNameRaw.trim();
      const driverId = select ? select.value : '';
      const orderId = select ? select.getAttribute('data-order') : '';
      const confirmBtn = document.getElementById('confirmAuthorizeBtn');

      document.getElementById('authorizeOrderId').textContent = orderId;
      document.getElementById('authorizeDriverName').textContent = driverName || 'Not selected';

      if (confirmBtn) {
        confirmBtn.onclick = () => {
          closeAuthorizeModal();
          authorizeOrder(button);
        };
      }

      modal.classList.add('active');
    }

    function closeAuthorizeModal() {
      const modal = document.getElementById('authorizeModal');
      if (modal) modal.classList.remove('active');
    }

    window.openProductModal = openProductModal;
    window.closeProductModal = closeProductModal;
    window.openAuthorizeModal = openAuthorizeModal;
    window.closeAuthorizeModal = closeAuthorizeModal;

    window.openProviderProductsModal = async function(orderId) {
      const modal = document.getElementById('providerProductsModal');
      const list = document.getElementById('provider-products-list');
      if (!modal || !list) return;
      const items = await fetchOrderItems(orderId);
      if (!items.length) {
        list.innerHTML = '<div class="empty-state">No products found.</div>';
      } else {
        list.innerHTML = items.map(it => `
          <div class="doodle-card" style="transform: none; box-shadow: none; margin-bottom: 8px;">
            <div style="display: flex; gap: 10px; align-items: center;">
              ${it.logo_path ? `<img src="${escapeHtml(it.logo_path)}" alt="" style="width: 48px; height: 48px; object-fit: contain; border-radius: 4px; background: #fff;" onerror="this.style.display='none'" />` : ''}
              <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 900; font-size: 14px;">${escapeHtml(it.name || 'Product')}</div>
                <div style="font-size: 12px; color: var(--disabled-color);">SKU: ${escapeHtml(it.sku || '�f¢â�?s¬â�,�')} �f�?s�,· Qty: ${escapeHtml(it.qty || '�f¢â�?s¬â�,�')}</div>
              </div>
            </div>
          </div>
        `).join('');
      }
      modal.classList.add('active');
    };

    window.closeProviderProductsModal = function() {
      const modal = document.getElementById('providerProductsModal');
      if (modal) modal.classList.remove('active');
    };

    /* --- Low Stock Alerts --- */
    let activeAlertCardId = null;

    function getCategoryEmoji(category) {
      const map = { vaccines: '�f°�.¸â�,��"�â�,�°', layer_meds: '�f°�.¸â�,��"��. ', feed_meds: '�f°�.¸�,§�,ª', water: '�f°�.¸â�,��"��,§', meds: '�f°�.¸â�,��"��. ', supplies: '�f°�.¸â�,��"�,¦' };
      return map[category] || '�f¢�.¡�, �f¯�,¸�,';
    }

    async function loadLowStockAlerts() {
      const container = document.getElementById('low-stock-section');
      if (!container) return;
      container.innerHTML = '<div class="empty-state">Loading low stock alerts...</div>';

      try {
        const form = new URLSearchParams();
        form.set('action', 'low_stock');
        form.set('csrf_token', getCsrfToken());

        const res = await fetch('api/provider_low_stock.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

        const alerts = json.alerts || [];
        if (!alerts.length) {
          container.innerHTML = '<div class="empty-state">�f°�.¸�.½â�,�° All products are well-stocked!</div>';
          return;
        }

        container.innerHTML = `
          <div class="doodle-card" style="transform: rotate(0.5deg);">
            <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
              <span class="order-id" style="font-size: 20px;">Low Stock Alerts</span>
              <span style="font-size: 14px; font-weight: bold; color: var(--danger);">${alerts.length} need restock</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
              ${alerts.map(a => `
                <div class="alert-card" id="alert-${escapeHtml(String(a.product_id))}" onclick="openInventoryAdjust('${escapeHtml(String(a.product_id))}', '${escapeHtml(a.name)}', '${escapeHtml(String(a.qty))}')">
                  <div class="alert-icon-box">${getCategoryEmoji(a.category)}</div>
                  <div class="alert-info">
                    <div class="alert-product-name">${escapeHtml(a.name)}</div>
                    <div class="alert-meta">
                      ${a.category ? 'Category: ' + escapeHtml(a.category) + '<br>' : ''}
                      Stock: <span class="qty-critical">${escapeHtml(String(a.qty))} units</span> (Critical �f¢â�?s¬â�,� below 5)
                    </div>
                  </div>
                </div>
              `).join('')}
            </div>
          </div>
        `;
      } catch (e) {
        container.innerHTML = '<div class="empty-state" style="color:#ef4444;">' + escapeHtml(e.message || 'Failed to load alerts') + '</div>';
      }
    }

    function openInventoryAdjust(productId, productName, currentQty) {
      activeAlertCardId = productId;
      document.getElementById('adjProductName').textContent = productName;
      document.getElementById('adjCurrentQty').textContent = currentQty;
      document.getElementById('adjNewQty').value = '';
      const modal = document.getElementById('inventoryAdjustModal');
      if (modal) modal.setAttribute('hidden', '');
      modal.classList.add('active');
      setTimeout(() => document.getElementById('adjNewQty').focus(), 100);
    }

    function closeInventoryAdjustModal() {
      const modal = document.getElementById('inventoryAdjustModal');
      if (modal) {
        modal.classList.remove('active');
        modal.setAttribute('hidden', '');
      }
      activeAlertCardId = null;
    }

    async function saveStock() {
      const addedQty = parseInt(document.getElementById('adjNewQty').value || '0', 10);
      const currentQty = parseInt(document.getElementById('adjCurrentQty').textContent || '0', 10);
      const productId = activeAlertCardId;

      if (!productId) {
        showToast('�f¢�.¡�, �f¯�,¸�, No product selected!');
        return;
      }

      if (addedQty <= 0) {
        showToast('�f¢�.¡�, �f¯�,¸�, Enter a valid restock amount!');
        return;
      }

      try {
        const form = new FormData();
        form.set('product_id', String(productId));
        form.set('qty', String(addedQty));
        form.set('inv_action', 'add');
        form.set('csrf_token', getCsrfToken());

        const res = await fetch('api/inventory_upsert.php', {
          method: 'POST',
          body: form,
          credentials: 'same-origin',
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.ok) throw new Error(data.error || ('HTTP ' + res.status));

        const totalQty = currentQty + addedQty;

        if (totalQty < 5) {
          showToast(`�f¢�.¡�, �f¯�,¸�, Restocked to ${totalQty}, still below safe threshold!`);
          const card = document.getElementById('alert-' + productId);
          if (card) {
            const qtySpan = card.querySelector('.qty-critical');
            if (qtySpan) qtySpan.textContent = totalQty + ' units';
          }
          return;
        }

        const card = document.getElementById('alert-' + productId);
        if (card) {
          card.style.transform = 'scale(0.95) translateY(10px) rotate(2deg)';
          card.style.opacity = '0';
          card.style.pointerEvents = 'none';
          setTimeout(() => { card.style.display = 'none'; }, 300);
        }

        closeInventoryAdjustModal();
        showToast(`�f¢�.�?oâ�,�¦ ${document.getElementById('adjProductName').textContent} restocked to ${totalQty}!`);
      } catch (e) {
        showToast('Update failed: ' + (e.message || 'Unknown error'));
      }
    }

    window.loadLowStockAlerts = loadLowStockAlerts;
    window.openInventoryAdjust = openInventoryAdjust;
    window.closeInventoryAdjustModal = closeInventoryAdjustModal;
    window.saveStock = saveStock;

    /* --- Order Search --- */
    async function searchOrder() {
      const orderId = document.getElementById('orderSearchInput')?.value?.trim();
      if (!orderId) {
        showToast('�f¢�.¡�, �f¯�,¸�, Please enter an Order ID');
        return;
      }

      const contentEl = document.getElementById('orderDetailsContent');
      contentEl.innerHTML = '<div class="empty-state">Searching...</div>';

      try {
        const form = new URLSearchParams();
        form.set('action', 'search_order');
        form.set('order_id', orderId);
        form.set('csrf_token', getCsrfToken());

        const res = await fetch('api/provider_orders.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: form.toString(),
          credentials: 'same-origin',
        });

        const raw = await res.text();
        console.log('[order-search] raw response', raw);
        const data = JSON.parse(raw);
        console.log('[order-search] parsed', data);
        if (!res.ok || !data.ok) {
          throw new Error(data.error || 'Order not found');
        }

        const items = data.items || [];
        const grandTotal = Number(data.grand_total || 0).toFixed(2);
        console.log('[order-search] items', items, 'grandTotal', grandTotal);

        const itemsHtml = items.map(item => {
          const imgSrc = (item.logo_path || '').trim();
          const imgHtml = imgSrc
            ? `<img src="${escapeHtml(imgSrc)}" alt="Product image" class="order-item-thumb" onerror="this.style.display='none'">`
            : '<div class="order-item-thumb-fallback">�f°�.¸â�,��"�,¦</div>';

          const lineTotal = (parseFloat(item.price || 0) * parseInt(item.qty || 0)).toFixed(2);

          return `
            <div class="order-item-row">
              ${imgHtml}
              <div style="flex: 1; min-width: 0;">
                <div style="font-weight: 900; font-size: 15px; color: var(--ink);">${escapeHtml(item.name)}</div>
                <div style="font-size: 12px; color: #666;">SKU: ${escapeHtml(item.sku)} | Qty: ${escapeHtml(String(item.qty))}</div>
                <div style="font-size: 12px; color: #666;">Price: �f¢â�,�š�,¹${Number(item.price || 0).toFixed(2)} each</div>
              </div>
              <div style="font-weight: 900; font-size: 15px; color: var(--primary); white-space: nowrap;">
                �f¢â�,�š�,¹${lineTotal}
              </div>
            </div>
          `;
        }).join('');

        contentEl.innerHTML = `
          <div class="holo-bar">
            <div class="holo-title">Order ${escapeHtml(data.order_id)}</div>
            <div class="holo-meta">Customer: ${escapeHtml(data.customer_name)} | ${escapeHtml(data.customer_phone)}</div>
            <div class="holo-meta">Address: ${escapeHtml(data.delivery_address)}</div>
            <div class="holo-meta">Status: ${escapeHtml(data.status)} | Date: ${escapeHtml(data.created_at || '')}</div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 12px;">
            ${itemsHtml}
          </div>

          <div class="order-total-box" style="margin-top: 12px;">
            <div class="total-label">Total Amount Paid</div>
            <div class="total-value">�f¢â�,�š�,¹${grandTotal}</div>
          </div>
        `;

        const modal = document.getElementById('orderDetailsModal');
        modal.hidden = false;
        modal.classList.add('active');
      } catch (e) {
        contentEl.innerHTML = `<div class="empty-state" style="color:#ef4444;">${escapeHtml(e.message || 'Failed to load order')}</div>`;
        const modal = document.getElementById('orderDetailsModal');
        modal.hidden = false;
        modal.classList.add('active');
      }
    }

    function closeOrderDetailsModal() {
      const modal = document.getElementById('orderDetailsModal');
      if (modal) {
        modal.classList.remove('active');
        modal.hidden = true;
      }
    }

    document.getElementById('orderSearchInput')?.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') searchOrder();
    });

    window.searchOrder = searchOrder;
    window.closeOrderDetailsModal = closeOrderDetailsModal;
  </script>

  <!-- Product Modal -->
  <div class="modal-overlay" id="productModal" hidden>
    <div class="modal-backdrop" onclick="closeProductModal()"></div>
    <div class="modal-panel" onclick="event.stopPropagation()">
      <div class="modal-header">
        <div class="modal-title">Product Details</div>
        <button class="modal-close" onclick="closeProductModal()" aria-label="Close">�f�'â�,��?�</button>
      </div>
      <div class="modal-body">
        <img class="modal-img" id="modalProductImage" src="" alt="Product Image" onerror="this.style.display='none'" />
        <div class="modal-meta">
          <div class="modal-row"><span class="modal-label">Product</span><span class="modal-value" id="modalProductTitle">�f¢â�?s¬â�,�</span></div>
          <div class="modal-row"><span class="modal-label">Category</span><span class="modal-value" id="modalProductCategory">�f¢â�?s¬â�,�</span></div>
          <div class="modal-row"><span class="modal-label">SKU</span><span class="modal-value" id="modalProductSku">�f¢â�?s¬â�,�</span></div>
          <div class="modal-row"><span class="modal-label">Size</span><span class="modal-value" id="modalProductSize">�f¢â�?s¬â�,�</span></div>
          <div class="modal-row"><span class="modal-label">Price</span><span class="modal-value" id="modalProductPrice">�f¢â�,�š�,¹0.00</span></div>
          <div class="modal-row"><span class="modal-label">Qty</span><span class="modal-value" id="modalProductQty">�f¢â�?s¬â�,�</span></div>
          <div id="modalProductDesc" style="font-size: 13px; color: var(--disabled-color);"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Authorization Confirmation Modal -->
  <div class="modal-overlay" id="authorizeModal" hidden>
    <div class="modal-backdrop" onclick="closeAuthorizeModal()"></div>
    <div class="modal-panel" onclick="event.stopPropagation()">
      <div class="modal-header">
        <div class="modal-title">Confirm Authorization</div>
        <button class="modal-close" onclick="closeAuthorizeModal()" aria-label="Close">�f�'â�,��?�</button>
      </div>
      <div class="modal-body">
        <div class="doodle-card" style="transform: none; box-shadow: none;">
          <div class="card-details">
            <div class="detail-row"><span class="detail-label">Order</span><span class="detail-value" id="authorizeOrderId">�f¢â�?s¬â�,�</span></div>
            <div class="detail-row"><span class="detail-label">Driver</span><span class="detail-value" id="authorizeDriverName">�f¢â�?s¬â�,�</span></div>
          </div>
          <p style="font-size: 14px; color: var(--disabled-color);">Please confirm you want to authorize this order and assign it to the selected logistics user.</p>
        </div>
        <div class="modal-actions">
          <button class="doodle-btn btn-authorize" id="confirmAuthorizeBtn">
            <svg width="20" height="20" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><polyline points="22 4 12 14.01 9 11.01" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Confirm & Assign
          </button>
          <button class="doodle-btn" onclick="closeAuthorizeModal()" style="background-color: var(--bg-color); transform: rotate(0.5deg);">Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Provider Products Modal -->
  <div class="modal-overlay" id="providerProductsModal" hidden>
    <div class="modal-backdrop" onclick="closeProviderProductsModal()"></div>
    <div class="modal-panel" onclick="event.stopPropagation()" style="max-width: 480px;">
      <div class="modal-header">
        <div class="modal-title">Order Products</div>
        <button class="modal-close" onclick="closeProviderProductsModal()" aria-label="Close">�f�'â�,��?�</button>
      </div>
      <div class="modal-body" id="provider-products-list"></div>
    </div>
  </div>

  <!-- Quick Inventory Adjust Modal -->
  <div class="modal-overlay" id="inventoryAdjustModal" hidden>
    <div class="modal-backdrop" onclick="closeInventoryAdjustModal()"></div>
    <div class="modal-panel" onclick="event.stopPropagation()" style="max-width: 400px;">
      <div class="modal-header">
        <div class="modal-title">Quick Adjust</div>
        <button class="modal-close" onclick="closeInventoryAdjustModal()" aria-label="Close">�f�'â�,��?�</button>
      </div>
      <div class="modal-body">
        <div style="background: #ffd166; padding: 12px; border-radius: 12px; border: 2px solid var(--ink); margin-bottom: 15px; transform: rotate(-1deg);">
          <div id="adjProductName" style="font-family: var(--font-family); font-size: 20px; font-weight: bold;">Product</div>
          <div style="font-size: 14px; font-weight: bold;">Current Qty: <span id="adjCurrentQty" style="color: var(--danger); font-size: 18px;">0</span></div>
        </div>
        <div style="display: flex; flex-direction: column; gap: 8px;">
          <label style="font-weight: 900; font-size: 14px;">Add New Stock (Incoming Delivery)</label>
          <div style="display: flex; gap: 8px;">
            <input type="number" id="adjNewQty" class="doodle-input" style="text-align: center;" placeholder="Enter amount..." min="1">
            <button class="doodle-btn" style="width: auto; background-color: var(--action-color);" onclick="saveStock()">Restock</button>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Order Details Modal -->
  <div class="modal-overlay" id="orderDetailsModal" hidden>
    <div class="modal-backdrop" onclick="closeOrderDetailsModal()"></div>
    <div class="modal-panel" onclick="event.stopPropagation()" style="max-width: 520px;">
      <div class="modal-header">
        <div class="modal-title">Order Details</div>
        <button class="modal-close" onclick="closeOrderDetailsModal()" aria-label="Close">�f�'â�,��?�</button>
      </div>
      <div class="modal-body" id="orderDetailsContent"></div>
    </div>
  </div>
</body>
</html>
