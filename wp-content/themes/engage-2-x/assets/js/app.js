require('es6-promise').polyfill();
// require('./components/MenuMobile');

if (!window.location.pathname.includes('annual')) {
	require('./components/NavBar');
}
require('./components/FeaturedImgLightbox');
require('./components/PastInternsDropdown');
require('./components/Utilities');
require('./components/Animation');
