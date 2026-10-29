(function () {
  function getEl(id) {
    return document.getElementById(id);
  }

  function ensureOverlay() {
    var overlay = getEl('sfsPageTransitionOverlay');
    if (overlay) return overlay;

    overlay = document.createElement('div');
    overlay.id = 'sfsPageTransitionOverlay';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.style.position = 'fixed';
    overlay.style.inset = '0';
    overlay.style.background = 'rgba(0,0,0,0.70)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '9999';
    overlay.style.opacity = '0';
    overlay.style.transition = 'opacity 180ms ease';

    var inner = document.createElement('div');
    inner.style.display = 'flex';
    inner.style.flexDirection = 'column';
    inner.style.alignItems = 'center';
    inner.style.gap = '14px';

    // truck SVG (same animation you requested)
    inner.innerHTML = [
      '<div class="w-full max-w-xs relative flex flex-col items-center">',
      '  <svg class="w-full h-8" viewBox="0 0 400 50" width="320" height="40">',
      '    <line x1="10" y1="35" x2="390" y2="35" stroke="#475569" stroke-width="2" />',
      '    <line class="truck-path" x1="10" y1="35" x2="390" y2="35" stroke="#f43f5e" stroke-width="2" />',
      '    <g class="truck-anim">',
      '      <rect x="-10" y="15" width="22" height="15" fill="#f43f5e" rx="2" />',
      '      <circle cx="-5" cy="32" r="3" fill="#0f172a" />',
      '      <circle cx="5" cy="32" r="3" fill="#0f172a" />',
      '    </g>',
      '  </svg>',
      '</div>'
    ].join('');

    // Add required keyframes once.
    if (!document.getElementById('sfsPageTransitionStyles')) {
      var style = document.createElement('style');
      style.id = 'sfsPageTransitionStyles';
      style.textContent = [
        '.sfsPageTransitionHide{opacity:0}',
        '#sfsPageTransitionOverlay{opacity:0}',
        '#sfsPageTransitionOverlay.sfsShown{opacity:1}',
        '.truck-path{stroke-dasharray:8,4;animation:dash-move 1s linear infinite;}',
        '@keyframes dash-move{to{stroke-dashoffset:-12}}',
        '.truck-anim{animation:truck-drive 3s ease-in-out infinite;}',
        '@keyframes truck-drive{0%{transform:translateX(-10px)}50%{transform:translateX(180px)}100%{transform:translateX(380px)}}'
      ].join('\n');
      document.head.appendChild(style);
    }

    overlay.appendChild(inner);
    document.body.appendChild(overlay);
    return overlay;
  }

  function go(event, url) {
    try {
      if (event && event.preventDefault) event.preventDefault();
      if (!url) return;

      var overlay = ensureOverlay();
      overlay.classList.add('sfsShown');

      // wait for the animation
      window.setTimeout(function () {
        window.location.href = url;
      }, 1200);
    } catch (e) {
      // fallback
      if (event && event.preventDefault) event.preventDefault();
      window.location.href = url;
    }
  }

  window.sfsTransition = {
    go: go
  };
})();

