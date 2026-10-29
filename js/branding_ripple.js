(function () {
  function splitBrandText(el) {
    if (!el || el.dataset.rippleSplit === 'true') return;

    const raw = (el.textContent || '').trim();
    if (!raw) return;

    el.dataset.rippleSplit = 'true';

    // Replace text with per-character spans
    el.innerHTML = '';
    const chars = raw.split('');

    chars.forEach((ch, i) => {
      const span = document.createElement('span');
      span.innerHTML = ch === ' ' ? '&nbsp;' : ch;
      span.style.animationDelay = `${i * 0.15}s`;
      el.appendChild(span);
    });
  }

  function boot() {
    // Branding animation disabled.
    // Intentionally do nothing.
    return;
  }



  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }
})();

