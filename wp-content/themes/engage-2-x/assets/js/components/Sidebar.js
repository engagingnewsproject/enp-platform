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
  const overlay = document.querySelector('.sidebar-overlay');

  if (filterToggle && sidebar) {
    const isMobileSidebar = () => window.matchMedia('(max-width: 799px)').matches;
    const setSidebarState = (isOpen) => {
      sidebar.classList.toggle('is-open', isOpen);
      sidebar.setAttribute('aria-hidden', isOpen ? 'false' : 'true');
      if (isOpen) {
        sidebar.removeAttribute('inert');
      } else {
        sidebar.setAttribute('inert', '');
      }
      filterToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
      if (overlay) overlay.classList.toggle('is-visible', isOpen);
    };

    const syncSidebarState = () => {
      if (!isMobileSidebar()) {
        sidebar.removeAttribute('aria-hidden');
        sidebar.removeAttribute('inert');
        sidebar.classList.remove('is-open');
        filterToggle.setAttribute('aria-expanded', 'false');
        if (overlay) overlay.classList.remove('is-visible');
        return;
      }
      setSidebarState(sidebar.classList.contains('is-open'));
    };

    syncSidebarState();
    window.addEventListener('resize', syncSidebarState);
    filterToggle.style.display = 'flex';
    filterToggle.addEventListener('click', function() {
      if (!isMobileSidebar()) return;
      const isOpen = sidebar.classList.contains('is-open');
      setSidebarState(!isOpen);
    });
  }

  if (closeBtn && sidebar && filterToggle) {
    closeBtn.style.display = 'block';
    closeBtn.addEventListener('click', function() {
      sidebar.classList.remove('is-open');
      sidebar.setAttribute('aria-hidden', 'true');
      sidebar.setAttribute('inert', '');
      filterToggle.setAttribute('aria-expanded', 'false');
      if (overlay) overlay.classList.remove('is-visible');
    });
  }

  // Close sidebar when clicking outside
  document.addEventListener('click', function(e) {
    if (
      sidebar && 
      sidebar.classList.contains('is-open') &&
      !sidebar.contains(e.target) &&
      !filterToggle.contains(e.target)
    ) {
      sidebar.classList.remove('is-open');
      sidebar.setAttribute('aria-hidden', 'true');
      sidebar.setAttribute('inert', '');
      if (filterToggle) filterToggle.setAttribute('aria-expanded', 'false');
      if (overlay) overlay.classList.remove('is-visible');
    }
  });
});
