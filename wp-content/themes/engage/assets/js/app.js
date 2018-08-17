require('es6-promise').polyfill();





// set-up our collapsing things, like the main menus
import("./collapse").then(Collapse => {

	var mainNav = document.getElementById('main-nav')
	var secondaryNav = document.getElementById('secondary-nav')
	var menuToggle = document.getElementById('menu-toggle')
  new Collapse.default(menuToggle, [mainNav, secondaryNav]);
});

console.log('hello!')