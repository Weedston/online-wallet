document.addEventListener('DOMContentLoaded', function() {
  const toggle = document.querySelector('.menu-toggle');
  const menu = document.querySelector('nav ul');
  if (toggle && menu) {
    toggle.addEventListener('click', function() {
      menu.classList.toggle('open');
    });
    // Автоматически закрывать меню при клике вне или на ссылку
    document.addEventListener('click', function(e) {
      if (!menu.contains(e.target) && !toggle.contains(e.target)) {
        menu.classList.remove('open');
      }
      if (e.target.tagName === 'A' && menu.classList.contains('open')) {
        menu.classList.remove('open');
      }
    });
  }
});