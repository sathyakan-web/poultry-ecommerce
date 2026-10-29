// WebAuthn helpers shared across login and app
window.sfs_webauthn_arraybuffer_to_base64url = function(buffer) {
  const bytes = new Uint8Array(buffer);
  let binary = '';
  for (let i = 0; i < bytes.byteLength; i++) {
    binary += String.fromCharCode(bytes[i]);
  }
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
};

window.sfs_webauthn_base64url_to_arraybuffer = function(base64url) {
  const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
  const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
  const binary = atob(padded);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
};

window.sfs_webauthn_inProgress = false;
window.sfs_webauthn_lastClickTime = 0;

window.registerPasskey = async function() {
  if (!window.PublicKeyCredential) {
    alert('WebAuthn is not supported in this browser.');
    return;
  }

  if (window.sfs_webauthn_inProgress) {
    alert('Please wait, a passkey action is already in progress.');
    return;
  }
  window.sfs_webauthn_inProgress = true;

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const startResp = await fetch('auth/webauthn_register_start.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ csrf_token: csrf }).toString(),
    });
    const startData = await startResp.json();
    if (!startResp.ok || !startData.ok) {
      throw new Error(startData.error || 'Passkey registration not available');
    }

    const options = startData.options;
    options.challenge = sfs_webauthn_base64url_to_arraybuffer(options.challenge);
    options.user.id = sfs_webauthn_base64url_to_arraybuffer(options.user.id);

    const credential = await navigator.credentials.create({
      publicKey: options,
    });

    if (!credential || !credential.response) {
      throw new Error('No credential created');
    }

    const form = new URLSearchParams();
    form.set('credential_id', sfs_webauthn_arraybuffer_to_base64url(credential.rawId));
    if (credential.response.publicKey) {
      form.set('public_key', sfs_webauthn_arraybuffer_to_base64url(credential.response.publicKey));
    }
    form.set('attestation', sfs_webauthn_arraybuffer_to_base64url(credential.response.attestationObject));
    if (credential.response.clientDataJSON) {
      form.set('client_data_json', sfs_webauthn_arraybuffer_to_base64url(credential.response.clientDataJSON));
    }
    form.set('csrf_token', csrf);

    const finishResp = await fetch('auth/webauthn_register_finish.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    });
    const finishData = await finishResp.json();
    if (!finishResp.ok || !finishData.ok) {
      throw new Error(finishData.error || 'Passkey registration failed');
    }

    if (navigator.vibrate) navigator.vibrate([40, 30, 40]);
    alert('Fingerprint registered successfully! You can now use it to login.');
    window.location.reload();
  } catch (err) {
    console.error('registerPasskey error', err);
    alert('Passkey registration failed: ' + err.message);
  } finally {
    window.sfs_webauthn_inProgress = false;
  }
};

window.triggerPasskeyLogin = async function(phone) {
  if (!window.PublicKeyCredential || !navigator.credentials || !navigator.credentials.get) {
    throw new Error('WebAuthn is not supported in this browser.');
  }

  if (window.sfs_webauthn_inProgress) {
    alert('Passkey is busy. Reloading page to reset the scanner...');
    window.location.reload();
    return;
  }
  window.sfs_webauthn_inProgress = true;

  try {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const startResp = await fetch('auth/webauthn_login_start.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: new URLSearchParams({ phone: phone, csrf_token: csrf }).toString(),
    });
    const startText = await startResp.text();
    const startData = JSON.parse(startText);
    if (!startResp.ok || !startData.ok) {
      throw new Error(startData.error || 'Passkey login not available');
    }

    const options = startData.options;
    options.challenge = sfs_webauthn_base64url_to_arraybuffer(options.challenge);
    options.allowCredentials = options.allowCredentials.map((cred) => ({
      type: cred.type,
      id: sfs_webauthn_base64url_to_arraybuffer(cred.id),
    }));

    await new Promise(resolve => setTimeout(resolve, 500));
    var assertion = await navigator.credentials.get({
      publicKey: options,
    });

    if (!assertion || !assertion.response) {
      throw new Error('No biometric response received');
    }

    const form = new URLSearchParams();
    form.set('phone', phone);
    form.set('credential_id', sfs_webauthn_arraybuffer_to_base64url(assertion.rawId));
    form.set('authenticator_data', sfs_webauthn_arraybuffer_to_base64url(assertion.response.authenticatorData));
    form.set('client_data_json', sfs_webauthn_arraybuffer_to_base64url(assertion.response.clientDataJSON));
    form.set('signature', sfs_webauthn_arraybuffer_to_base64url(assertion.response.signature));
    form.set('csrf_token', csrf);

    const finishResp = await fetch('auth/webauthn_login_finish.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: form.toString(),
    });
    const finishData = await finishResp.json();
    if (!finishResp.ok || !finishData.ok) {
      throw new Error(finishData.error || 'Biometric authentication failed');
    }

    if (navigator.vibrate) navigator.vibrate([40, 30, 40]);
    const role = (finishData.session && finishData.session.role) ? finishData.session.role : 'customer';
    if (role === 'provider') {
      window.location.href = 'provider_dashboard.php';
    } else if (role === 'logistics') {
      window.location.href = 'logistics_dashboard_spa.php';
    } else {
      window.location.href = 'index.php';
    }
  } catch (err) {
    console.error('triggerPasskeyLogin error', err);
    if (err && err.name === 'AbortError') {
      return;
    }
    const msg = (err && err.message) ? err.message : JSON.stringify(err);
    alert('Passkey error: ' + msg + '. Reloading page to reset...');
    window.location.reload();
    return;
  } finally {
    window.sfs_webauthn_inProgress = false;
  }
};

