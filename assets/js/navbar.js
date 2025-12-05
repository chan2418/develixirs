document.addEventListener('DOMContentLoaded', function () {
  /* === Mobile off-canvas menu === */
  const overlay = document.querySelector('.mobile-menu-overlay');
  const openBtn = document.querySelector('.mobile-menu-toggle');
  const closeBtn = document.querySelector('.mobile-menu-close');

  if (openBtn && overlay) {
    openBtn.addEventListener('click', function () {
      overlay.classList.add('open');
    });
  }

  if (closeBtn && overlay) {
    closeBtn.addEventListener('click', function () {
      overlay.classList.remove('open');
    });
  }

  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (!e.target.closest('.mobile-menu-panel')) {
        overlay.classList.remove('open');
      }
    });
  }

  /* === Mobile mega menu (Shop) === */
  const shopMenu = document.querySelector('.nav li.has-mega');
  if (window.innerWidth <= 768 && shopMenu) {
    shopMenu.addEventListener('click', function (e) {
      e.stopPropagation();
      this.classList.toggle('open');
    });
  }

  /* === Search category dropdown === */
  const toggle = document.getElementById('searchCategoryToggle');
  const dropdown = document.getElementById('searchCategoryDropdown');
  const label = document.getElementById('searchCategoryLabel');
  const input = document.getElementById('searchCategoryInput');

  if (toggle && dropdown && label && input) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      toggle.classList.toggle('open');
    });

    dropdown.querySelectorAll('li').forEach(function (li) {
      li.addEventListener('click', function (e) {
        e.stopPropagation();
        const catId = this.getAttribute('data-cat-id') || '';
        const catName = this.getAttribute('data-cat-name') || 'All categories';

        input.value = catId;
        label.textContent = catId ? catName : 'All categories';

        toggle.classList.remove('open');
      });
    });

    document.addEventListener('click', function () {
      toggle.classList.remove('open');
    });
  }

  /* === User Profile Dropdown === */
  const userDropdown = document.querySelector('.user-dropdown');
  if (userDropdown) {
    userDropdown.addEventListener('click', function (e) {
      e.stopPropagation();
      this.classList.toggle('open');
    });

    document.addEventListener('click', function () {
      userDropdown.classList.remove('open');
    });
  }
});