require('es6-promise').polyfill();

// set-up our collapsing things, like the main menus

var mainNav = document.getElementById('main-nav')
var secondaryNav = document.getElementById('secondary-nav')
var menuToggle = document.getElementById('menu-toggle')

var filters = document.getElementsByClassName('filters')

if(mainNav || filters.length > 0) {
	import("./collapse").then(Collapse => {
		if(mainNav && secondaryNav && menuToggle) {
			new Collapse.default(menuToggle, [mainNav, secondaryNav])
		}

		if(filters.length > 0) {
			let filterItems,
					filterParent,
					filterSublist;

			for(let filter of filters) {

				filterItems = filter.getElementsByClassName('filter__item--top-item')
				for(let filterItem of filterItems) {
					// the .filter__link--parent
					filterParent = filterItem.getElementsByClassName('filter__link--parent')[0]
					filterSublist = filterItem.getElementsByClassName('filter__sublist')[0]
					new Collapse.default(filterParent, [filterSublist])
				}
			}
		}
	})
}

if(document.getElementById('orbit-balls')) {
	import("./orbit").then(Orbit => {
		new Orbit.default()
	})
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
