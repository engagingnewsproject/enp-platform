jQuery(document).ready(function($) {

	let maxChars = 42;

	// Loop through each of the titles
	$('.home-intro .tile__title').each(function() {
		let title = $(this).html(); // Grab the title text

		// If the title is too large
		if (title.length > maxChars) {
			// Find the end of the next word after 40 characters
			let spaceSearch = title.substring(maxChars, title.length);
			let spaceIndex = spaceSearch.indexOf(' ');
			// Make sure we're not currently on the last word And that the word isn't too big
			if (spaceIndex != -1 && spaceIndex < 10) {
				title = title.substring(0, spaceIndex + maxChars);
			} else {
				title = title.substring(0, maxChars);
			}
			title += "...";
			$(this).html(title);
		}
	});


	$("#content-slider").lightSlider({
		// fix slider height issue: https://github.com/sachinchoolur/lightslider/issues/271
		onSliderLoad: function (el) {
			var maxHeight = 0,
				container = $(el),
				mq = window.matchMedia("(max-width: 570px)");
				children = container.children();
			
			if (mq.matches) {
			// window width is at less than 570px
			}
			else {
				// window width is greater than 570px
				children.each(function () {
					var childHeight = $(this).height();
					if (childHeight > maxHeight) {
						maxHeight = childHeight;
					}
				});
				container.height(maxHeight - 275);
			}

		},
		// END fix slider height issue: https://github.com/sachinchoolur/lightslider/issues/271
		loop: true,
		mode: 'fade',
		item: 1,
		useCSS: true,
		controls: true,
		keyPress: true,
		adaptiveHeight: true,
		enableDrag: true,
		pauseOnHover: false,
		enableTouch: true,
		prevHtml: '<svg><use xlink:href="#chevron-left"></use></svg>',
		nextHtml: '<svg><use xlink:href="#chevron-right"></use></svg>',
	});

});
