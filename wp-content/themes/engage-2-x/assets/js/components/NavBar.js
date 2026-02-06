/**
 * Handles the main navigation functionality including:
 * - Mobile menu toggle
 * - Dropdown menus
 * - Click outside to close
 */

let navbarToggler = document.querySelector(".navbar-toggler");
let navbarDropdown = document.querySelector("#navbarNavDropdown");

// Only run when nav elements exist (skip in wp-admin etc.)
if (navbarToggler && navbarDropdown) {
  const isMobileMenu = () => window.matchMedia("(max-width: 1199px)").matches;

  const setMobileMenuState = (isOpen) => {
    navbarDropdown.classList.toggle("show", isOpen);
    navbarToggler.classList.toggle("is-open", isOpen);
    navbarToggler.setAttribute("aria-expanded", isOpen ? "true" : "false");
    navbarDropdown.setAttribute("aria-hidden", isOpen ? "false" : "true");
    if (isOpen) {
      navbarDropdown.removeAttribute("inert");
    } else {
      navbarDropdown.setAttribute("inert", "");
    }
  };

  const syncMenuState = () => {
    if (!isMobileMenu()) {
      navbarDropdown.removeAttribute("aria-hidden");
      navbarDropdown.removeAttribute("inert");
      navbarToggler.setAttribute("aria-expanded", "false");
      return;
    }
    setMobileMenuState(navbarDropdown.classList.contains("show"));
  };

  syncMenuState();
  window.addEventListener("resize", syncMenuState);

  // Mobile menu toggle
  navbarToggler.addEventListener("click", function () {
    if (!isMobileMenu()) return;
    const isOpen = navbarDropdown.classList.contains("show");
    setMobileMenuState(!isOpen);
  });

  /**
   * Helper function to add event listeners to multiple elements
   * @param {string} className - CSS class to target
   * @param {string} event - Event type to listen for
   * @param {Function} fn - Callback function
   */
  function getTogglerId(className, event, fn) {
    let list = document.querySelectorAll(className);
    for (let i = 0, len = list.length; i < len; i++) {
      list[i].addEventListener(event, fn, false);
    }
  }

  let dropdownMenus = document.querySelectorAll(".dropdown-menu");
  let dropdownTogglers = document.querySelectorAll(".dropdown-toggle");

  /**
   * Closes all open dropdown menus
   */
  function closeMenus() {
    for (let j = 0; j < dropdownMenus.length; j++) {
      dropdownMenus[j].classList.remove("show");
    }
    for (let k = 0; k < dropdownTogglers.length; k++) {
      dropdownTogglers[k].classList.remove("show");
      dropdownTogglers[k].setAttribute("aria-expanded", "false");
    }
  }

  /**
   * Toggles dropdown menu visibility
   * @param {Event} e - Click event
   */
  function toggleDropdown(e) {
    let isOpen = this.classList.contains("show");

    if (!isOpen) {
      closeMenus();
      document
        .querySelector(`[aria-labelledby=${this.id}]`)
        .classList.add("show");
      this.classList.add("show");
      this.setAttribute("aria-expanded", "true");
    } else if (isOpen) {
      closeMenus();
    }

    e.preventDefault();
  }

  getTogglerId(".dropdown-toggle", "click", toggleDropdown);

  let navbar = document.querySelector(".navbar");

  navbar.addEventListener("focusout", function () {
    window.onclick = function (event) {
      if (document.querySelector(".navbar").contains(event.target)) {
        return;
      } else {
        closeMenus();
      }
    };
  });

  // Close mobile menu when clicking outside
  document.addEventListener("click", function(event) {
    if (!navbar.contains(event.target) && !navbarDropdown.contains(event.target)) {
      closeMobileMenu();
    }
  });

  /**
   * Closes the mobile menu and resets its state
   */
  function closeMobileMenu() {
    if (!isMobileMenu()) return;
    setMobileMenuState(false);
  }
}
