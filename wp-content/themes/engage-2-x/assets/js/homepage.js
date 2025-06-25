import Flickity from 'flickity'

// Initialize Flickity
const carousel = new Flickity('.carousel-main', {
  // Flickity options
  wrapAround: true,
  contain: true,
	lazyLoad: true,
  ariaLabel: 'Carousel Navigation'
})

// Enhance accessibility of navigation buttons
function enhanceButtonAccessibility() {
  // Previous button
  const prevButton = document.querySelector('.flickity-prev-next-button.previous');
  if (prevButton) {
    prevButton.setAttribute('aria-label', 'Previous slide');
    const prevIcon = prevButton.querySelector('.flickity-button-icon');
    if (prevIcon) {
      // Remove role="img" since it's decorative
      prevIcon.removeAttribute('role');
      prevIcon.setAttribute('aria-hidden', 'true');
      // Remove title element if it exists
      const titleElement = prevIcon.querySelector('title');
      if (titleElement) {
        titleElement.remove();
      }
    }
  }

  // Next button
  const nextButton = document.querySelector('.flickity-prev-next-button.next');
  if (nextButton) {
    nextButton.setAttribute('aria-label', 'Next slide');
    const nextIcon = nextButton.querySelector('.flickity-button-icon');
    if (nextIcon) {
      // Remove role="img" since it's decorative
      nextIcon.removeAttribute('role');
      nextIcon.setAttribute('aria-hidden', 'true');
      // Remove title element if it exists
      const titleElement = nextIcon.querySelector('title');
      if (titleElement) {
        titleElement.remove();
      }
    }
  }
}

// Call the function after Flickity is initialized
enhanceButtonAccessibility();

// Utility: get all focusable elements inside a container
function getFocusableElements(container) {
  return container.querySelectorAll(
    'a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])'
  );
}

function updateSlideFocusability() {
  document.querySelectorAll('.carousel-cell').forEach(cell => {
    const isHidden = cell.getAttribute('aria-hidden') === 'true';
    getFocusableElements(cell).forEach(el => {
      if (isHidden) {
        el.setAttribute('tabindex', '-1');
      } else {
        el.removeAttribute('tabindex');
      }
    });
  });
}

// After Flickity changes slides, update focusability
carousel.on('select', updateSlideFocusability);
carousel.on('settle', updateSlideFocusability);

// Also run once on page load
updateSlideFocusability();

let maxChars = 42;

// Get all elements with the class "tile__title"
let titleElements = document.querySelectorAll(".home-intro .tile__title");

titleElements.forEach(function (element) {
		let title = element.innerHTML; // Grab the title text

		// If the title is too large
		if (title.length > maxChars) {
				// Find the end of the next word after 40 characters
				let spaceSearch = title.substring(maxChars, title.length);
				let spaceIndex = spaceSearch.indexOf(" ");

				// Make sure we're not currently on the last word and that the word isn't too big
				if (spaceIndex !== -1 && spaceIndex < 10) {
						title = title.substring(0, spaceIndex + maxChars);
				} else {
						title = title.substring(0, maxChars);
				}

				title += "...";
				element.innerHTML = title;
		}
});