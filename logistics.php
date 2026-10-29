<?php
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
require_once __DIR__ . '/auth/check_login.php';
require_login('login.php');
require_role(['logistics']);
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
    <title>Logistics Dashboard - SafeFlock Solution</title>
    <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">

    <script src="js/theme.js"></script>
    <script src="js/route-guard.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>window.__USER_ROLE__=<?php echo json_encode($role); ?>;</script>

    <style>
        :root {
            --ink: #323232;
            --paper-front: #fff9e6;
            --paper-disabled: #f1f5f9;
            --bg-color: #ffffff;

            --action-color: #4ecdc4;
            --action-hover: #3bbfb6;
            --disabled-color: #94a3b8;
            --warning-color: #ff9f1c;
            --highlight-bg: #ffe66d;
            --success-color: #06d6a0;
            --switch-bg: #4ecdc4;

            --accent: #ff6b6b;
            --primary: #ffe66d;

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

        body {
            font-family: var(--font-ui); background-color: #f0f0f0; color: var(--ink);
            display: flex; justify-content: center; align-items: center; min-height: 100vh; overflow: hidden;
        }

        .mobile-container {
            width: 100%; max-width: 414px; height: 100vh; max-height: 896px; position: relative;
            background-color: var(--bg-color); border: 3px solid var(--ink);
            display: flex; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background-image: radial-gradient(var(--ink) 1px, transparent 1px); background-size: 20px 20px;
        }

        .header {
            display: flex; justify-content: space-between; align-items: center; padding: 20px 20px 10px 20px;
            background-color: rgba(255, 255, 255, 0.95); border-bottom: 2px dashed var(--ink); z-index: 10;
        }

        .page-title {
            font-family: var(--font-family); font-size: 28px; color: var(--ink); transform: rotate(-1deg);
            letter-spacing: 1px; text-shadow: 1px 1px 0px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 8px;
        }

        .avatar {
            width: 42px; height: 42px; background-color: var(--switch-bg); color: var(--ink);
            border: var(--border-width) solid var(--ink); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;
            font-family: var(--font-family); transform: rotate(4deg); box-shadow: 2px 2px 0px var(--ink);
        }

        .duty-wrapper { display: flex; align-items: center; gap: 12px; font-family: var(--font-family); font-size: 16px; font-weight: bold; }

        .duty-toggle {
            width: 60px; height: 32px; background-color: var(--accent); border: 2px solid var(--ink);
            border-radius: 30px; position: relative; cursor: pointer; transition: 0.3s;
            box-shadow: inset 2px 2px 5px rgba(0,0,0,0.2);
        }
        .duty-toggle.active { background-color: var(--success-color); }
        .duty-knob {
            width: 24px; height: 24px; background-color: var(--bg-color); border: 2px solid var(--ink);
            border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 2px 2px 0px rgba(0,0,0,0.2);
        }
        .duty-toggle.active .duty-knob { transform: translateX(26px); }

        .content-area { flex-grow: 1; padding: 20px; overflow-y: auto; padding-bottom: 120px; display: flex; flex-direction: column; gap: 20px; }

        .spa-view { display: none; flex-direction: column; gap: 20px; animation: fade-in 0.3s ease; }
        .spa-view.active { display: flex; }
        @keyframes fade-in { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        #orders-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        @media (min-width: 768px) {
            #orders-list {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 16px;
                align-items: start;
            }
            #orders-list .doodle-card {
                margin: 0;
            }
        }

        .doodle-card {
            background-color: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1);
            box-shadow: var(--shadow-offset) var(--shadow-offset) 0px var(--ink); padding: 20px; position: relative;
            transition: all 0.2s ease; display: flex; flex-direction: column; gap: 12px;
            background-image: repeating-linear-gradient(transparent, transparent 28px, rgba(0, 0, 0, 0.05) 28px, rgba(0, 0, 0, 0.05) 30px); background-position: 0 10px;
            overflow: hidden;
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed rgba(50,50,50,0.2); padding-bottom: 10px; }
        .order-id { font-family: var(--font-family); font-size: 22px; color: var(--ink); font-weight: bold; }
        .provider-tag { font-size: 12px; font-weight: bold; color: var(--disabled-color); font-family: var(--font-ui); margin-top: -5px; }

        .address-box {
            background-color: var(--highlight-bg); border: 2px solid var(--ink); border-radius: 4px 15px 4px 15px;
            padding: 10px; transform: rotate(-1deg); box-shadow: 2px 2px 0px rgba(0,0,0,0.1); position: relative; margin: 5px 0;
        }
        .address-box::before { content: "📍"; position: absolute; top: -10px; left: -10px; font-size: 20px; animation: pin-bounce 2s infinite cubic-bezier(0.28, 0.84, 0.42, 1); }
        @keyframes pin-bounce { 0%, 100% { transform: rotate(-15deg) translateY(0); } 50% { transform: rotate(-15deg) translateY(-6px) scale(1.1); } }
        .address-text { font-family: var(--font-ui); font-size: 15px; font-weight: bold; line-height: 1.4; }

        .contact-row { display: flex; justify-content: space-between; align-items: center; background: rgba(255,255,255,0.5); border: 2px dashed var(--ink); padding: 8px 12px; border-radius: 10px; margin-top: 5px; }

        .action-area { display: flex; flex-direction: column; gap: 10px; margin-top: 10px; position: relative; z-index: 20; }

        .doodle-btn {
            font-family: var(--font-family); font-size: 18px; font-weight: bold; padding: 12px 20px;
            border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); cursor: pointer; transition: all 0.15s ease;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; letter-spacing: 1px; color: var(--ink); box-shadow: 3px 3px 0px var(--ink);
        }
        .btn-deliver { background-color: var(--action-color); transform: rotate(1deg); }
        .btn-deliver:hover { background-color: var(--action-hover); transform: translateY(-2px) rotate(2deg); box-shadow: 5px 5px 0px var(--ink); }
        .btn-deliver:active { transform: translate(3px, 3px) rotate(0deg); box-shadow: 0px 0px 0px transparent; }
        .btn-done { background-color: var(--disabled-color); cursor: not-allowed; transform: rotate(-1deg); box-shadow: 2px 2px 0px var(--ink); opacity: 0.8; }

        .otp-section {
            display: none; flex-direction: column; align-items: center; gap: 10px;
            background: rgba(255,255,255,0.6); border: 2px dashed var(--disabled-color); padding: 12px; border-radius: var(--sketch-radius-btn); transform: rotate(0.5deg);
        }
        .otp-digit {
            width: 45px; height: 50px; font-family: var(--font-family); font-size: 24px; font-weight: 900; text-align: center;
            border: 2px solid var(--ink); border-radius: 8px 12px 10px 8px / 12px 8px 10px 12px; background-color: var(--bg-color);
            color: var(--ink); box-shadow: 2px 2px 0px var(--ink); outline: none; transition: all 0.2s;
        }
        .otp-digit:focus { background-color: #fffdf5; box-shadow: 4px 4px 0px var(--action-color); border-color: var(--action-color); transform: translateY(-2px); }

        .doodle-truck-svg {
            position: absolute; left: -120px; top: 35%; width: 100px; height: 60px; z-index: 15; opacity: 0; pointer-events: none;
            fill: var(--bg-color); stroke: var(--ink); stroke-width: 2.5; stroke-linecap: round; stroke-linejoin: round;
        }
        .doodle-card.delivered { background-color: var(--paper-disabled); opacity: 0.8; box-shadow: 2px 2px 0px var(--ink); background-image: none; }
        .doodle-card.delivered .doodle-truck-svg { animation: drive-across 1.5s cubic-bezier(0.25, 1, 0.5, 1) forwards; }

        @keyframes drive-across {
            0% { left: -120px; opacity: 1; transform: rotate(0deg) translateY(0px); }
            15% { transform: rotate(-5deg) translateY(-8px); }
            30% { transform: rotate(2deg) translateY(2px); }
            45% { transform: rotate(-3deg) translateY(-6px); }
            100% { left: 120%; opacity: 1; transform: rotate(0deg) translateY(0px); }
        }

        .signature-svg {
            position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%); width: 140px; height: 80px; z-index: 12; pointer-events: none; opacity: 0;
        }
        .signature-svg path { stroke-dasharray: 500; stroke-dashoffset: 500; }
        .doodle-card.delivered .signature-svg { opacity: 1; }
        .doodle-card.delivered .signature-svg path { animation: draw-sig 0.6s ease-out 1.3s forwards; }
        @keyframes draw-sig { to { stroke-dashoffset: 0; } }

        .delivered-stamp {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(15deg) scale(3);
            font-family: var(--font-family); font-size: 28px; font-weight: 900; color: #059669; border: 4px dashed #059669;
            padding: 5px 15px; border-radius: 8px; opacity: 0; pointer-events: none; z-index: 10; text-transform: uppercase; letter-spacing: 2px;
        }
        .doodle-card.delivered .delivered-stamp { animation: stamp-down 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) 2.1s forwards; }

        .modal-overlay {
            position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);
            display: flex; justify-content: center; align-items: center; z-index: 9999;
            opacity: 0; pointer-events: none; transition: opacity 0.3s;
        }
        .modal-overlay.active { opacity: 1; pointer-events: all; }
        .modal-overlay[style*="display: flex"] { opacity: 1; pointer-events: all; }

        /* Delivery product thumbnails (logistics view) */
        .delivery-product-thumbs { display: flex; gap: 8px; flex-wrap: wrap; justify-content: center; margin-top: 10px; }
        .delivery-product-thumb { width: 64px; height: 64px; object-fit: cover; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); background: var(--bg-color); box-shadow: 3px 3px 0px var(--ink); }

        /* Product Modal Styles */
        .modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.45); }
        .modal-panel { position: relative; width: 100%; max-width: 380px; max-height: 85vh; overflow-y: auto; background: var(--paper-front); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-1); box-shadow: 8px 8px 0px var(--ink); padding: 0; z-index: 2; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px dashed rgba(50,50,50,0.2); padding: 14px 18px; margin-bottom: 0; }
        .modal-title { font-family: var(--font-family); font-size: 20px; font-weight: bold; }
        .modal-close { background: var(--bg-color); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); width: 34px; height: 34px; font-size: 18px; cursor: pointer; box-shadow: 2px 2px 0px var(--ink); }
        .modal-body { display: flex; flex-direction: column; gap: 0; padding: 16px 18px; }
        .modal-img-wrapper { width: 100%; height: 180px; background: var(--bg-color); border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); box-shadow: 3px 3px 0px var(--ink); overflow: hidden; position: relative; margin-bottom: 14px; }
        .modal-img { width: 100%; height: 100%; object-fit: cover; border-radius: inherit; }
        .modal-img-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--disabled-color); font-size: 13px; font-weight: bold; }
        .modal-meta { display: flex; flex-direction: column; gap: 10px; }
        .modal-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px dashed rgba(50,50,50,0.15); }
        .modal-row:last-child { border-bottom: none; }
        .modal-label { font-family: var(--font-family); font-weight: bold; color: var(--ink); }
        .modal-value { font-weight: bold; color: var(--disabled-color); text-align: right; }
        .modal-tag { display: inline-block; padding: 4px 10px; border-radius: 20px; background: var(--highlight-bg); border: var(--border-width) solid var(--ink); font-size: 11px; font-weight: bold; color: var(--ink); margin-top: 4px; }

        .toast-msg {
            position: fixed; top: 20px; left: 50%; transform: translateX(-50%) translateY(-100px); background-color: var(--ink); color: var(--bg-color);
            padding: 12px 24px; border-radius: var(--sketch-radius-2); font-family: var(--font-family); font-size: 18px; font-weight: bold;
            z-index: 1000; transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1); box-shadow: 4px 4px 0px var(--action-color); text-align: center; border: 2px solid var(--bg-color);
        }
        .toast-msg.show { transform: translateX(-50%) translateY(0); }

        .flock-dock-wrapper {
            position: fixed; bottom: 15px; left: 0; right: 0;
            display: flex; justify-content: center; pointer-events: none; z-index: 50;
        }
        .flock-dock {
            pointer-events: auto; background-color: #ffffff; border: 2px solid #2b2b2b;
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
        .nav-item svg { width: 22px; height: 22px; fill: none; stroke: #2b2b2b; stroke-width: 2.5;
            stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
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
        .nav-item.logout-btn svg { stroke: #ef476f; }
        .nav-item.logout-btn .nav-label { color: #ef476f; }
        @keyframes squish-dock {
            0% { border-radius: 24px 32px 24px 32px / 32px 24px 32px 24px; }
            100% { border-radius: 32px 24px 32px 24px / 24px 32px 24px 32px; }
        }

        @media (min-width: 768px) {
            body {
                display: block; justify-content: flex-start; align-items: flex-start;
                min-height: 100vh; overflow: visible; padding: 20px; margin: 0;
            }
            .mobile-container {
                max-width: 1200px; width: 100%; max-height: none; height: auto; min-height: 80vh;
                border: none; border-radius: 0; box-shadow: none; margin: 0 auto;
                background-image: none; background-color: var(--bg-color);
                overflow: visible;
            }
            .header { padding: 20px 30px 10px 30px; }
            .content-area { flex-grow: 0; padding: 20px; padding-bottom: 140px; width: 100%; overflow-y: visible; }
        }
        @media (max-width: 767px) {
            body { overflow: hidden; }
        }
    </style>
</head>
<body data-role="<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>">
    <div id="toast" class="toast-msg">Action triggered!</div>

    <!-- Unified Delivery Modal: Scan QR → Verify Product → Send OTP → Enter OTP → Complete -->
    <div id="deliveryModal" class="modal-overlay">
        <div class="scanner-box" style="max-width: 420px;">
            <button class="close-modal" onclick="closeDeliveryModal()" aria-label="Close">×</button>
            
            <!-- Step 1: QR Scanner -->
            <div id="step-scan" class="delivery-step">
                <h2 style="font-family: var(--font-display); font-size: 24px; margin-bottom: 5px;">Scan Product QR</h2>
                <p style="font-weight: bold; color: var(--disabled-color); font-size: 13px;">Align the QR code within the frame</p>
                <div id="qrReader" style="width: 100%; height: 220px; border: 2px dashed var(--disabled-color); border-radius: 12px; background: #f5f5f5;"></div>
                <div id="scannerStatus" style="font-weight: 900; color: var(--action-color); font-size: 14px; margin-top: 10px; min-height: 20px;">Initializing camera…</div>
            </div>

            <!-- Step 2: Product Verified + Send OTP -->
            <div id="step-verified" class="delivery-step" style="display: none;">
                <h2 style="font-family: var(--font-display); font-size: 24px; margin-bottom: 5px; color: var(--success-color);">✅ Product Verified</h2>
                <div style="margin: 12px 0; padding: 12px; background: rgba(6,214,160,0.1); border: 2px solid var(--success-color); border-radius: 8px;">
                    <div style="font-weight: bold; color: var(--success-color);" id="verifiedProductName"></div>
                    <div style="font-size: 12px; color: var(--disabled-color);" id="verifiedProductSku"></div>
                </div>
                <button class="doodle-btn btn-deliver" id="btnSendOtp" onclick="sendOtpFromModal()" style="width: 100%; margin-top: 12px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Send OTP to Customer
                </button>
            </div>

            <!-- Step 3: OTP Entry -->
            <div id="step-otp" class="delivery-step" style="display: none;">
                <h2 style="font-family: var(--font-display); font-size: 24px; margin-bottom: 5px;">Enter OTP</h2>
                <p style="font-weight: bold; color: var(--disabled-color); font-size: 13px;">Customer received a 4-digit code</p>
                <div id="otpStatus" style="font-weight: 900; color: var(--action-color); font-size: 14px; margin: 10px 0; min-height: 20px;"></div>
                <div style="display: flex; gap: 8px; justify-content: center; margin: 10px 0;">
                    <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 1)" onkeydown="checkBackspace(event, this, null)">
                    <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 2)" onkeydown="checkBackspace(event, this, 0)">
                    <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 3)" onkeydown="checkBackspace(event, this, 1)">
                    <input type="tel" maxlength="1" class="otp-digit" oninput="verifyOTP(this)" onkeydown="checkBackspace(event, this, 2)">
                </div>
                <button class="doodle-btn btn-deliver" id="btnVerifyOtp" onclick="verifyOTPFromModal()" style="width: 100%; margin-top: 8px; background-color: var(--success-color);">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    Verify & Handover
                </button>
            </div>

            <!-- Step 4: Success -->
            <div id="step-success" class="delivery-step" style="display: none; text-align: center;">
                <div style="font-size: 60px; margin: 10px 0;">🎉</div>
                <h2 style="font-family: var(--font-display); font-size: 28px; color: var(--success-color);">Delivered!</h2>
                <p style="color: var(--disabled-color);">Order has been successfully delivered</p>
                <button class="doodle-btn btn-deliver" onclick="closeDeliveryModal()" style="width: 100%; margin-top: 16px;">Close</button>
            </div>
        </div>
    </div>

    <!-- Product Modal -->
    <div class="modal-overlay" id="productModal">
        <div class="modal-backdrop" onclick="closeProductModal()"></div>
        <div class="modal-panel" onclick="event.stopPropagation()">
            <div class="modal-header">
                <div class="modal-title">Product Details</div>
                <button class="modal-close" onclick="closeProductModal()" aria-label="Close">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-img-wrapper">
                    <img class="modal-img" id="modalProductImage" src="" alt="Product Image" />
                    <div class="modal-img-placeholder" id="modalProductPlaceholder" style="display:none;">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <div style="margin-top: 6px;">No Image</div>
                    </div>
                </div>
                <div class="modal-meta">
                    <div class="modal-row"><span class="modal-label">Product</span><span class="modal-value" id="modalProductTitle">—</span></div>
                    <div class="modal-row"><span class="modal-label">Category</span><span class="modal-value" id="modalProductCategory">—</span></div>
                    <div class="modal-row"><span class="modal-label">SKU</span><span class="modal-value" id="modalProductSku">—</span></div>
                    <div class="modal-row"><span class="modal-label">Size</span><span class="modal-value" id="modalProductSize">—</span></div>
                    <div class="modal-row"><span class="modal-label">Price</span><span class="modal-value" id="modalProductPrice">₹0.00</span></div>
                    <div class="modal-row"><span class="modal-label">Qty</span><span class="modal-value" id="modalProductQty">—</span></div>
                    <div class="modal-row"><span class="modal-label">Customer</span><span class="modal-value" id="modalProductCustomer">—</span></div>
                    <div id="modalProductDesc" style="font-size: 12px; color: var(--disabled-color); margin-top: 6px;"></div>
                    <div class="modal-tag" id="modalProductTag" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="mobile-container">
        <header class="header">
            <h1 class="page-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="3 11 22 2 13 21 11 13 3 11"></polygon>
                </svg>
                <span id="pageTitleText">Assigned Orders</span>
            </h1>
            <div class="duty-wrapper">
                <span id="dutyText" style="color: var(--warning-color);">Off Duty</span>
                <div class="duty-toggle" id="dutyToggle" onclick="toggleDuty()">
                    <div class="duty-knob" id="dutyKnob"></div>
                </div>
            </div>
            <div class="avatar" title="Logistics Profile: <?php echo htmlspecialchars($user['full_name'] ?? 'Driver', ENT_QUOTES, 'UTF-8'); ?>">LB</div>
        </header>

        <main class="content-area">
            <!-- View: Orders (Queue only) -->
            <div id="view-orders" class="spa-view active">
                <div id="orders-list">
                    <div class="muted" style="font-weight:900;">Loading orders…</div>
                </div>
            </div>

            <!-- View: Logistics (The active operation/delivery workflow) -->
            <div id="view-logistics" class="spa-view">
                <div class="doodle-card" style="transform: rotate(0.5deg);">

                    <svg class="doodle-truck-svg" viewBox="0 0 100 60">
                        <path d="M10,40 L10,10 L70,10 L70,40 Z"></path>
                        <path d="M70,40 L70,20 L95,20 L95,40 Z"></path>
                        <circle cx="25" cy="45" r="8"></circle>
                        <circle cx="80" cy="45" r="8"></circle>
                    </svg>

                    <svg class="signature-svg" viewBox="0 0 200 100">
                        <path d="M20,50 Q40,10 60,60 T100,40 T140,70 Q160,20 180,50" fill="none" stroke="var(--ink)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>

                    <div class="delivered-stamp">Delivered</div>

                    <div class="card-header">
                        <span class="order-id" id="activeOrderId">#ORD-9929</span>
                        <div style="display: flex; gap: 5px; align-items: center;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#4a90e2" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation: spin-slow 8s linear infinite;"><line x1="12" y1="2" x2="12" y2="22"></line><line x1="2" y1="12" x2="22" y2="12"></line><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line><line x1="4.93" y1="19.07" x2="19.07" y2="4.93"></line></svg>
                            <span style="font-weight: bold; color: var(--action-color);">En Route</span>
                        </div>
                    </div>

                    <div class="contact-row" style="margin-top: 8px;">
                        <div style="flex: 1;">
                            <div style="font-weight: bold; font-family: var(--font-family); font-size: 15px;">Customer</div>
                            <div style="font-size: 13px; color: var(--disabled-color);" id="deliveryCustomerText">—</div>
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: bold; font-family: var(--font-family); font-size: 15px;">Contact</div>
                            <div style="font-size: 13px; color: var(--disabled-color);" id="customerPhoneText">—</div>
                        </div>
                    </div>

                    <div class="address-box" style="margin-top: 8px;">
                        <div class="address-text" id="deliveryAddressText">Loading address…</div>
                    </div>

                    <div id="deliveryProducts" style="margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; justify-content: center;" class="delivery-product-thumbs">
                        <div class="empty-state" style="font-size: 13px; color: var(--disabled-color); font-weight: bold;">Loading products…</div>
                    </div>

                    <!-- Logistics Operation Workflow -->
                    <div class="action-area">
                        <button class="doodle-btn btn-deliver" id="btnScanPackage" onclick="startScan(this)">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"></path><path d="M17 3h2a2 2 0 0 1 2 2v2"></path><path d="M21 17v2a2 2 0 0 1-2 2h-2"></path><path d="M7 21H5a2 2 0 0 1-2-2v-2"></path><line x1="7" y1="12" x2="17" y2="12"></line></svg>
                            Scan Package
                        </button>

                        <div class="otp-section" id="otpSection">
                            <p style="font-size: 14px; font-weight: bold; color: var(--disabled-color); text-align: center;">OTP sent to customer! Enter it below:</p>
                            <div style="display: flex; gap: 8px; justify-content: center;">
                                <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 1)" onkeydown="checkBackspace(event, this, null)">
                                <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 2)" onkeydown="checkBackspace(event, this, 0)">
                                <input type="tel" maxlength="1" class="otp-digit" oninput="moveFocus(this, 3)" onkeydown="checkBackspace(event, this, 1)">
                                <input type="tel" maxlength="1" class="otp-digit" oninput="verifyOTP(this)" onkeydown="checkBackspace(event, this, 2)">
                            </div>
                            <button class="doodle-btn btn-deliver" onclick="verifyOTPManually(this)" style="background-color: var(--success-color); transform: rotate(-1deg); margin-top: 5px;">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                                Verify & Handover
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other Views -->
            <div id="view-home" class="spa-view">
                <div class="doodle-card" style="background-color: #ffd166; transform: rotate(-1deg);">
                    <h2 style="font-family: var(--font-family); font-size: 22px;">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'Driver', ENT_QUOTES, 'UTF-8'); ?>!</h2>
                    <p style="font-size: 15px; opacity: 0.9;">Here\'s your delivery overview for today.</p>
                </div>
                <div id="home-stats" class="empty-state">Loading stats...</div>
            </div>
            <div id="view-settings" class="spa-view"><div class="doodle-card"><h2>Settings</h2><p>Driver profile controls.</p></div></div>
        </main>

        <div class="flock-dock-wrapper">
            <nav class="flock-dock" id="bottomNav">

                <div class="active-indicator" id="activeIndicator"></div>

                <a href="#" class="nav-item" data-target="home">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    </div>
                    <span class="nav-label">Home</span>
                </a>

                <a href="#" class="nav-item active" data-target="orders">
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

                <a href="#" class="nav-item" data-target="settings">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0-2.83l-.06-.06a1.65 1.65 0 0 0-.33-1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                    </div>
                    <span class="nav-label">Settings</span>
                </a>

                <a href="#" class="nav-item logout-btn" data-target="logout" onclick="doLogout()">
                    <div class="icon-container">
                        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </div>
                    <span class="nav-label">Logout</span>
                </a>

            </nav>
        </div>
    </div>

    <script>
        function showToast(message, icon) {
            const toast = document.getElementById('toast');
            toast.innerHTML = (icon ? icon + ' ' : '') + message;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }

        function doLogout() {
            showToast('Logging out...');
            fetch('auth/logout.php?json=1', { credentials: 'same-origin' })
                .catch(function () {})
                .finally(function () {
                    try { localStorage.removeItem('safeFlock_session'); } catch (e) {}
                    window.location.href = 'login.php';
                });
        }

        function getCsrfToken() {
            const meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, (m) => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));
        }

        let activeOrderId = null;
        let dutyStatus = 'OFF_DUTY';

        async function loadDutyStatus() {
            try {
                const form = new URLSearchParams();
                form.set('action', 'current_status');
                form.set('csrf_token', getCsrfToken());
                const res = await fetch('api/driver_duty.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) return;
                applyDutyUi(json.status);
            } catch (e) {}
        }

        function applyDutyUi(status) {
            dutyStatus = status || 'OFF_DUTY';
            const toggle = document.getElementById('dutyToggle');
            const text = document.getElementById('dutyText');
            if (!toggle || !text) return;
            if (dutyStatus === 'ON_DUTY') {
                toggle.classList.add('active');
                text.innerText = 'On Duty';
                text.style.color = 'var(--success-color)';
            } else {
                toggle.classList.remove('active');
                text.innerText = 'Off Duty';
                text.style.color = 'var(--warning-color)';
            }
        }

        async function toggleDuty() {
            const toggle = document.getElementById('dutyToggle');
            const text = document.getElementById('dutyText');
            if (!toggle || !text) return;
            const wasOffDuty = dutyStatus !== 'ON_DUTY';
            try {
                const form = new URLSearchParams();
                form.set('action', 'toggle');
                form.set('csrf_token', getCsrfToken());
                const res = await fetch('api/driver_duty.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || 'Failed to update duty');
                applyDutyUi(json.status);
                showToast(wasOffDuty ? 'Clocked In! Drive Safely.' : 'Clocked Out. Great job today!', wasOffDuty ? '✅' : '😴');
            } catch (e) {
                showToast(e.message || 'Duty update failed', '⚠️');
            }
        }

        // ===== LOGISTICS: ORDERS TAB =====
        async function loadAssignedOrders() {
            const listEl = document.getElementById('orders-list');
            if (!listEl) return;
            listEl.innerHTML = '<div class="muted" style="font-weight:900;">Loading orders…</div>';

            try {
                const form = new URLSearchParams();
                form.set('action', 'list_orders');
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/logistics_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

                const orders = json.orders || [];
                listEl.innerHTML = '';

                if (!orders.length) {
                    listEl.innerHTML = '<div class="doodle-card"><p>No orders assigned to you yet.</p></div>';
                    return;
                }

                orders.forEach(order => {
                    const card = document.createElement('div');
                    card.className = 'doodle-card';
                    card.style.transform = 'rotate(-1deg)';
                    card.innerHTML = `
                        <div class="card-header">
                            <span class="order-id">#${escapeHtml(order.order_id)}</span>
                            <span style="font-weight: bold; color: var(--action-color);">${escapeHtml(order.status_label || order.status)}</span>
                        </div>
                        <div class="provider-tag">Customer: ${escapeHtml(order.delivery_address || '—')}</div>
                        <div style="font-family: var(--font-ui); font-size: 15px; margin-top: 10px;">
                            <strong>Items:</strong> ${order.item_count || '—'} | <strong>Customer:</strong> ${escapeHtml(order.customer_name || '—')}
                        </div>
                        ${order.items && order.items.length ? `<div style="margin-top: 12px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap;">
                            ${order.items.map(i => `<div style="text-align: center; display: flex; flex-direction: column; align-items: center;">
                                <img src="${escapeHtml(i.logo_path || '')}" alt="${escapeHtml(i.product_name || 'Product')}" style="width: 50px; height: 50px; object-fit: cover; border: var(--border-width) solid var(--ink); border-radius: var(--sketch-radius-btn); background: var(--bg-color); box-shadow: 3px 3px 0px var(--ink);" onerror="this.style.display='none'">
                                <div style="font-size: 11px; margin-top: 4px; color: var(--disabled-color);">${escapeHtml(i.product_name || 'Product')}</div>
                            </div>`).join('')}
                        </div>` : ''}
                        <div style="display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap;">
                            <button class="doodle-btn" style="background-color: #ffd166; flex: 1;" onclick="startRoute('${escapeHtml(order.order_id)}')">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                                Start Route
                            </button>
                            <button class="doodle-btn" style="background-color: var(--paper-front); flex: 1;" data-item='${escapeHtml(JSON.stringify(order.items?.[0] || {}))}' onclick="openProductModal(this)">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                                View Product
                            </button>
                        </div>
                    `;
                    listEl.appendChild(card);
                });
            } catch (e) {
                listEl.innerHTML = '<div class="doodle-card" style="color:#ef476f;">Failed to load orders.</div>';
            }
        }

        // ===== LOGISTICS: HOME STATS =====
        async function loadHomeStats() {
            const container = document.getElementById('home-stats');
            if (!container) return;
            container.innerHTML = '<div class="empty-state">Loading stats...</div>';

            try {
                const form = new URLSearchParams();
                form.set('action', 'home_stats');
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/logistics_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

                const total = json.total_orders ?? 0;
                const pending = json.pending_orders ?? 0;
                const inRoute = json.in_route_orders ?? 0;
                const delivered = json.delivered_orders ?? 0;

                container.innerHTML = `
                    <div class="doodle-card" style="transform: rotate(0.5deg); display: flex; flex-direction: column; gap: 14px;">
                        <div class="card-header" style="border-bottom-color: rgba(50,50,50,0.3);">
                            <span class="order-id" style="font-size: 20px;">My Delivery Overview</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                            <div class="doodle-card" style="transform: rotate(-0.5deg); padding: 12px; flex-direction: column; gap: 6px; align-items: flex-start;">
                                <div style="font-size: 12px; font-weight: 900; color: var(--disabled-color); text-transform: uppercase;">Total Orders</div>
                                <div style="font-family: var(--font-family); font-size: 28px; font-weight: bold; color: var(--ink);">${escapeHtml(String(total))}</div>
                            </div>
                            <div class="doodle-card" style="transform: rotate(0.5deg); padding: 12px; flex-direction: column; gap: 6px; align-items: flex-start;">
                                <div style="font-size: 12px; font-weight: 900; color: var(--disabled-color); text-transform: uppercase;">To Deliver</div>
                                <div style="font-family: var(--font-family); font-size: 28px; font-weight: bold; color: var(--warning-color);">${escapeHtml(String(pending))}</div>
                            </div>
                            <div class="doodle-card" style="transform: rotate(-0.5deg); padding: 12px; flex-direction: column; gap: 6px; align-items: flex-start;">
                                <div style="font-size: 12px; font-weight: 900; color: var(--disabled-color); text-transform: uppercase;">In Route</div>
                                <div style="font-family: var(--font-family); font-size: 28px; font-weight: bold; color: var(--action-color);">${escapeHtml(String(inRoute))}</div>
                            </div>
                            <div class="doodle-card" style="transform: rotate(0.5deg); padding: 12px; flex-direction: column; gap: 6px; align-items: flex-start;">
                                <div style="font-size: 12px; font-weight: 900; color: var(--disabled-color); text-transform: uppercase;">Delivered</div>
                                <div style="font-family: var(--font-family); font-size: 28px; font-weight: bold; color: var(--success-color);">${escapeHtml(String(delivered))}</div>
                            </div>
                        </div>
                    </div>
                `;
            } catch (e) {
                container.innerHTML = '<div class="empty-state" style="color:#ef4444;">' + escapeHtml(e.message || 'Failed to load stats') + '</div>';
            }
        }

        function startRoute(orderId) {
            showToast('Starting route for #' + orderId + '...');
            activeOrderId = orderId;
            const el = document.getElementById('activeOrderId');
            if (el) el.innerText = '#' + orderId;

            const logisticsTab = document.querySelector('.nav-item[data-target="logistics"]');
            if (logisticsTab) logisticsTab.click();

            loadOrderDetails(orderId);
        }

        async function loadOrderDetails(orderId) {
            try {
                const form = new URLSearchParams();
                form.set('action', 'get_details');
                form.set('order_id', orderId);
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/logistics_orders.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || ('HTTP ' + res.status));

                const items = json.items || [];
                const first = items[0] || {};

                const customerNameEl = document.getElementById('deliveryCustomerText');
                const addressEl = document.getElementById('deliveryAddressText');
                const phoneEl = document.getElementById('customerPhoneText');
                const productsEl = document.getElementById('deliveryProducts');

                if (customerNameEl) customerNameEl.textContent = escapeHtml(first.customer_name || '—');
                if (addressEl) addressEl.textContent = escapeHtml(first.delivery_address || '—');
                if (phoneEl) phoneEl.textContent = escapeHtml(first.customer_phone || '—');

                if (productsEl && items.length) {
                    productsEl.innerHTML = items.map(i => `<img class="delivery-product-thumb" src="${escapeHtml(i.logo_path || '')}" alt="${escapeHtml(i.name || 'Product')}" title="${escapeHtml(i.name || 'Product')}" onerror="this.style.display='none'">`).join('');
                    if (!productsEl.querySelector('img[src]') && !productsEl.querySelector('img:not([style*="display: none"])')) {
                        productsEl.innerHTML = '<div style="font-size: 12px; color: var(--disabled-color); font-weight: bold;">No product images</div>';
                    }
                } else if (productsEl) {
                    productsEl.innerHTML = '<div style="font-size: 12px; color: var(--disabled-color); font-weight: bold;">No products</div>';
                }
            } catch (e) {
                console.warn('Failed to load order details:', e);
            }
        }

        // ===== LOGISTICS: ACTIVE DELIVERY TAB =====
        let qrScanner = null;
        let scannedQrToken = null;

        function openDeliveryModal() {
            if (!activeOrderId) {
                showToast('No active order selected');
                return;
            }
            const modal = document.getElementById('deliveryModal');
            modal.classList.add('active');
            showStep('step-scan');
            document.getElementById('scannerStatus').innerText = 'Initializing camera…';
            scannedQrToken = null;
            startQrScanner();
        }

        function closeDeliveryModal() {
            const modal = document.getElementById('deliveryModal');
            modal.classList.remove('active');
            stopQrScanner();
            // Reset all steps
            document.querySelectorAll('.delivery-step').forEach(s => s.style.display = 'none');
            document.querySelectorAll('#step-otp .otp-digit').forEach(i => i.value = '');
            document.getElementById('otpStatus').innerText = '';
            document.getElementById('btnSendOtp').disabled = false;
            document.getElementById('btnSendOtp').innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Send OTP to Customer';
            document.getElementById('btnVerifyOtp').disabled = false;
            document.getElementById('btnVerifyOtp').innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Verify & Handover';
        }

        function showStep(stepId) {
            document.querySelectorAll('.delivery-step').forEach(s => s.style.display = 'none');
            document.getElementById(stepId).style.display = 'block';
        }

        function startQrScanner() {
            if (qrScanner) {
                qrScanner.clear().catch(() => {});
                qrScanner = null;
            }
            try {
                qrScanner = new Html5QrcodeScanner('qrReader', {
                    qrbox: { width: 220, height: 220 },
                    fps: 10,
                    rememberLastScan: true,
                });
                qrScanner.render(onQrScanSuccess, onQrScanError);
            } catch (e) {
                document.getElementById('scannerStatus').innerText = 'Failed to start scanner: ' + e.message;
            }
        }

        function stopQrScanner() {
            if (qrScanner) {
                qrScanner.clear().catch(() => {});
                qrScanner = null;
            }
        }

        function onQrScanSuccess(decodedText) {
            if (scannedQrToken) return;
            stopQrScanner();
            scannedQrToken = decodedText.trim();
            document.getElementById('scannerStatus').innerText = 'Verifying product…';
            verifyScannedQr();
        }

        function onQrScanError(err) {
            // Ignore scan errors (no QR in frame)
        }

        async function verifyScannedQr() {
            try {
                const form = new URLSearchParams();
                form.set('order_id', activeOrderId);
                form.set('qr_token', scannedQrToken);
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/verify_delivery_qr.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || 'Verification failed');

                // Product verified - show verified step
                document.getElementById('verifiedProductName').innerText = json.product.name;
                document.getElementById('verifiedProductSku').innerText = 'SKU: ' + json.product.sku + (json.product.batch_no ? ' | Batch: ' + json.product.batch_no : '');
                showStep('step-verified');
            } catch (e) {
                document.getElementById('scannerStatus').innerText = '❌ ' + escapeHtml(e.message);
                setTimeout(() => {
                    scannedQrToken = null;
                    startQrScanner();
                    document.getElementById('scannerStatus').innerText = 'Try scanning again…';
                }, 2000);
            }
        }

        async function sendOtpFromModal() {
            const btn = document.getElementById('btnSendOtp');
            btn.disabled = true;
            btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" style="animation: spin-slow 1s linear infinite;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Sending…';

            try {
                const form = new URLSearchParams();
                form.set('order_id', activeOrderId);
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/send_delivery_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || 'Failed to send OTP');

                showToast('✅ OTP sent to customer!');
                showStep('step-otp');
                document.getElementById('otpStatus').innerText = 'OTP sent! Enter the 4-digit code.';
                setTimeout(() => {
                    const first = document.querySelector('#step-otp .otp-digit');
                    if (first) first.focus();
                }, 100);
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Send OTP to Customer';
                showToast('❌ ' + escapeHtml(e.message));
            }
        }

        async function verifyOTPFromModal() {
            if (!activeOrderId) return;

            const inputs = document.querySelectorAll('#step-otp .otp-digit');
            const otpCode = Array.from(inputs).map(i => i.value).join('');

            if (otpCode.length < 4) {
                showToast('Please enter the full 4-digit OTP');
                return;
            }

            const btn = document.getElementById('btnVerifyOtp');
            btn.disabled = true;
            btn.innerText = 'Verifying...';

            try {
                const form = new URLSearchParams();
                form.set('order_id', activeOrderId);
                form.set('otp_code', otpCode);
                form.set('csrf_token', getCsrfToken());

                const res = await fetch('api/verify_delivery_otp.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: form.toString(),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok || !json.ok) throw new Error(json.error || 'Invalid OTP');

                showStep('step-success');
                showToast('✅ Order delivered successfully!', '🎉');

                // Update the delivery card in the logistics tab
                const scanBtn = document.getElementById('btnScanPackage');
                if (scanBtn) scanBtn.style.display = 'none';
                const actionArea = scanBtn?.closest('.action-area');
                const otpSection = actionArea?.querySelector('.otp-section');
                if (otpSection) {
                    otpSection.innerHTML = '<button class="doodle-btn btn-done" disabled style="width: 100%;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"></path></svg> Delivered</button>';
                }
            } catch (e) {
                btn.disabled = false;
                btn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> Verify & Handover';
                document.getElementById('otpStatus').innerText = '❌ ' + escapeHtml(e.message);
                document.getElementById('otpStatus').style.color = 'var(--accent)';
            }
        }

        // Keep old startScan for backward compatibility (button click handler)
        async function startScan(btn) {
            openDeliveryModal();
        }

        // Product Modal
        function openProductModal(btnOrItemJson) {
            const modal = document.getElementById('productModal');
            if (!modal) return;
            let item = btnOrItemJson;
            if (typeof item === 'string') {
                try { item = JSON.parse(item); } catch (e) { console.warn('Invalid item JSON'); return; }
            } else if (item && item.dataset && item.dataset.item) {
                try { item = JSON.parse(item.dataset.item); } catch (e) { console.warn('Invalid item JSON'); return; }
            }
            const img = document.getElementById('modalProductImage');
            const placeholder = document.getElementById('modalProductPlaceholder');
            const title = document.getElementById('modalProductTitle');
            const category = document.getElementById('modalProductCategory');
            const sku = document.getElementById('modalProductSku');
            const size = document.getElementById('modalProductSize');
            const price = document.getElementById('modalProductPrice');
            const qty = document.getElementById('modalProductQty');
            const customer = document.getElementById('modalProductCustomer');
            const desc = document.getElementById('modalProductDesc');
            const tag = document.getElementById('modalProductTag');

            if (img) {
                img.src = item.logo_path ? item.logo_path : '';
                img.onerror = function() { this.style.display = 'none'; if (placeholder) placeholder.style.display = 'flex'; };
                img.onload = function() { this.style.display = 'block'; if (placeholder) placeholder.style.display = 'none'; };
                if (!item.logo_path) { img.style.display = 'none'; if (placeholder) placeholder.style.display = 'flex'; }
            }
            if (title) title.textContent = item.product_name || item.name || 'Product';
            if (category) {
                category.textContent = item.category || '—';
                if (tag) { tag.textContent = item.category || 'Product'; tag.style.display = 'inline-block'; }
            }
            if (sku) sku.textContent = item.sku ? 'SKU: ' + item.sku : 'SKU: —';
            if (size) size.textContent = item.size ? 'Size: ' + item.size : 'Size: —';
            if (price) price.textContent = item.price ? '₹' + Number(item.price).toFixed(2) : '₹0.00';
            if (qty) qty.textContent = item.qty ? 'Qty: ' + item.qty : 'Qty: —';
            if (customer) customer.textContent = item.customer_name || '—';
            if (desc) desc.textContent = item.description || '';

            modal.classList.add('active');
        }

        function closeProductModal() {
            const modal = document.getElementById('productModal');
            if (modal) modal.classList.remove('active');
        }

        function moveFocus(input, nextIndex) {
            input.value = input.value.replace(/[^0-9]/g, '');
            if (input.value.length === 1) {
                const inputs = input.closest('.otp-section').querySelectorAll('.otp-digit');
                if (nextIndex < inputs.length) inputs[nextIndex].focus();
            }
        }

        function checkBackspace(e, input, prevIndex) {
            if (e.key === 'Backspace' && input.value.length === 0 && prevIndex !== null) {
                const inputs = input.closest('.otp-section').querySelectorAll('.otp-digit');
                inputs[prevIndex].focus();
            }
        }

        function verifyOTP(lastInput) {
            lastInput.value = lastInput.value.replace(/[^0-9]/g, '');
            if (lastInput.value.length === 1) {
                const inputs = lastInput.closest('.otp-section').querySelectorAll('.otp-digit');
                let allFilled = true;
                inputs.forEach(inp => { if (!inp.value) allFilled = false; });
                if (allFilled) {
                    const btn = lastInput.closest('.otp-section').querySelector('.btn-deliver');
                    verifyOTPManually(btn);
                }
            }
        }

        // ===== SPA TAB SWITCHING =====
        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/auth_session.php', { credentials: 'same-origin' })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok && data.session) {
                        try {
                            localStorage.setItem('safeFlock_session', JSON.stringify(Object.assign({}, data.session, { expires_at: Date.now() + 8 * 60 * 60 * 1000 })));
                        } catch (e) {}
                    }
                })
                .catch(function () {});

            loadDutyStatus();
            const navItems = document.querySelectorAll('.nav-item');
            const views = document.querySelectorAll('.spa-view');
            const pageTitleText = document.getElementById('pageTitleText');
            const dock = document.getElementById('bottomNav');
            const indicator = document.getElementById('activeIndicator');

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
            if (initialActive) {
                const target = initialActive.getAttribute('data-target');
                if (target === 'orders') loadAssignedOrders();
                if (target === 'home') loadHomeStats();
                setTimeout(() => updateIndicator(initialActive, true), 50);
            }

            navItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (this.classList.contains('active')) return;

                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');

                    const target = this.getAttribute('data-target');
                    if (target === 'orders') {
                        pageTitleText.innerText = 'Assigned Orders';
                        loadAssignedOrders();
                    } else if (target === 'logistics') {
                        pageTitleText.innerText = 'Active Route';
                    } else if (target === 'home') {
                        pageTitleText.innerText = 'Home';
                        loadHomeStats();
                    } else if (target === 'settings') {
                        pageTitleText.innerText = 'Settings';
                    }

                    const targetView = document.getElementById('view-' + target);
                    if (targetView) {
                        views.forEach(v => v.classList.remove('active'));
                        targetView.classList.add('active');
                    }

                    updateIndicator(this, false);
                });
            });
        });
    </script>
</body>
</html>
