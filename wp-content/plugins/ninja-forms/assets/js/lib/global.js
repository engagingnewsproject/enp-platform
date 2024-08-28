// TODO: Fix error collecting.
//window.onerror = function(message, url, lineNumber) {
//  var data;
//
//  data = {
//  	'action': 'nf_log_js_error',
//  	'security': nfFrontEnd.ajaxNonce,
//  	'message': message,
//  	'url': url,
//  	'lineNumber': lineNumber
//  };
//
//  jQuery.ajax({
//	    url: nfFrontEnd.adminAjax,
//	    type: 'POST',
//	    data: data,
//	    cache: false,
//	   	success: function( data, textStatus, jqXHR ) {
//	   		try {
//		   		
//	   		} catch( e ) {
//	   			console.log( e );
//	   			console.log( 'Parse Error' );
//				console.log( e );
//	   		}
//
//	    },
//	    error: function( jqXHR, textStatus, errorThrown ) {
//	        // Handle errors here
//	        console.log('ERRORS: ' + errorThrown);
//			console.log( jqXHR );
//
//			try {
//			
//			} catch( e ) {
//				console.log( 'Parse Error' );
//			}
//		}
//	});
//  return false;
//};  

var nfRadio = Backbone.Radio;

nfRadio.channel( 'form' ).on( 'render:view', function() {		
	jQuery( '.g-recaptcha' ).each( function() {
		var callback = jQuery( this ).data( 'callback' );
		var fieldID = jQuery( this ).data( 'fieldid' );
		if ( typeof window[ callback ] !== 'function' ){
			window[ callback ] = function( response ) {
				nfRadio.channel( 'recaptcha' ).request( 'update:response', response, fieldID );
			};
		}
	} );
} );

var nfRecaptcha = Marionette.Object.extend( {
	initialize: function() {
		/*
		 * If we've already rendered our form view, render our recaptcha fields.
		 */
		if ( 0 != jQuery( '.g-recaptcha' ).length ) {
			this.renderCaptcha();
		}
		/*
		 * We haven't rendered our form view, so hook into the view render radio message, and then render.
		 */
		this.listenTo( nfRadio.channel( 'form' ), 'render:view', this.renderCaptcha );
        this.listenTo( nfRadio.channel( 'captcha' ), 'reset', this.renderCaptcha );
	},

	renderCaptcha: function() {
		jQuery( '.g-recaptcha:empty' ).each( function() {
			var opts = {
				fieldid: jQuery( this ).data( 'fieldid' ),
				size: jQuery( this ).data( 'size' ),
				theme: jQuery( this ).data( 'theme' ),
				sitekey: jQuery( this ).data( 'sitekey' ),
				callback: jQuery( this ).data( 'callback' )
			};

			var grecaptchaID = grecaptcha.render( jQuery( this )[0], opts );

			if ( opts.size === 'invisible' ) {
				try {
					nf_reprocess_recaptcha( grecaptchaID  );
					setInterval(nf_reprocess_recaptcha, 110000, grecaptchaID);
				} catch( e ){
					console.log( 'Notice: Error trying to execute grecaptcha.' );
				}
			}	
		} );
	}
} );

var nfRenderRecaptcha = function() {
	new nfRecaptcha();
}

if (typeof nf_reprocess_recaptcha === 'undefined') {
	const nf_reprocess_recaptcha = ( grecaptchaID ) => {
		grecaptcha.execute( grecaptchaID );
	}
}

const nf_check_recaptcha_consent = () => {

	let stored_responses = [], services = [];

	//Cookie check
	if(!nf_check_data_for_recaptcha_consent()){
		stored_responses.push( false );
		services.push("missing_cookie");
	}
	
	//Build response with services gathered and print it in global scope
	const response = {
		"consent_state": stored_responses,
		"services" : services
	};

	nfFrontEnd.nf_consent_status_response = response;
	//Display filterable status to add extra consent check
	let nf_consent_status_extra_check = new CustomEvent('nf_consent_status_check', {detail: response});
	document.dispatchEvent(nf_consent_status_extra_check);

	return nfFrontEnd.nf_consent_status_response;
}
//Get specific recaptcha cookie
const nf_check_data_for_recaptcha_consent = () => {
	return nf_get_cookie_by_name("_grecaptcha") !== "";
}
//Get a cookie
const nf_get_cookie_by_name = (cname) => {
	let name = cname + "=";
	let decodedCookie = decodeURIComponent(document.cookie);
	let ca = decodedCookie.split(';');
	for(let i = 0; i <ca.length; i++) {
	  let c = ca[i];
	  while (c.charAt(0) == ' ') {
		c = c.substring(1);
	  }
	  if (c.indexOf(name) == 0) {
		return c.substring(name.length, c.length);
	  }
	}
	return "";
}

const nf_reload_after_cookie_consent = ( submitFieldID, layoutView ) => {
	if(typeof submitFieldID !== "undefined" && typeof layoutView !== "undefined"){
		nfRadio.channel( 'fields' ).request("remove:error", submitFieldID, "recaptcha-v3-missing");
		nfRadio.channel( 'fields' ).request("remove:error", submitFieldID, "recaptcha-v3-consent");
		nfRadio.channel( 'form' ).trigger( 'render:view', layoutView );
	}
}

const nf_add_reCaptcha_aria = () => {
	
	// Callback function to execute when mutations are observed
	const nf_act_on_inserted_node = (mutationList, observer) => {
		for (const mutation of mutationList) {
			if (mutation.type === 'childList' && mutation.target.className === "g-recaptcha") {
				let nf_recaptchaTextarea = document.getElementById("g-recaptcha-response");
				if(typeof nf_recaptchaTextarea !== "undefined" ){
					nf_recaptchaTextarea.setAttribute("aria-hidden", "true");
					nf_recaptchaTextarea.setAttribute("aria-label", "Silent reCaptcha security check");
					nf_recaptchaTextarea.setAttribute("aria-readonly", "true");
					observer.disconnect();
				}
			}
		}
		observer.disconnect();
	};
	//Observe Forms
	const nf_forms_listed = document.querySelectorAll(".ninja-forms-form-wrap");
	if(nf_forms_listed.length > 0){
		let nf_recaptcha_observers = [];
		nf_forms_listed.forEach((nf_form) => {
			nf_recaptcha_observers.push({"class": new MutationObserver(nf_act_on_inserted_node), "element": nf_form});
		});
		//Add an observer for each form
		if( nf_recaptcha_observers.length > 0){
			nf_recaptcha_observers.forEach((object) => {
				object.class.observe( object.element, 
					{ childList: true, subtree: true }
				);
			});
		}
	}
}

const nf_remove_noscript_tags_as_needed = () => {
	const noscripts = document.getElementsByClassName('ninja-forms-noscript-message')

	for (let i = 0; i < noscripts.length; i++) {
		noscripts[i].parentNode.removeChild(noscripts[i])
	}
}

jQuery(document).on( 'nfFormReady', () => {
	nf_remove_noscript_tags_as_needed();
	nf_add_reCaptcha_aria();
});