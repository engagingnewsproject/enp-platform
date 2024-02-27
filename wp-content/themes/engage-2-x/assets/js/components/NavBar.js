let navbarToggler = document.querySelector(".navbar-toggler");
let navbarDropdown = document.querySelector("#navbarNavDropdown");
let navbarDropdownExpanded = navbarDropdown.getAttribute("aria-expanded");

// Mobile menu toggle

navbarToggler.addEventListener("click", function () {
  navbarDropdown.classList.toggle("show");

  if (navbarDropdownExpanded == "true") {
    navbarDropdownExpanded = "false";
  } else {
    navbarDropdownExpanded = "true";
  }

  navbarDropdown.setAttribute("aria-expanded", navbarDropdownExpanded);
});

// Dropdown toggle

function getTogglerId(className, event, fn) {
  let list = document.querySelectorAll(className);
  for (let i = 0, len = list.length; i < len; i++) {
    list[i].addEventListener(event, fn, false);
  }
}

getTogglerId(".dropdown-toggle", "click", toggleDropdown);

let dropdownMenus = document.querySelectorAll(".dropdown-menu");
let dropdownTogglers = document.querySelectorAll(".dropdown-toggle");

function closeMenus() {
  for (let j = 0; j < dropdownMenus.length; j++) {
    dropdownMenus[j].classList.remove("show");
  }
  for (let k = 0; k < dropdownTogglers.length; k++) {
    dropdownTogglers[k].classList.remove("show");
    dropdownTogglers[k].setAttribute("aria-expanded", "false");
  }
}

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

// Close dropdowns on focusout

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
