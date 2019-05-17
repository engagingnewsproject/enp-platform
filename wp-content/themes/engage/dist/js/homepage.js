jQuery(document).ready(function($) {

	let maxChars = 42;

	// Loop through each of the titles
	$('.home-intro .tile__title').each(function(index) {
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
		loop:true,
		item: 1,
		controls: true,
		enableDrag: false,
		prevHtml: '<i class="fas fa-chevron-left"></i>',
		nextHtml: ' <i class="fas fa-chevron-right"></i>'
	})

});
