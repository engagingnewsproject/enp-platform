less = {
    env: "development", // or "production"
    async: false,       // load imports async
    fileAsync: false,   // load imports async when in a page under
                        // a file protocol
    poll: 1000,         // when in watch mode, time in ms between polls
    functions: {},      // user functions, keyed by name
    dumpLineNumbers: "comments", // or "mediaQuery" or "all"
    relativeUrls: false,// whether to adjust url's to be relative
                        // if false, url's are already relative to the
                        // entry less file
    rootpath: ""// a path to add on to the start of every url
                        //resource
};

(function ($) {
  $(function() {
  	// Validate
  	// http://bassistance.de/jquery-plugins/jquery-plugin-validation/
  	// http://docs.jquery.com/Plugins/Validation/
  	// http://docs.jquery.com/Plugins/Validation/validate#toptions

  		$('#poll-form').validate({
  	    rules: {
  	      title: {
  	        minlength: 2,
  	        required: true
  	      },
  	      question: {
  	      	minlength: 2,
  	        required: true
  	      }
  	    },
  			highlight: function(element) {
  				$(element).closest('.form-group').removeClass('success').addClass('error');
  			},
  			success: function(element) {
  				element
  				.text('OK!').addClass('valid')
  				.closest('.form-group').removeClass('error').addClass('success');
  			}
  	  });
  });
}(jQuery));