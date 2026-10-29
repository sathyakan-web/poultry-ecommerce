<?php
$embed = !empty($_GET['embed']) && $_GET['embed'] === '1';

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['lifetime'=>0,'path'=>'/','domain'=>'','secure'=>false,'httponly'=>true,'samesite'=>'Lax']);
  session_start();
}
if (!$embed) {
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Expires: 0');
  header('Pragma: no-cache');
}
require_once __DIR__.'/auth/check_login.php';
require_login('login.php');
require_once __DIR__.'/auth/csrf.php';
require_once __DIR__.'/db/config.php';
require_once __DIR__.'/api/db.php';

$user = sfs_current_user();
$userId = (int)$_SESSION['user_id'];

$role = sfs_current_role();

$stmt = db()->prepare('SELECT id, phone, full_name, role, biometric_enabled FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$profile = $stmt->fetch();

if (!$profile) {
  header('Location: login.php');
  exit;
}

$webauthnCount = 0;
$webauthnError = '';
$webauthnSuccess = '';
$webauthnDetail = '';
try {
  $stmt = db()->prepare('SELECT COUNT(*) FROM webauthn_credentials WHERE user_id = :user_id');
  $stmt->execute([':user_id' => $userId]);
  $webauthnCount = (int)$stmt->fetchColumn();
} catch (Throwable $e) {
  $webauthnCount = 0;
  $webauthnError = 'Could not load fingerprint status.';
}

if (isset($_GET['removed'])) {
  $webauthnSuccess = 'Fingerprint removed successfully. You can register a new one below.';
}
if (isset($_GET['error'])) {
  $webauthnError = 'Something went wrong while removing fingerprint.';
  $webauthnDetail = isset($_GET['msg']) ? $_GET['msg'] : '';
}


$csrfToken = sfs_csrf_token();
if (!$embed):
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
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
  <title>Poultry - Settings</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/doodle.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/loading.css" />
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: { sans: ['Inter', 'sans-serif'] },
          colors: {
            brand: { teal: '#14b8a6', dark: '#064e3b', lime: '#b5c43d' }
          }
        }
      }
    }
  </script>
  <style>
    :root {
      --primary-btn: #ff6b6b;
      --action-btn: #ff6b6b;
      --dock-bg: #ffffff;
      --border-width: 3px;
      --ink: #2b2b2b;
      --dock-radius: 40px 50px 45px 55px / 55px 45px 50px 40px;
    }
    body { font-family: 'Inter', sans-serif; -webkit-tap-highlight-color: transparent; }
    .hide-scrollbar::-webkit-scrollbar { display: none; }
    .hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }

    .settings-page-text-white { color: var(--text-main); }
    .settings-page-text-muted { color: var(--text-muted); }
    .settings-page-text-success { color: #22c55e; }
    .settings-page-text-error { color: #ef4444; }
    .settings-page-text-warning { color: #f59e0b; }
    .settings-page-text-brand { color: var(--primary-btn); }
    .settings-page-border { border-color: var(--border); }

    <?php if ($embed): ?>
    #settings-embed { margin: 0; padding: 0 !important; overflow: hidden; }
    #settings-embed .web-dock-container { display: none !important; }
    #settings-embed .doodle-header { display: none !important; }
    #settings-embed main { padding: 0 !important; height: 100vh !important; min-height: unset !important; }
    #settings-embed .doodle-container { padding: 0 !important; height: 100vh !important; min-height: unset !important; }
    #settings-embed .doodle-container > main { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
    /* --- Modal sizing for Settings modals --- */
    #settings-embed #reset-passcode-modal .doodle-modal {
      max-width: 420px;
      width: 92vw;
      padding: 20px;
    }
    @media (min-width: 640px) {
      #settings-embed #reset-passcode-modal .doodle-modal {
        max-width: 460px;
      }
    }
    /* --- Settings card sizing --- */
    #settings-embed .doodle-card {
      max-width: 100%;
    }
    @media (min-width: 640px) {
      #settings-embed .doodle-card {
        max-width: 420px;
      }
    }
    /* --- Fingerprint button sizing --- */
    #settings-embed #settings-enable-fingerprint,
    #settings-embed #webauthn-reset-form button {
      min-height: 44px;
    }
    <?php endif; ?>

    #reset-passcode-modal .doodle-modal {
      background-color: var(--card-bg);
      border-color: var(--ink);
      color: var(--text-main);
    }

    #reset-passcode-modal .doodle-modal input,
    #reset-passcode-modal .doodle-modal button {
      color: var(--text-main);
    }

    #reset-passcode-modal .doodle-modal input {
      background-color: var(--paper-front);
      border-color: var(--ink);
    }

    .web-dock-container {
      position: fixed; bottom: 15px; left: 0; right: 0;
      display: flex; justify-content: center; pointer-events: none; z-index: 1000;
    }

    .flock-dock {
      pointer-events: auto;
      background-color: var(--dock-bg);
      border: var(--border-width) solid var(--ink);
      border-radius: var(--dock-radius);
      box-shadow: 0px 6px 0px var(--ink), 0px 12px 20px rgba(0,0,0,0.2);
      display: flex; align-items: center; padding: 8px 12px;
      height: 70px; width: 90%; max-width: 650px; position: relative;
    }

    .nav-item {
      display: flex; flex-direction: column; align-items: center; justify-content: center;
      text-decoration: none; color: var(--ink); flex: 1; height: 100%; position: relative;
      z-index: 2; cursor: pointer; transition: transform 0.2s;
    }

    .icon-container { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; margin-bottom: 2px; }
    .nav-item svg {
      width: 22px; height: 22px; fill: none; stroke: var(--ink); stroke-width: 2.5;
      stroke-linecap: round; stroke-linejoin: round; transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
    .nav-label { font-size: 10px; font-weight: 900; }

    .active-indicator {
      position: absolute; height: 55px; background-color: var(--primary-btn);
      border: 2px dashed var(--ink); border-radius: 24px 32px 24px 32px / 32px 24px 32px 24px;
      box-shadow: 2px 2px 0px var(--ink); top: 3px; z-index: 1;
      transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), width 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
      animation: squish-dock 4s infinite alternate;
    }

    .nav-item.active { color: var(--ink); }
    .nav-item.active svg {
      transform: translateY(-5px) scale(1.15) rotate(-5deg);
      filter: drop-shadow(2px 2px 0px rgba(0,0,0,0.1));
    }
    .nav-item.logout svg { stroke: var(--action-btn); }
    .nav-item.logout .nav-label { color: var(--action-btn); }

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
    @media (min-width: 768px) {
      body { display: block !important; overflow-y: auto; }
      .web-dock-container { position: fixed !important; }
      .flock-dock { width: 100% !important; max-width: 1000px !important; height: 70px; }
      .nav-item { flex: 1 !important; min-width: 0 !important; }
      .active-indicator { display: block !important; }
    }
  </style>
</head>
<body class="min-h-screen flex flex-col relative" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?>
<div id="settings-embed" class="min-h-screen flex flex-col relative p-4 md:p-6 overflow-y-auto" data-role="<?php echo htmlspecialchars(sfs_current_role(), ENT_QUOTES, 'UTF-8'); ?>">
<?php endif; ?>
  <?php if ($embed): ?>
  <style>
    #settings-embed .web-dock-container { display: none !important; }
    #settings-embed .doodle-header { display: none !important; }
    #settings-embed main { padding: 0 !important; height: 100vh !important; min-height: unset !important; max-width: none !important; margin: 0 !important; width: 100% !important; }
    #settings-embed .doodle-container { padding: 0 !important; height: 100vh !important; min-height: unset !important; }
    #settings-embed { padding: 0 !important; }
    #settings-embed .doodle-container > main { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
    /* --- Modal sizing for Settings modals --- */
    #settings-embed #reset-passcode-modal .doodle-modal {
      max-width: 420px;
      width: 92vw;
      padding: 20px;
    }
    @media (min-width: 640px) {
      #settings-embed #reset-passcode-modal .doodle-modal {
        max-width: 460px;
      }
    }
    /* --- Settings card sizing --- */
    #settings-embed .doodle-card {
      max-width: 420px;
      margin-left: auto !important;
      margin-right: auto !important;
    }
    /* --- Fingerprint button sizing --- */
    #settings-embed #settings-enable-fingerprint,
    #settings-embed #webauthn-reset-form button {
      min-height: 44px;
    }
  </style>
  <?php endif; ?>
  <header class="doodle-header">
    <h1 class="brand-title">Poultry</h1>
  </header>
  <div class="doodle-container">

  <main class="flex-1 w-full max-w-md mx-auto p-4 md:p-6 overflow-hidden relative min-h-screen">
    
    <section class="w-full pb-10">
      <div class="flex items-center gap-3 mb-5">
        <?php if (!$embed): ?>
        <a href="index.php" class="w-10 h-10 rounded-full border settings-page-border flex items-center justify-center settings-page-text-muted hover:settings-page-text-white transition-colors">
          <i class="fa-solid fa-arrow-left text-sm"></i>
        </a>
        <?php endif; ?>
        <h1 class="text-xl font-bold tracking-wide">Settings</h1>
      </div>

      <div class="space-y-4">
        <div class="doodle-card">
          <h3 class="settings-page-text-white text-base font-bold mb-3 flex items-center gap-2">
            <i class="fa-solid fa-user settings-page-text-brand"></i> Profile
          </h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="settings-page-text-muted">Phone / ID</span>
              <span class="settings-page-text-white font-mono"><?php echo htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="flex justify-between">
              <span class="settings-page-text-muted">Full Name</span>
              <span class="settings-page-text-white"><?php echo htmlspecialchars($profile['full_name'] ?: '—', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="flex justify-between">
              <span class="settings-page-text-muted">Role</span>
              <span class="settings-page-text-brand font-semibold capitalize"><?php echo htmlspecialchars($profile['role'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          </div>
        </div>

        <div class="doodle-card">
          <h3 class="settings-page-text-white text-base font-bold mb-3 flex items-center gap-2">
            <i class="fa-solid fa-fingerprint settings-page-text-brand"></i> Fingerprint Login
          </h3>
          <h3 class="settings-page-text-white text-base font-bold mb-3 flex items-center gap-2">
            <i class="fa-solid fa-fingerprint settings-page-text-brand"></i> Fingerprint Login
          </h3>
          <p class="settings-page-text-muted text-xs mb-4 leading-relaxed">
            Use your device fingerprint or biometric scanner to log in quickly without typing your passcode.
          </p>

          <?php if ($webauthnSuccess): ?>
            <div class="flex items-center gap-2 settings-page-text-success text-sm font-semibold mb-3">
              <i class="fa-solid fa-circle-check"></i>
              <span><?php echo htmlspecialchars($webauthnSuccess, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
          <?php elseif ($webauthnError): ?>
            <div class="flex items-center gap-2 settings-page-text-error text-sm font-semibold mb-3">
              <i class="fa-solid fa-circle-xmark"></i>
              <span><?php echo htmlspecialchars($webauthnError, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <?php if ($webauthnDetail): ?>
              <div class="settings-page-text-error text-[11px] font-mono mb-2"><?php echo htmlspecialchars($webauthnDetail, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
          <?php endif; ?>

          <?php if ($webauthnCount > 0): ?>
            <div class="flex items-center gap-2 settings-page-text-success text-sm font-semibold mb-3">
              <i class="fa-solid fa-circle-check"></i>
              <span>Fingerprint registered</span>
            </div>
            <form id="webauthn-reset-form" method="POST" action="auth/webauthn_reset.php" class="mt-2">
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>" />
              <button type="submit" class="doodle-btn doodle-btn-outline w-full">
                Remove Fingerprint
              </button>
            </form>
            <script>
              (function() {
                const form = document.getElementById('webauthn-reset-form');
                if (!form) return;
                form.addEventListener('submit', async function(e) {
                  e.preventDefault();
                  const confirmed = confirm('Remove registered fingerprint? You will need to register again to use fingerprint login.');
                  if (!confirmed) return;
                  try {
                    const data = new FormData(form);
                    const res = await window.sfsWithLoading(fetch(form.action, { method: 'POST', body: data, headers: { 'Accept': 'application/json' } }));
                    const text = await res.text();
                    console.log('Reset response', res.status, text);
                    let json = {};
                    try { json = JSON.parse(text); } catch (e) {}
                    if (!res.ok || !json.ok) {
                      alert(json.error || ('Failed to remove fingerprint. HTTP ' + res.status));
                      return;
                    }
                    window.location.href = 'settings.php?removed=1';
                  } catch (err) {
                    console.error('Reset network error', err);
                    alert('Network error while removing fingerprint.');
                  }
                });
               })();
   </script>
              <?php else: ?>
            <div class="flex items-center gap-2 settings-page-text-warning text-sm font-semibold mb-3">
              <i class="fa-solid fa-triangle-exclamation"></i>
              <span>Not registered</span>
            </div>
            <button id="settings-enable-fingerprint" class="doodle-btn w-full">
              Enable Fingerprint Login
            </button>
          <?php endif; ?>
        </div>

        <script>
          document.addEventListener('DOMContentLoaded', function() {
            (function() {
               const openBtn = document.getElementById('open-reset-passcode');
               const modal = document.getElementById('reset-passcode-modal');
               const closeBtn = document.getElementById('close-reset-passcode');
               const requestBtn = document.getElementById('reset-request-otp');
               const submitBtn = document.getElementById('reset-submit-passcode');
               const statusEl = document.getElementById('reset-passcode-status');
               const phoneInput = document.getElementById('reset-phone');
               const otpInput = document.getElementById('reset-otp');
               const newPasscodeInput = document.getElementById('reset-new-passcode');
               const confirmPasscodeInput = document.getElementById('reset-confirm-passcode');

               if (!modal || !openBtn) return;

               function showStatus(msg, color) {
                 statusEl.textContent = msg;
                 statusEl.className = 'text-xs text-center ' + (color || 'settings-page-text-muted');
                 statusEl.classList.remove('hidden');
               }

               function openModal() {
                 modal.removeAttribute('hidden');
                 modal.classList.add('active');
                 otpInput.value = '';
                 newPasscodeInput.value = '';
                 confirmPasscodeInput.value = '';
                 submitBtn.disabled = true;
                 showStatus('', 'hidden');
               }

               function closeModal() {
                 modal.classList.remove('active');
                 modal.setAttribute('hidden', '');
               }

               openBtn.addEventListener('click', openModal);
               closeBtn.addEventListener('click', closeModal);
               modal.addEventListener('click', function(e) {
                 if (e.target === modal) {
                   closeModal();
                 }
               });

              requestBtn.addEventListener('click', async function() {
                const phone = (phoneInput.value || '').replace(/\D/g, '');
                if (!/^\d{10}$/.test(phone)) {
                  showStatus('Enter a valid 10-digit phone.', 'settings-page-text-error');
                  return;
                }

                requestBtn.disabled = true;
                showStatus('Sending OTP...', 'settings-page-text-muted');

                try {
                  const data = new FormData();
                  data.append('phone', phone);
                  data.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                  const res = await window.sfsWithLoading(fetch('auth/request_otp.php', { method: 'POST', body: data }));
                  const json = await res.json().catch(function() { return {}; });
                  if (!res.ok || !json.ok) {
                    showStatus(json.error || ('HTTP ' + res.status), 'settings-page-text-error');
                    requestBtn.disabled = false;
                    return;
                  }

                  showStatus('OTP sent. Enter it below.', 'settings-page-text-success');
                  submitBtn.disabled = false;
                  if (navigator.vibrate) navigator.vibrate(30);
                  otpInput.focus();
                } catch (e) {
                  showStatus('Network error while sending OTP.', 'settings-page-text-error');
                  requestBtn.disabled = false;
                }
              });

              submitBtn.addEventListener('click', async function() {
                const phone = (phoneInput.value || '').replace(/\D/g, '');
                const otp = (otpInput.value || '').replace(/\D/g, '');
                const newPasscode = newPasscodeInput.value || '';
                const confirmPasscode = confirmPasscodeInput.value || '';

                if (!/^\d{6}$/.test(otp)) {
                  showStatus('Enter the 6-digit OTP.', 'settings-page-text-error');
                  return;
                }
                if (!/^\d{4,}$/.test(newPasscode)) {
                  showStatus('New passcode must be at least 4 digits.', 'settings-page-text-error');
                  return;
                }
                if (newPasscode !== confirmPasscode) {
                  showStatus('Passcodes do not match.', 'settings-page-text-error');
                  return;
                }

                submitBtn.disabled = true;
                showStatus('Updating passcode...', 'settings-page-text-muted');

                try {
                  const data = new FormData();
                  data.append('phone', phone);
                  data.append('otp', otp);
                  data.append('new_password', newPasscode);
                  data.append('csrf_token', document.querySelector('meta[name="csrf-token"]').content);

                  const res = await window.sfsWithLoading(fetch('auth/reset_password.php', { method: 'POST', body: data }));
                  const json = await res.json().catch(function() { return {}; });
                  if (!res.ok || !json.ok) {
                    showStatus(json.error || ('HTTP ' + res.status), 'settings-page-text-error');
                    submitBtn.disabled = false;
                    return;
                  }

                  showStatus('Passcode updated successfully.', 'settings-page-text-success');
                  if (navigator.vibrate) navigator.vibrate([40, 30, 40]);
                  setTimeout(closeModal, 1200);
                } catch (e) {
                  showStatus('Network error while updating passcode.', 'settings-page-text-error');
                  submitBtn.disabled = false;
                }
              });
            })();
          });
        </script>

        <div class="doodle-card">
          <h3 class="settings-page-text-white text-base font-bold mb-3 flex items-center gap-2">
            <i class="fa-solid fa-shield-halved settings-page-text-brand"></i> Security
          </h3>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="settings-page-text-muted">Passcode Login</span>
              <span class="settings-page-text-success font-semibold">Enabled</span>
            </div>
            <div class="flex justify-between">
              <span class="settings-page-text-muted">SMS OTP</span>
              <span class="settings-page-text-success font-semibold">Enabled</span>
            </div>
            <button id="open-reset-passcode" class="doodle-btn w-full mt-3">
              Change Passcode
            </button>
          </div>
        </div>

        <div id="reset-passcode-modal" class="modal-overlay" hidden>
          <form id="reset-passcode-form" class="doodle-modal" autocomplete="off">
            <div class="flex items-center justify-between mb-4">
              <h3 class="settings-page-text-white text-base font-bold flex items-center gap-2">
                <i class="fa-solid fa-key settings-page-text-brand"></i> Change Passcode
              </h3>
              <button id="close-reset-passcode" type="button" class="settings-page-text-muted hover:settings-page-text-white">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <p class="text-xs settings-page-text-muted mb-4">Verify with OTP sent to your registered phone, then set a new passcode.</p>
            <div class="space-y-3">
              <div>
                <label for="reset-phone" class="block text-[11px] font-bold settings-page-text-muted mb-1">Phone / ID</label>
                <input id="reset-phone" class="doodle-input" value="<?php echo htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?>" readonly />
              </div>
              <div>
                <label for="reset-otp" class="block text-[11px] font-bold settings-page-text-muted mb-1">OTP</label>
                <input id="reset-otp" class="doodle-input" placeholder="6-digit OTP" maxlength="6" />
              </div>
              <div>
                <label for="reset-new-passcode" class="block text-[11px] font-bold settings-page-text-muted mb-1">New Passcode</label>
                <input id="reset-new-passcode" type="password" autocomplete="new-password" class="doodle-input" placeholder="Min 4 digits" />
              </div>
              <div>
                <label for="reset-confirm-passcode" class="block text-[11px] font-bold settings-page-text-muted mb-1">Confirm New Passcode</label>
                <input id="reset-confirm-passcode" type="password" autocomplete="new-password" class="doodle-input" placeholder="Repeat passcode" />
              </div>
              <button type="button" id="reset-request-otp" class="doodle-btn w-full">
                Send OTP
              </button>
              <button type="button" id="reset-submit-passcode" disabled class="doodle-btn w-full disabled:opacity-50 disabled:cursor-not-allowed">
                Update Passcode
              </button>
              <p id="reset-passcode-status" class="text-xs text-center hidden"></p>
            </div>
          </form>
        </div>
      </div>

    </section>
  </main>

  <script src="js/loading.js"></script>
  <script src="js/webauthn.js"></script>
  <script>
    const enableBtn = document.getElementById('settings-enable-fingerprint');
    if (enableBtn) {
      enableBtn.addEventListener('click', async () => {
        if (!window.PublicKeyCredential) {
          alert('WebAuthn is not supported in this browser. Use Chrome, Safari, or Edge on a compatible device.');
          return;
        }
        if (typeof registerPasskey === 'function') {
          try {
            await registerPasskey();
          } catch (e) {
            console.error('Passkey error', e);
            alert('Fingerprint registration failed: ' + ((e && e.message) ? e.message : JSON.stringify(e)));
          }
        } else {
          alert('Passkey registration is not available right now.');
        }
      });
    }

  </script>
  <?php if (!$embed): ?>
  <script src="js/doodle.js"></script>
  <?php endif; ?>
  </div>
  <?php if (!$embed): ?>
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
        <a href="calculator.php" class="nav-item" data-page="calculator"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="4" y="4" width="16" height="16" rx="2" ry="2"></rect><rect x="9" y="9" width="6" height="6"></rect><line x1="9" y1="1" x2="9" y2="4"></line><line x1="15" y1="1" x2="15" y2="4"></line><line x1="9" y1="20" x2="9" y2="23"></line><line x1="15" y1="20" x2="15" y2="23"></line><line x1="20" y1="9" x2="23" y2="9"></line><line x1="20" y1="14" x2="23" y2="14"></line><line x1="1" y1="9" x2="4" y2="9"></line><line x1="1" y1="14" x2="4" y2="14"></line></svg></div><span class="nav-label">Dosage Calc</span></a>
        <a href="settings.php" class="nav-item active" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'provider'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="qr.php" class="nav-item" data-page="verify"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><rect x="7" y="7" width="3" height="3"></rect><rect x="14" y="7" width="3" height="3"></rect><rect x="7" y="14" width="3" height="3"></rect><rect x="14" y="14" width="3" height="3"></rect></svg></div><span class="nav-label">Verify QR</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="provider_dashboard.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item active" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php elseif ($role === 'logistics'): ?>
        <a href="index.php" class="nav-item" data-page="home"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div><span class="nav-label">Home</span></a>
        <a href="orders.php" class="nav-item" data-page="orders"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg></div><span class="nav-label">Orders</span></a>
        <a href="logistics_dashboard_spa.php" class="nav-item" data-page="logistics"><div class="icon-container"><svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div><span class="nav-label">Logistics</span></a>
        <a href="settings.php" class="nav-item active" data-page="settings"><div class="icon-container"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09a1.65 1.65 0 0 0 1.51 1z"></path></svg></div><span class="nav-label">Settings</span></a>
        <a href="auth/logout.php" class="nav-item logout" data-page="logout"><div class="icon-container"><svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg></div><span class="nav-label">Logout</span></a>
      <?php endif; ?>
    </nav>
  </div>
  <?php endif; ?>
  <?php if (!$embed): ?>
  <script src="js/theme.js"></script>
  <script src="js/desktop-dock.js"></script>
  </body>
  </html>
  <?php else: ?>
  </div>
  <?php endif; ?>















