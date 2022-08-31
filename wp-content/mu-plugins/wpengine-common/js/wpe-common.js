var url = window.location.pathname
var filename = url.substring(url.lastIndexOf('/')+1);
var warning = "Before taking this action, we at WP Engine recommend that you create a Restore Point of your site. This will allow you to undo this action within minutes.";

wpe.updates = {}; // wpe is initialized via wp_localize_script().

// Runtime jQuery
jQuery(document).ready(function($) {

	$('a[href*="wpe-user-portal"]').click(function(e){
		e.preventDefault();
		window.open("https://my.wpengine.com");
	});

	/**
	 * Bind the appropriate buttons and links to the update confirm modal.
	 */
	if( filename == 'update-core.php' && $('form.upgrade').length > 0 && wpe.popup_disabled != 1 ) {
		var $element = $('#upgrade, #upgrade-plugins, #upgrade-themes, #upgrade-plugins-2, #upgrade-themes-2');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmButton( $element );
	} else if( filename == 'plugins.php' && wpe.popup_disabled !=  1 ) {
		var $element = $('#doaction, .update-link');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmButton( $element );
	} else if( filename == 'plugin-install.php' && wpe.popup_disabled != 1 ) {
		var $element = $('a.install-now, a.update-now');
		wpe.updates.confirmInit( $element );
		wpe.updates.confirmLink( $element );
	} else if( filename == 'index.php' && wpe.popup_disabled != 1 ) { 
		var $element = $('a.install-now'); 
		wpe.updates.confirmInit( $element ); 
		wpe.updates.confirmLink( $element ); 
	}
});

/*
 * Class for managing the Deploy from staging response
 */
(function($) {

	/**
	 * Sets the initial state of the element before user interaction with the modal.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmInit = function( $element ) {
		// Initialize buttons and links with a non-confirmed status
		$element.data('confirmChange', false);
	}

	/**
	 * Intercepts the click event handler for Buttons.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmButton = function( $element ) {
		// Intercept the click handler
		$element.click(function(e) {
			if( false === $(this).data('confirmChange') ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
			wpe.updates.confirmChange( $(this) );
		});
	}

	/**
	 * Intercepts the click event handler for Links.
	 *
	 * @param  {[type]} $element jQuery element that stores the state.
	 */
	wpe.updates.confirmLink = function( $element) {
		// Intercept the click handler
		$element.click(function(e) {
			if( false === $(this).data('confirmChange') ) {
				e.preventDefault();
				e.stopImmediatePropagation();
			}
			wpe.updates.confirmChange( $(this), true );
		});
	}

	/**
	 * Displays the apprise modal and prompts the user to create a backup.
	 *
	 * @param  {[type]}  $element    The jQuery element being clicked upon.
	 * @param  {Boolean} actLikeLink Should we resume the click action or redirect to the href attribute?
	 */
	wpe.updates.confirmChange = function($element, actLikeLink) {
		// Set false as the default.
		var actLikeLink = typeof actLikeLink !== 'undefined' ?  actLikeLink : false;
		if( $element.data('confirmChange') === false ) {
			wpe.apprise(warning, {'confirm':true,'textCancel': "Yes, open my WP Engine Dashboard in a new window.",'textOk':'No thanks, I already did this.' }, function(r) {
				if(r != false) {
					if( 'function' === typeof wp.updates.installPlugin ) {
						$element.data('confirmChange', true);
						if ( $element[0].className.includes('activate-now') ) {
							window.location.href = $element.attr('href');
						} else {
							$element.click();
						}
					} else {
						if( true === actLikeLink ) {
							window.location.href = $element.attr('href');
						} else {
							$element.data('confirmChange', true);
							$element.click();
						}
					}
				} else {
					window.open('https://my.wpengine.com/installs/'+wpe.account+'/backup_points','_blank');
				}
			});
		} else {
			// Reset the button/link state.
			$element.data('confirmChange', false);
		}
	}

})(jQuery);

/**
 * Determines whether query args are present
 *
 * @param  {[type]}  str
 * @return {Boolean}
 */
function has_args(str) {
	var querystring = window.location.href.split('?',2);
	var querystring = querystring[1];
	if ( !querystring ) {
		return false;
	} else {
		if( querystring.indexOf(str) != '-1' )
		{
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Displays popup
 * http://thrivingkings.com/apprise/
 * DON'T USE THIS. USE TWITTER BOOTSTRAP MODAL INSTEAD ... see deploy from staging for example
 */
wpe.apprise = function (string, args, callback) {
	var $ = jQuery.noConflict();
	var default_args =
		{
		'confirm'		:	false, 		// Ok and Cancel buttons
		'verify'		:	false,		// Yes and No buttons
		'input'			:	false, 		// Text input (can be true or string for default text)
		'animate'		:	false,		// Groovy animation (can true or number, default is 400)
		'textOk'		:	'Ok',		// Ok button default text
		'textCancel'	:	'Cancel',	// Cancel button default text
		'textYes'		:	'Yes',		// Yes button default text
		'textNo'		:	'No',		// No button default text
		'cancelable'		: 	false,
		'options'		: 	false
		}

	if(args)
		{
		for(var index in default_args)
			{ if(typeof args[index] == "undefined") args[index] = default_args[index]; }
		}

	var aHeight = $(document).height();
	var aWidth = $(document).width();
	$('body').append('<div class="appriseOverlay" id="aOverlay"></div>');
	$('.appriseOverlay').css('height', aHeight).css('width', aWidth).fadeIn(100);
	$('body').append('<div class="appriseOuter"></div>');
	$('.appriseOuter').append('<div class="appriseInner"></div>');
	$('.appriseInner').append(string);
	$('.appriseOuter').css("left", ( $(window).width() - $('.appriseOuter').width() ) / 2+$(window).scrollLeft() + "px");
	//add a cancel button
		$(document).on('click','.closeit a', function(e) { e.preventDefault(); $('.appriseOverlay,.appriseOuter').remove(); });
	if(args) {
		if( args['cancelable'] ) {
			$('.appriseOuter').prepend('<div class="closeit"><a href="#">cancel</a></div>');
		}
		if(args['animate'])
			{
			var aniSpeed = args['animate'];
			if(isNaN(aniSpeed)) { aniSpeed = 400; }
			$('.appriseOuter').css('top', '-200px').show().animate({top:"100px"}, aniSpeed);
			}
		else
			{ $('.appriseOuter').css('top', '100px').fadeIn(200); }
		}
	else
		{ $('.appriseOuter').css('top', '100px').fadeIn(200); }


	$('.appriseInner').append('<div class="aButtons"></div>');
	if(args)
		{
		if(args['confirm'] )
			{
			$('.aButtons').append('<button value="ok">'+args['textOk']+'</button>');
			$('.aButtons').append('<button value="cancel">'+args['textCancel']+'</button>');
		}
		else if(args['verify'])
			{
			$('.aButtons').append('<button value="ok">'+args['textYes']+'</button>');
			$('.aButtons').append('<button value="cancel">'+args['textNo']+'</button>');
		}
		else if(typeof(args['options']) == 'function' ) {
			args['options']();
		}
		else if(typeof(args['options']) == 'object')
			{
				for(i = 0; i < args['options'].length; i++) {
					$('.aButtons').append('<button value="'+args['options'][i]['db_mode']+'" >'+args['options'][i]['label']+'</button>');
				}
		}
		else
			{ $('.aButtons').append('<button value="ok">'+args['textOk']+'</button>'); }
		}
	else
		{ $('.aButtons').append('<button value="ok">Ok</button>'); }
	//add in input
	if(args)
	{
	if(args['input'])
		{
		if(typeof(args['input'])=='string')
			{
			$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" value="'+args['input']+'" /></div>');
			}
		else if (typeof(args['input']) =='object')
			{
				$(args['input'].before).before('<div class="aInput"><span>'+args['input'].label+'</span><input type="text" class="aTextbox" value="'+args['input'].value+'" /></div>');
			}
		else
			{
				$('.appriseInner').append('<div class="aInput"><input type="text" class="aTextbox" t="aTextbox" /></div>');
				}
			$('.aTextbox').focus();
		}
	}

	$(document).keydown(function(e)
		{
		if($('.appriseOverlay').is(':visible'))
			{
			if(e.keyCode == 13)
				{ $('.aButtons > button[value="ok"]').click(); }
			if(e.keyCode == 27)
				{ $('.aButtons > button[value="cancel"]').click(); }
			}
		});

	var aText = $('.aTextbox').val();
	if(!aText) { aText = false; }
	$('.aTextbox').keyup(function()
		{ aText = $(this).val(); });

	$('.aButtons > button').click(function()
		{
		$('.appriseOverlay').remove();
	$('.appriseOuter').remove();
		if(callback) {
			var wButton = $(this).attr("value");
			if(wButton=='ok') {
				if(args) {
					if(args['input'])
						{ callback(aText); }
					else
						{ callback(true); }
				} else { callback(true); }
			} else if( args['options'] ) {
					return_args = { 'option_val': wButton };
					if( args['input'] ) {
						return_args.text_val = aText;
					}
					callback(return_args);
			} else if(wButton=='cancel')
				{ callback(false); }
			}
		});
}//end apprise
