(function () {
  'use strict';

  if (window.__sfsLoadingInitialized) return;
  window.__sfsLoadingInitialized = true;

  function createOverlay() {
    const overlay = document.createElement('div');
    overlay.className = 'loading-overlay';
    overlay.setAttribute('aria-hidden', 'true');
    overlay.innerHTML = `
      <div class="easter-animation">
        <div class="egg">
          <div class="eyes"></div>
        </div>
        <div class="shadow"></div>
        <div class="clouds">
          <div class="cloud1"></div>
          <div class="cloud2"></div>
          <div class="cloud3"></div>
        </div>
      </div>
    `;
    document.body.appendChild(overlay);
    return overlay;
  }

  let overlay = null;

  function getOverlay() {
    if (!overlay || !document.body.contains(overlay)) {
      overlay = createOverlay();
    }
    return overlay;
  }

  window.sfsShowLoading = function () {
    const el = getOverlay();
    el.classList.add('is-visible');
  };

  window.sfsHideLoading = function () {
    const el = getOverlay();
    if (el) {
      el.classList.remove('is-visible');
    }
  };

  window.sfsWithLoading = async function (promise) {
    sfsShowLoading();
    try {
      return await promise;
    } finally {
      sfsHideLoading();
    }
  };
})();
