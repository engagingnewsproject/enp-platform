jQuery(document).ready(function($) {
    $('#nf-start').click( function() {
        $.post(
			nfAdmin.ajax_url,
			{
				'action': 'nf_onboarding_start',
				'security': nfAdmin.nonce
			}
		).then (function( response ) {
			response = JSON.parse( response );

			if(response.data.hasOwnProperty('errors')) {
				var errors = response.data.errors;
				var errorMsg = '';

				if (Array.isArray(errors)) {
					errors.forEach(function(error) {
						errors += error + "\n";
					})
				} else {
					errors = errors;
				}
				alert(errors);
				return null;
			}

			if( response.data.success ) {
                window.nfOB = new NinjaOnboarding();
                jQuery('#nf-start').html(nfOBi18n.inProgress).addClass('disabled');
                window.location.href = "admin.php?page=ninja-forms#forms";
			}
		} );
    });
    $('#nf-dismiss').click( function() {
        $.post(
			nfAdmin.ajax_url,
			{
				'action': 'nf_onboarding_dismiss',
				'security': nfAdmin.nonce
			}
		).then (function( response ) {
			response = JSON.parse( response );

			if(response.data.hasOwnProperty('errors')) {
				var errors = response.data.errors;
				var errorMsg = '';

				if (Array.isArray(errors)) {
					errors.forEach(function(error) {
						errors += error + "\n";
					})
				} else {
					errors = errors;
				}
				alert(errors);
				return null;
			}

			if( response.data.success ) {
                window.location.replace(nfAdmin.dashboard_url);
			}
		} );
    });
    // Initial Optin modal
    if ( '1' == nfAdmin.showOptin ) {
        // Declare all of our opt-in code here.
        var optinModal = new jBox( 'Modal', {
            closeOnEsc:     false,
            closeOnClick:   false,
            width:          400
        } );
        // Define the modal title.
        var title = document.createElement( 'div' );
        title.id = 'optin-modal-title';
        var titleStyling = document.createElement( 'h2' );
        titleStyling.innerHTML = 'Help make Ninja Forms better!';
        title.appendChild( titleStyling );
        // Define the modal content.
        var content = document.createElement( 'div' );
        content.classList.add( 'message' );
        content.style.padding = '0px 20px 20px 20px';
        content.innerHTML = nfi18n.optinContent;
        var p = document.createElement( 'p' );
        p.style.paddingBottom = '10px';
        var checkBox = document.createElement( 'input' );
        checkBox.id = 'optin-send-email';
        checkBox.setAttribute( 'type', 'checkbox' );
        checkBox.style.margin = '7px';
        var label = document.createElement( 'label' );
        label.setAttribute( 'for', 'optin-send-email' );
        label.innerHTML = nfi18n.optinYesplease;
        p.appendChild( checkBox );
        p.appendChild( label );
        content.appendChild( p );
        p = document.createElement( 'p' );
        p.id = 'optin-block';
        p.style.padding = '0px 5px 20px 5px';
        p.style.display = 'none';
        var email = document.createElement( 'input' );
        email.id = 'optin-email-address';
        email.setAttribute( 'type', 'text' );
        email.setAttribute( 'value', nfAdmin.currentUserEmail );
        email.style.width = '100%';
        email.style.fontSize = '16px';
        p.appendChild( email );
        content.appendChild( p );
        var spinner = document.createElement( 'span' );
        spinner.id = 'optin-spinner';
        spinner.classList.add( 'spinner' );
        spinner.style.display = 'none';
        content.appendChild( spinner );
        var actions = document.createElement( 'div' );
        actions.id = 'optin-buttons';
        actions.classList.add( 'buttons' );
        var cancel = document.createElement( 'div' );
        cancel.id = 'optout';
        cancel.classList.add( 'nf-button', 'secondary' );
        cancel.innerHTML = nfi18n.optinSecondary;
        actions.appendChild( cancel );
        var confirm = document.createElement( 'div' );
        confirm.id = 'optin';
        confirm.classList.add( 'nf-button', 'primary', 'pull-right' );
        confirm.innerHTML = nfi18n.optinPrimary;
        actions.appendChild( confirm );
        content.appendChild( actions );
        // Define the success title.
        var successTitle = document.createElement( 'h2' );
        successTitle.innerHTML = nfi18n.optinAwesome;
        // Define the success content.
        var successContent = document.createElement( 'div' );
        successContent.id = 'optin-thankyou';
        successContent.classList.add( 'message' );
        successContent.style.padding = '20px';
        successContent.innerHTML = nfi18n.optinThanks;
        // Set the options for the modal and open it.
        optinModal.setContent( document.createElement( 'div' ).appendChild( content ).innerHTML );
        optinModal.setTitle( document.createElement( 'div' ).appendChild( title ).innerHTML );
        optinModal.open();
        // Show/Hide email field, based on the opt-in checkbox.
        jQuery( '#optin-send-email' ).click( function( e ) {
            if( jQuery( this ).is( ':checked' ) ) {
                jQuery( '#optin-block' ).show();
            } else {
                jQuery( '#optin-block' ).hide();
            }
        } );
        // Setup the optin click event.
        jQuery( '#optin' ).click( function( e ) {
            var sendEmail;

            if (  document.getElementById('optin-send-email').checked ) {
                sendEmail = 1;
                userEmail = document.getElementById('optin-email-address').value;
            } else {
                sendEmail = 0;
                userEmail = '';
            }
            // Disable our buttons.
            jQuery( '#optin' ).unbind( 'click' );
            jQuery( '#optout' ).unbind( 'click' );
            // Get a reference to the current width (to avoid resizing the button).
            var width = jQuery( '#optin' ).width();
            // Show spinner.
            jQuery( '#optin' ).html( '<span class="dashicons dashicons-update dashicons-update-spin"></span>' );
            jQuery( '#optin' ).width( width );
            // Hit AJAX endpoint and opt-in.
            jQuery.post( ajaxurl, { action: 'nf_optin', ninja_forms_opt_in: 1, send_email: sendEmail, user_email: userEmail, _wpnonce: nfAdmin.nf_optin_nonce },
                        function( response ) {
                /**
                 * When we get a response from our endpoint, show a thank you and set a timeout
                 * to close the modal.
                 */
                optinModal.setTitle( document.createElement( 'div' ).appendChild( successTitle ).innerHTML );
                optinModal.setContent( document.createElement( 'div' ).appendChild( successContent ).innerHTML );
                setTimeout (
                    function(){
                        optinModal.close();
                    },
                    2000
                );
            } );            
        } );
        // Setup the optout click event.
        jQuery( '#optout' ).click( function( e ) {
            // Disable our buttons.
            jQuery( '#optin' ).unbind( 'click' );
            jQuery( '#optout' ).unbind( 'click' );
            // Get a reference to the current width (to avoid resizing the button).
            var width = jQuery( '#optout' ).width();
            // Show spinner.
            jQuery( '#optout' ).html( '<span class="dashicons dashicons-update dashicons-update-spin"></span>' );
            jQuery( '#optout' ).width( width );
            // Hit AJAX endpoint and opt-out.
             jQuery.post( ajaxurl, { action: 'nf_optin', ninja_forms_opt_in: 0, _wpnonce: nfAdmin.nf_optin_nonce }, function( response ) {
                // When we get a response from our endpoint, close the modal. 
                optinModal.close();
            } );            
        } );
    }

    if ('1' == nfAdmin.onboardingStep) {
        jQuery('#nf-start').html(nfOBi18n.inProgress).addClass('disabled');
    }
});