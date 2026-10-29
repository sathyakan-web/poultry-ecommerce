(function () {
  'use strict';

  if (window.__sfsDesktopDockInitialized) return;
  window.__sfsDesktopDockInitialized = true;

  function init() {
    var dock = document.getElementById('bottomNav');
    if (!dock) return;

    var navItems = dock.querySelectorAll('.nav-item');
    var indicator = document.getElementById('activeIndicator');

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

    var initialActive = dock.querySelector('.nav-item.active');
    if (initialActive) {
      setTimeout(function () { updateIndicator(initialActive, true); }, 50);
    }

    navItems.forEach(function (item) {
      item.addEventListener('click', function (e) {
        if (item.classList.contains('logout')) {
          return;
        }
        e.preventDefault();
        navItems.forEach(function (nav) { nav.classList.remove('active'); });
        item.classList.add('active');
        updateIndicator(item);
        var href = item.getAttribute('href');
        if (href) {
          window.location.href = href;
        }
      });
    });

    window.addEventListener('resize', function () {
      var currentActive = dock.querySelector('.nav-item.active');
      if (currentActive) {
        updateIndicator(currentActive, true);
      }
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
