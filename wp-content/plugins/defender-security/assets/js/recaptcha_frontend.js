( function( $, WPDEF ) {
	WPDEF = WPDEF || {};

	WPDEF.prepare = function() {
		// Display reCaptcha for plugin`s block. Also check if elements exists when loaded via lazy loading.
		var tryReCaptchaCounter = 0,
			wpdefRecaptchaTimer = setInterval( function() {
				if ( $( '.wpdef_recaptcha_v2_checkbox, .wpdef_recaptcha_v2_invisible' ).length > 0 ) {
					$( '.wpdef_recaptcha_v2_checkbox, .wpdef_recaptcha_v2_invisible' ).each( function() {
						var container = $( this ).find( '.wpdef_recaptcha' );

						if (
							container.is( ':empty' ) &&
							( WPDEF.vars.visibility || $( this ).is( ':visible' ) === $( this ).is( ':not(:hidden)' ) )
						) {
							var containerId = container.attr( 'id' );
							WPDEF.display( containerId );

							// disable input field in noscript
							$( this ).find( 'noscript #g-recaptcha-response' ).prop( 'disabled', true );
						}
					} );

					clearInterval( wpdefRecaptchaTimer );
				}
				tryReCaptchaCounter++;
				// Stop trying after 20 times.
				if ( tryReCaptchaCounter >= 20 ) {
					clearInterval( wpdefRecaptchaTimer );
				}
			}, 1000 );

		if ( 'v3_recaptcha' == WPDEF.options.version ) {
			grecaptcha.ready( function() {
				grecaptcha.execute( WPDEF.options.sitekey, {action: 'WPDEF_reCaptcha'}).then(function( token ) {
					document.querySelectorAll( "#g-recaptcha-response" ).forEach( function ( elem ) { elem.value = token } );
				});
			});
		}

		/*
		 * Display google reCaptcha for others blocks. It's necessary because
		 * we have disabled the connection to Google reCaptcha API from other plugins.
		 */
		if ( 'v2_checkbox' == WPDEF.options.version || 'v2_invisible' == WPDEF.options.version ) {

			$( '.g-recaptcha' ).each( function() {
				// reCAPTCHA will be generated into the empty block only.
				if ( $( this ).html() === '' && $( this ).text() === '' ) {

					// Get element`s ID.
					var container = $( this ).attr( 'id' );

					if ( typeof container == 'undefined' ) {
						container = get_id();
						$( this ).attr( 'id', container );
					}

					// Get reCaptcha parameters.
					var sitekey  = $( this ).attr( 'data-sitekey' ),
						theme    = $( this ).attr( 'data-theme' ),
						lang     = $( this ).attr( 'data-lang' ),
						size     = $( this ).attr( 'data-size' ),
						type     = $( this ).attr( 'data-type' ),
						tabindex = $( this ).attr( 'data-tabindex' ),
						callback = $( this ).attr( 'data-callback' ),
						ex_call  = $( this ).attr( 'data-expired-callback' ),
						stoken   = $( this ).attr( 'data-stoken' ),
						params   = [];

					params['sitekey'] = sitekey ? sitekey : WPDEF.options.sitekey;
					if ( !! theme ) {
						params['theme'] = theme;
					}
					if ( !! lang ) {
						params['lang'] = lang;
					}
					if ( !! size ) {
						params['size'] = size;
					}
					if ( !! type ) {
						params['type'] = type;
					}
					if ( !! tabindex ) {
						params['tabindex'] = tabindex;
					}
					if ( !! callback ) {
						params['callback'] = callback;
					}
					if ( !! ex_call ) {
						params['expired-callback'] = ex_call;
					}
					if ( !! stoken ) {
						params['stoken'] = stoken;
					}

					WPDEF.display( container, params );
				}
			} );

			// Count the number of reCAPTCHA blocks in the form.
			$( 'form' ).each( function() {
				if ( $( this ).contents().find( 'iframe[title="recaptcha widget"]' ).length > 1 && ! $( this ).children( '.grecaptcha_dublicate_error' ).length ) {
					$( this ).prepend( '<div class="grecaptcha_dublicate_error error" style="color: red;">' + WPDEF.options.error + '</div><br />\n' );
				}
			} );
		}
	};

	WPDEF.display = function( container, params ) {
		if ( typeof( container ) == 'undefined' || container == '' || typeof( WPDEF.options ) == 'undefined' ) {
			return;
		}

		// Add attribute disable to the submit.
		if ( 'v2_checkbox' === WPDEF.options.version && WPDEF.options.disable ) {
			$( '#' + container ).closest( 'form' ).find( 'input:submit, button' ).prop( 'disabled', true );
		}

		function storeEvents( el ) {
			var target = el,
				events = $._data( el.get( 0 ), 'events' );
			// Restoring events.
			if ( typeof events != 'undefined' ) {
				var storedEvents = {};
				$.extend( true, storedEvents, events );
				target.off();
				target.data( 'storedEvents', storedEvents );
			}
			// Storing and removing onclick action.
			if ( 'undefined' != typeof target.attr( 'onclick' ) ) {
				target.attr( 'wpdef-onclick', target.attr( 'onclick') );
				target.removeAttr( 'onclick' );
			}
		}

		function restoreEvents( el ) {
			var target = el,
				events = target.data( 'storedEvents' );
			// Restoring events.
			if ( typeof events != 'undefined' ) {
				for ( var event in events ) {
					for ( var i = 0; i < events[event].length; i++ ) {
						target.on( event, events[event][i] );
					}
				}
			}
			// Reset stored events.
			target.removeData( 'storedEvents' );
			// Restoring onclick action.
			if ( 'undefined' != typeof target.attr( 'wpdef-onclick' ) ) {
				target.attr( 'onclick', target.attr( 'wpdef-onclick' ) );
				target.removeAttr( 'wpdef-onclick' );
			}
		}

		function storeOnSubmit( form, grecaptcha_index ) {
			form.on( 'submit', function( e ) {
				if ( '' == form.find( '.g-recaptcha-response' ).val() ) {
					e.preventDefault();
					e.stopImmediatePropagation();
					targetObject = $( e.target || e.srcElement || e.targetObject );
					targetEvent = e.type;
					grecaptcha.execute( grecaptcha_index );
				}
			} ).find( 'input:submit, button' ).on( 'click', function( e ) {
				if ( '' == form.find( '.g-recaptcha-response' ).val() ) {
					e.preventDefault();
					e.stopImmediatePropagation();
					targetObject = $( e.target || e.srcElement || e.targetObject );
					targetEvent = e.type;
					grecaptcha.execute( grecaptcha_index );
				}
			} );
		}

		var grecaptcha_version = WPDEF.options.version;
		
		if ( 'v2_checkbox' == grecaptcha_version ) {
			if ( $( '#' + container ).parent().width() <= 300 && $( '#' + container ).parent().width() != 0 || $( window ).width() < 400 ) {
				var size = 'compact';
			} else {
				var size = 'normal';
			}
			var parameters = params ? params : { 'sitekey' : WPDEF.options.sitekey, 'theme' : WPDEF.options.theme, 'size' : size },
				block = $( '#' + container ),
				form = block.closest( 'form' );

				// Callback function works only in frontend.
				if ( ! $( 'body' ).hasClass( 'wp-admin' ) ) {
					parameters['callback'] = function() {
						form.find( 'button, input:submit' ).prop( 'disabled', false );
					};
				}

			var grecaptcha_index = grecaptcha.render( container, parameters );
			$( '#' + container ).data( 'grecaptcha_index', grecaptcha_index );
		} else if ( 'v2_invisible' == grecaptcha_version ) {
			var block = $( '#' + container ),
				form = block.closest( 'form' ),
				parameters = params ? params : { 'sitekey' : WPDEF.options.sitekey, 'size' : WPDEF.options.size, 'tabindex' : 9999, badge: 'inline' },
				targetObject = false,
				targetEvent = false;

			if ( form.length ) {
				storeEvents( form );
				form.find( 'button, input:submit' ).each( function() {
					storeEvents( $( this ) );
				} );

				// Callback function works only in frontend.
				if ( ! $( 'body' ).hasClass( 'wp-admin' ) ) {
					parameters['callback'] = function( token ) {
						form.off();
						restoreEvents( form );
						form.find( 'button, input:submit' ).off().each( function() {
							restoreEvents( $( this ) );
						} );
						if ( targetObject && targetEvent ) {
							targetObject.trigger( targetEvent );
						}
						form.find( 'button, input:submit' ).each( function() {
							storeEvents( $( this ) );
						} );
						storeEvents( form );
						storeOnSubmit( form, grecaptcha_index );
						grecaptcha.reset( grecaptcha_index );
					};
				}

				var grecaptcha_index = grecaptcha.render( container, parameters );
				block.data( { 'grecaptcha_index' : grecaptcha_index } );

				if ( ! $( 'body' ).hasClass( 'wp-admin' ) ) {
					storeOnSubmit( form, grecaptcha_index );
				}
			}
		}
	};

	$( function() {
		var tryCounter = 0,
			wpdef_timer = setInterval( function() {
				if ( typeof Recaptcha != "undefined" || typeof grecaptcha != "undefined" ) {
					try {
						WPDEF.prepare();
					} catch ( e ) {
						console.log( 'Unexpected error occurred: ', e );
					}
					clearInterval( wpdef_timer );
				}
				tryCounter++;
				// Stop trying after 10 times.
				if ( tryCounter >= 10 ) {
					clearInterval( wpdef_timer );
				}
			}, 1000 );

		function wpdef_prepare() {
			if ( typeof Recaptcha != "undefined" || typeof grecaptcha != "undefined" ) {
				try {
					WPDEF.prepare();
				} catch ( err ) {
					console.log( err );
				}
			}
		}

		$( window ).on( 'load', wpdef_prepare );
	} );

	function get_id() {
		var id = 'wpdef_recaptcha_' + Math.floor( Math.random() * 1000 );
		if ( $( '#' + id ).length ) {
			id = get_id();
		} else {
			return id;
		}
	}

} )( jQuery, WPDEF );
