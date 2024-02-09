require("es6-promise").polyfill();
import debounce from "lodash.debounce";

var mainNav = document.getElementById("main-nav");
var secondaryNav = document.getElementById("secondary-nav");
var menuToggle = document.getElementById("menu-toggle");
const teamFilters = document.querySelector(".filter--team-menu");

var filters = document.getElementsByClassName("filters");

var collapsibles = [];

if (mainNav) {
	collapsibles.push({
		id: "menu",
		breakpoint: { min: 0, max: 800 },
		button: menuToggle,
		els: [mainNav, secondaryNav],
		collapsible: null,
	});
}

// Just need to set up collapsibles for filters then this should work
if (filters.length > 0) {
	let filterItems, filterParent, filterSublist;

	for (let filter of filters) {
		filterItems = filter.getElementsByClassName("filter__item--top-item");
		for (let filterItem of filterItems) {
			// the .filter__link--parent
			filterParent = filterItem.getElementsByClassName(
				"filter__link--parent"
			)[0];
			filterSublist =
				filterItem.getElementsByClassName("filter__sublist")[0];
			collapsibles.push({
				id: "filter",
				breakpoint: { min: 0, max: 800 },
				button: filterParent,
				els: [filterSublist],
				collapsible: null,
			});
		}
	}
}

function addOrDestroyMenu() {
	const w = window.innerWidth;
	for (let item in collapsibles) {
		if (
			collapsibles[item].breakpoint.min < w &&
			w < collapsibles[item].breakpoint.max &&
			collapsibles[item].collapsible === null
		) {
			import("./collapse").then((Collapse) => {
				collapsibles[item].collapsible = new Collapse.default(
					collapsibles[item].button,
					collapsibles[item].els
				);
			});
		} else if (
			(collapsibles[item].breakpoint.min > w ||
				w > collapsibles[item].breakpoint.max) &&
			collapsibles[item].collapsible !== null
		) {
			import("./collapse").then((Collapse) => {
				collapsibles[item].collapsible.destroy();
				collapsibles[item].collapsible = null;
			});
		}
	}
}

// set-up our collapsing things, like the main menus
addOrDestroyMenu();

window.addEventListener(
	"resize",
	debounce(function () {
		addOrDestroyMenu();
	}, 250)
);

if (document.getElementById("orbit-balls")) {
	import("./orbit").then((Orbit) => {
		new Orbit.default();
	});
}

/*
	Code for making the timeline events appear/disappear on scroll on the quiz creator landing page
	Currrently commented out because it's pretty inefficient and don't want to include it in this release.
	If I find a better way to implement it

	window.onscroll = function() {updateScroll()};

	function updateScroll() {
		var startAnchor = (document.getElementById("startAnchor").getBoundingClientRect().top + windowHeight); // Height in px of start of scroll
		var windowHeight = window.scrollY;
		var curHeight = $(document).scrollTop() + (windowHeight / 2);
		var scrolled = 0;

		// Set vars to 1 if we've scrolled past, 0 otherwise
		var stepOneAnchor = curHeight > (document.getElementById("stepOneAnchor").getBoundingClientRect().top + windowHeight) ? 1 : 0;
		var stepTwoAnchor = curHeight > (document.getElementById("stepTwoAnchor").getBoundingClientRect().top + windowHeight) ? 1 : 0;
		var stepThreeAnchor = curHeight > (document.getElementById("stepThreeAnchor").getBoundingClientRect().top + windowHeight) ? 1 : 0;

		// Update the opactity of the images
		$("#stepOneAnchor").css("opacity", stepOneAnchor);
		$("#stepTwoAnchor").css("opacity", stepTwoAnchor);
		$("#stepThreeAnchor").css("opacity", stepThreeAnchor);

		if (curHeight > startAnchor){ // Only change the height if we've scrolled past anchr
			scrolled = curHeight - startAnchor;
		}

		document.getElementById("myBar").style.height = scrolled + "px"; // Change the height of progress bar
	}
*/

// Adds functionality to automatically copy embed code to keyboard on button click
if (document.getElementById("copy-embed-code")) {
	document.getElementById("copy-embed-code").onclick = function (e) {
		// Get reference to the button we just clicked and then give it the 'active' class to show the 'COPIED!' text
		let button = e.target;
		button.classList.add("active");
		// After 1 second, we take away the active class to hide the text
		setTimeout(() => {
			button.classList.remove("active");
		}, 1000);
		// Call the function that actually copies the text to the keyboard
		copyEmbedCode();
	};
}

function copyEmbedCode() {
	// Get a reference to the textarea element that has the embed code in it
	let codeText = document.getElementById("embed-code");
	// Manually select the code and copy it to keyboard
	codeText.select();
	document.execCommand("copy");
	// Clear our selection after we copy it
	window.getSelection().removeAllRanges();
}

// This loop dynamically sets the background color of the dropdowns, since it varies page by page
// let dropdowns = document.querySelectorAll(".menu__sublist");
// let backgroundColor = getComputedStyle(document.querySelector(".header")).backgroundColor;
// for(let i = 0; i < dropdowns.length; i++){
// 	dropdowns[i].style.backgroundColor = backgroundColor;
// }

// TEMPORARY CLOSE FOR BANNER
// let announcementBannerClosed = sessionStorage.getItem('announcementBannerClosed');
// if(announcementBannerClosed !== 'true') {
// 		$('.main-body-wrapper').prepend('<div class="announcement-banner"><div class="container"><p style="margin-bottom: 0;">The Engaging Quiz tool will be down temporarily for maintenance from 2-4 pm CST. During this time embedded quizzes may not log user interaction.</p><button class="announcement__close"><span class="screen-reader-text">Close Banner</span></button></div></div>');
// }
//
// $(document).on('click', '.announcement__close', function() {
// 		// set session storage that they've closed it
// 		sessionStorage.setItem('announcementBannerClosed', 'true');
// 		$('.announcement-banner').remove();
// });

// This handleclick deals with the execution of show and hide the past interns of a semester
function toggleSemester(arg) {
	const class_name_1 = "past-interns-title__" + arg;
	const class_name_2 = "past-interns-list__" + arg;

	const title = document.getElementsByClassName(class_name_1);
	const content = document.getElementsByClassName(class_name_2);

	var x = title[0].getAttribute("aria-expanded");
	var y = content[0].getAttribute("aria-hidden");

	if (x == "true") {
		x = "false";
		y = "true";
		content[0].style.visibility = "hidden";
		content[0].style.marginTop = "0px";
		content[0].style.marginBottom = "0px";
		content[0].style.maxHeight = 0;
		content[0].style.overflow = "hidden";
	} else {
		x = "true";
		y = "false";
		content[0].style.visibility = "visible";
		content[0].style.marginTop = "20px";
		content[0].style.marginBottom = "20px";
		content[0].style.maxHeight = 100 + "%";
		content[0].style.overflow = "auto";
	}

	title[0].setAttribute("aria-expanded", x);
	content[0].setAttribute("aria-hidden", y);
}

// this change the direction of the arrow of a semester of past interns
function changeArrowDirection(arg) {
	const class_name = "past-interns-title__" + arg;
	const title = document.getElementsByClassName(class_name);
	var x = title[0].getAttribute("aria-expanded");
	if (x == "true") {
		title[0].setAttribute("data-toggle-arrow", "\u25bc");
	} else {
		title[0].setAttribute("data-toggle-arrow", "\u25ba");
	}
}

// these values are to be manaully added or deleted to ensure the semester selected are on file
var semesters = [
	"2019-2020",
	"2018-2019",
	"spring-2018",
	"alumni",
	"journalisim",
];

// In this forEach(), every iteration deals with one semester of past MEI interns
semesters.forEach(function (semester) {
	const class_name = "past-interns-title__" + semester;
	const title_element = document.getElementsByClassName(class_name);
	if (title_element.length > 0) {
		title_element[0].addEventListener(
			"click",
			function () {
				toggleSemester(semester);
				changeArrowDirection(semester);
			},
			false
		);
	}
});

// Modal pause video
jQuery(function () {
	jQuery("a[data-modal]").on("click", function () {
		jQuery(jQuery(this).data("modal")).modal();
		jQuery(".current, .close-modal").on("click", function (event) {
			jQuery("video").each(function (index) {
				jQuery(this).get(0).pause();
			});
		});
		jQuery(document).on("keyup", function (event) {
			if (event.key == "Escape") {
				jQuery("video").each(function (index) {
					jQuery(this).get(0).pause();
				});
			}
		});
		return false;
	});
});

if (teamFilters) {
	const boardItem = document.querySelector(".filter__item--board");
	const teamCategories = document.querySelector(".filters--team-menu");
	teamCategories.removeChild(boardItem);
	teamCategories.appendChild(boardItem);
}



// Lightbox for all post images
// add flick to all post images
document.addEventListener('DOMContentLoaded', function () {
  // Find all images inside elements with the ".article" class
  var images = document.querySelectorAll('.article img');

  // Add the "flick" class to each found image
  images.forEach(function (image) {
    image.classList.add('flick');

    // Attach a click event handler to each image with the "flick" class
    image.addEventListener('click', openLightbox);
  });
});

// Create a lightbox container
var lightbox = document.createElement('div');
lightbox.className = 'lightbox';
lightbox.id = 'image-lightbox';

// Create a close button
var closeButton = document.createElement('span');
closeButton.className = 'close-button';
closeButton.id = 'close-lightbox';
closeButton.innerHTML = '&times;';

// Create an image element
var lightboxImage = document.createElement('img');
lightboxImage.alt = 'Lightbox Image';
lightboxImage.id = 'lightbox-image';

// Add the close button and image to the lightbox
lightbox.appendChild(closeButton);
lightbox.appendChild(lightboxImage);

// Add the lightbox to the document body
document.body.appendChild(lightbox);

// Function to open the lightbox
function openLightbox(event) {
  // Prevent the default click behavior (e.g., following links)
  event.preventDefault();

  // Set the image source for the lightbox
  lightboxImage.src = this.getAttribute('src');

  // Add the "lightbox-open" class to the lightbox
  lightbox.classList.add('lightbox-open');
	
	// Check if there is a figcaption element in the lightbox
  var existingFigcaption = lightbox.querySelector('figcaption');

  // Get the figcaption of the clicked image
  var clickedFigcaption = this.nextElementSibling;

  // If an existing figcaption is found, replace it with the clickedFigcaption
  if (existingFigcaption) {
    existingFigcaption.parentNode.removeChild(existingFigcaption);
  }

  // Clone and append the clickedFigcaption element to the lightbox
  if (clickedFigcaption && clickedFigcaption.tagName === 'FIGCAPTION') {
    var clonedFigcaption = clickedFigcaption.cloneNode(true);
    clonedFigcaption.setAttribute('class', 'lightbox-caption');
    lightbox.appendChild(clonedFigcaption);
  }

  // Add a click event listener to the lightbox background to close it
  lightbox.addEventListener('click', closeLightbox);
}

// Function to close the lightbox
function closeLightbox() {
  // Remove the "lightbox-open" class from the lightbox
  lightbox.classList.remove('lightbox-open');
	
	// Remove the click event listener to avoid multiple bindings
  lightbox.removeEventListener('click', closeLightbox);
}

// Function to handle the Escape key press
function handleEscapeKey(event) {
  if (event.key === 'Escape') {
    closeLightbox();
  }
}

// Attach click event handler to the close button
closeButton.addEventListener('click', closeLightbox);

// Attach the keydown event listener to the document
document.addEventListener('keydown', handleEscapeKey);
