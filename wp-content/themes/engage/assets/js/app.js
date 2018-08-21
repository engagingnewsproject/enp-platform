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

if(document.getElementsByClassName('balls')) {
	import("./orbit").then(Orbit => {
		new Orbit.default()
	})
}

