require('es6-promise').polyfill();

// set-up our collapsing things, like the main menus

var mainNav = document.getElementById('main-nav')
var secondaryNav = document.getElementById('secondary-nav')
var menuToggle = document.getElementById('menu-toggle')

var filters = document.getElementsByClassName('filters')

if(mainNav || filters.length > 0)
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
			console.log(filterItems)
			for(let filterItem of filterItems) {
				// the .filter__link--parent
				console.log('filteritem', filterItem)
				filterParent = filterItem.getElementsByClassName('filter__link--parent')[0]
				filterSublist = filterItem.getElementsByClassName('filter__sublist')[0]
				new Collapse.default(filterParent, [filterSublist])
			}
		}
	}
});


if(filters) {

	console.log('main nav')
} else {
	console.log('wut')
}
console.log('hello!')