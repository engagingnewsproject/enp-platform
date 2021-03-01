jQuery( document ).ready( function( $ ) {
	var siteURL,
	    enpQuizDashboardURL;

	// get the Site URL
	siteURL = $('#wp-admin-bar-view-site a').attr('href');
	// make our URL
	enpQuizDashboardURL = siteURL + 'enp-quiz/dashboard/user';
	// set the menu link to our href
	$('.toplevel_page_enp_quiz_creator_dashboard').attr('href', enpQuizDashboardURL);

});
