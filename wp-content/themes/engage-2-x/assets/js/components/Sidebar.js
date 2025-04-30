/**
 * Handles the mobile sidebar functionality including:
 * - Toggle sidebar visibility
 * - Overlay management
 * - Click outside to close
 */
document.addEventListener('DOMContentLoaded', function() {
  const filterToggle = document.querySelector('.filter__toggle');
  const sidebar = document.querySelector('.archive__sidebar');
  const closeBtn = document.querySelector('.filter__close');

  if (filterToggle && sidebar) {
    filterToggle.style.display = 'flex';
    filterToggle.addEventListener('click', function() {
      const isOpen = sidebar.classList.contains('is-open');
      
      if (isOpen) {
        sidebar.classList.remove('is-open');
        filterToggle.setAttribute('aria-expanded', 'false');
        document.querySelector('.sidebar-overlay').classList.remove('is-visible');
      } else {
        sidebar.classList.add('is-open');
        filterToggle.setAttribute('aria-expanded', 'true');
        document.querySelector('.sidebar-overlay').classList.add('is-visible');
      }
    });
  }

  if (closeBtn && sidebar && filterToggle) {
    closeBtn.style.display = 'block';
    closeBtn.addEventListener('click', function() {
      sidebar.classList.remove('is-open');
      filterToggle.setAttribute('aria-expanded', 'false');
      document.querySelector('.sidebar-overlay').classList.remove('is-visible');
    });
  }

  // Close sidebar when clicking outside
  document.addEventListener('click', function(e) {
    if (
      sidebar.classList.contains('is-open') &&
      !sidebar.contains(e.target) &&
      e.target !== filterToggle
    ) {
      sidebar.classList.remove('is-open');
      filterToggle.setAttribute('aria-expanded', 'false');
      document.querySelector('.sidebar-overlay').classList.remove('is-visible');
    }
  });
});
