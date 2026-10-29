(function(){
  try {
    var key = 'sfs_theme';
    var homeToggle = document.getElementById('themeToggle');
    var role = (window.__USER_ROLE__ || (document.body && document.body.dataset && document.body.dataset.role) || '').toString();
    var roleKey = role ? (key + '_' + role) : key;
    var isDark = false;

    if (homeToggle) {
      isDark = !!localStorage.getItem(roleKey);
      homeToggle.checked = isDark;
      homeToggle.addEventListener('change', function() {
        isDark = !!homeToggle.checked;
        if (isDark) localStorage.setItem(roleKey, '1');
        else localStorage.removeItem(roleKey);
        applyTheme();
      });
    } else {
      isDark = !!localStorage.getItem(roleKey);
    }

    function applyTheme() {
      if (isDark) {
        document.body.classList.add('dark-theme');
        document.body.classList.add('doodle-dark');
        document.documentElement.classList.add('dark');
      } else {
        document.body.classList.remove('dark-theme');
        document.body.classList.remove('doodle-dark');
        document.documentElement.classList.remove('dark');
      }
    }

    applyTheme();
  } catch (e) {
    /* ignore theme errors */
  }
})();
