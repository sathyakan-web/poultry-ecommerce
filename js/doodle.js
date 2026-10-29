(function () {
  'use strict';

  if (window.__sfsDoodleInitialized) return;
  window.__sfsDoodleInitialized = true;

  window.sfsDoodleInit = function () {
    var toggles = document.querySelectorAll('#theme-toggle');
    var saved = localStorage.getItem('sfs_theme');
    var isDark = saved === 'dark';

    if (toggles.length) {
      toggles.forEach(function (t) {
        t.checked = isDark;
        t.addEventListener('change', function () {
          var anyChecked = Array.from(toggles).some(function (el) { return el.checked; });
          if (anyChecked) {
            document.body.classList.add('doodle-dark');
            localStorage.setItem('sfs_theme', 'dark');
          } else {
            document.body.classList.remove('doodle-dark');
            localStorage.setItem('sfs_theme', 'light');
          }
        });
      });
    }

    if (isDark) {
      document.body.classList.add('doodle-dark');
    } else {
      document.body.classList.remove('doodle-dark');
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', window.sfsDoodleInit);
  } else {
    window.sfsDoodleInit();
  }
})();
