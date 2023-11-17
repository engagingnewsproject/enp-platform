// jQuery(document).ready(function ($) {
// 	let maxChars = 42;

// 	// Loop through each of the titles
// 	$(".home-intro .tile__title").each(function () {
// 		let title = $(this).html(); // Grab the title text

// 		// If the title is too large
// 		if (title.length > maxChars) {
// 			// Find the end of the next word after 40 characters
// 			let spaceSearch = title.substring(maxChars, title.length);
// 			let spaceIndex = spaceSearch.indexOf(" ");
// 			// Make sure we're not currently on the last word And that the word isn't too big
// 			if (spaceIndex != -1 && spaceIndex < 10) {
// 				title = title.substring(0, spaceIndex + maxChars);
// 			} else {
// 				title = title.substring(0, maxChars);
// 			}
// 			title += "...";
// 			$(this).html(title);
// 		}
// 	});
// });

// initiate Flickity
// https://flickity.metafizzy.co/#initialize-with-vanilla-javascript
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