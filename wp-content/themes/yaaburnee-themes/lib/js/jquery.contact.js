/* -------------------------------------------------------------------------*
 * 						CONTACT FORM EMAIL VALIDATION	
 * -------------------------------------------------------------------------*/
	"use strict";
			
	function Validate() {

		var errors = false;
		var reason_name = "";
		var reason_mail = "";
		var reason_message = "";

		reason_name += validateName(document.getElementById('contactform').name);
		reason_mail += validateEmail(document.getElementById('contactform').email);
		reason_message += validateMessage(document.getElementById('contactform').comments);


		if (reason_name != "") {
			jQuery("#message").fadeIn(1000);
			jQuery("#message ul.error_messages li.name").text(reason_name).fadeIn(1000);
			jQuery("#contactform #name").css({"border-bottom" : "solid 1px red"});
			errors = true;
		} else {
			jQuery("#message ul.error_messages li.name").fadeOut('fast');
			jQuery("#contactform #name").css({"border-bottom" : "solid 1px #b4b4b4"});
		}


		if (reason_mail != "") {
			jQuery("#message").fadeIn(1000);
			jQuery("#message ul.error_messages li.email").text(reason_mail).fadeIn(1000);
			jQuery("#contactform #email").css({"border-bottom" : "solid 1px red"});
			errors = true;
		} else {
			jQuery("#message ul.error_messages li.email").fadeOut('fast');
			jQuery("#contactform #email").css({"border-bottom" : "solid 1px #b4b4b4"});
		}
		
		if (reason_message != "") {
			jQuery("#message").fadeIn(1000);
			jQuery("#message ul.error_messages li.message").text(reason_message).fadeIn(1000);
			jQuery("#contactform #comments").css({"border-bottom" : "solid 1px red"});
			errors = true;
		} else {
			jQuery("#message ul.error_messages li.message").fadeOut('fast');
			jQuery("#contactform #comments").css({"border-bottom" : "solid 1px #b4b4b4"});
		}
		
		if (errors == false){
			jQuery("#message ul.error_messages,#message,#message .error_message").fadeOut('fast');
			return true;
		} else {
			return false;
		}
		
		//document.getElementById("writecomment").submit(); return false;
	}
	
	
/* -------------------------------------------------------------------------*
 * 						SUBMIT CONTACT FORM	
 * -------------------------------------------------------------------------*/
 	jQuery(document).ready(function(jQuery){
		var adminUrl = df.adminUrl;
		jQuery('#contactform #submit').click(function() {
			if (Validate()==true) {
				var str = jQuery("#contactform").serialize();
				jQuery.ajax({
					url:adminUrl,
					type:"POST",
					data:"action=contact_form&"+str,
					success:function(results) {	
						jQuery("#contactform").fadeOut('fast');
						jQuery("#message").css('display', 'block');

						jQuery("#message .success").slideDown('slow');
					}
				});
			}
			return false;
		});
		return false;
	});