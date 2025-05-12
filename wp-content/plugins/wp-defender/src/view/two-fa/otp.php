<?php
/**
 * This template is used to display 2FA OTP field.
 *
 * @package WP_Defender
 */

if ( ! function_exists( 'login_header_otp' ) ) {
	// Copy from wp-login.php.
	/**
	 * Output the login page header.
	 *
	 * @param  string   $title  Optional. WordPress login Page title to display in the `<title>` element. Default 'Log In'.
	 * @param  string   $message  Optional. Message to display in header. Default empty.
	 * @param  bool     $show_logo  Optional. Show/hide header logo. Default true.
	 * @param  WP_Error $wp_error  Optional. The error to pass. Default empty.
	 */
	function login_header_otp( $title = 'Log In', $message = '', $show_logo = true, $wp_error = '' ) {
		global $error, $interim_login, $action;

		if ( empty( $wp_error ) ) {
			$wp_error = new WP_Error();
		}

		// Shake it!
		$shake_error_codes = array(
			'empty_password',
			'empty_email',
			'invalid_email',
			'invalidcombo',
			'empty_username',
			'invalid_username',
			'incorrect_password',
		);
		/**
		 * Filters the error codes array for shaking the login form.
		 *
		 * @param  array  $shake_error_codes  Error codes that shake the login form.
		 *
		 * @since 3.0.0
		 */
		$shake_error_codes = apply_filters( 'shake_error_codes', $shake_error_codes );
		if ( $shake_error_codes && $wp_error->get_error_code() && in_array(
			$wp_error->get_error_code(),
			$shake_error_codes,
			true
		) ) {
			add_action( 'login_head', 'wp_shake_js', 12 );
		}

		$separator = is_rtl() ? ' &rsaquo; ' : ' &lsaquo; ';

		?><!DOCTYPE html>
<!--[if IE 8]>
<html xmlns="http://www.w3.org/1999/xhtml" class="ie8"
		<?php
		language_attributes();
		?>
>
<![endif]-->
<!--[if !(IE 8) ]><!-->
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<!--<![endif]-->

<head>
	<meta http-equiv="Content-Type"
			content="<?php bloginfo( 'html_type' ); ?>; charset=<?php bloginfo( 'charset' ); ?>"/>
	<meta name="viewport" content="width=device-width">
	<title>
		<?php
		printf( '%s%s%s', esc_attr( get_bloginfo( 'name', 'display' ) ), esc_attr( $separator ), esc_attr( $title ) );
		?>
	</title>
		<?php

		wp_enqueue_style( 'login' );

		/*
		 * Remove all stored post data on logging out.
		 * This could be added by add_action('login_head'...) like wp_shake_js(),
		 * but maybe better if it's not removable by plugins.
		 */
		if ( 'loggedout' === $wp_error->get_error_code() ) {
			?>
		<script>if ("sessionStorage" in window) {
				try {
					for (var key in sessionStorage) {
						if (key.indexOf("wp-autosave-") != -1) {
							sessionStorage.removeItem(key)
						}
					}
				} catch (e) {
				}
			}
		</script>
			<?php
		}

		/**
		 * Enqueue scripts and styles for the login page.
		 *
		 * @since 3.1.0
		 */
		do_action( 'login_enqueue_scripts' );

		/**
		 * Fires in the login page header after scripts are enqueued.
		 *
		 * @since 2.1.0
		 */
		do_action( 'login_head' );

		if ( is_multisite() ) {
			$login_header_url   = network_home_url();
			$login_header_title = get_network()->site_name;
		} else {
			$login_header_url   = 'https://wordpress.org/';
			$login_header_title = esc_html__( 'Powered by WordPress', 'wpdef' );
		}

		/**
		 * Filters link URL of the header logo above login form.
		 *
		 * @param  string  $login_header_url  Login header logo URL.
		 *
		 * @since 2.1.0
		 */
		$login_header_url = apply_filters( 'login_headerurl', $login_header_url );

		/**
		 * Filters the title attribute of the header logo above login form.
		 *
		 * @param  string  $login_header_title  Login header logo title attribute.
		 *
		 * @since 2.1.0
		 */
		$login_header_title = apply_filters( 'login_headertitle', $login_header_title );

		$classes = array( 'login-action-' . $action, 'wp-core-ui' );
		if ( is_rtl() ) {
			$classes[] = 'rtl';
		}
		if ( $interim_login ) {
			$classes[] = 'interim-login';
			?>
		<style type="text/css">
			html {
				background-color: transparent;
			}
		</style>
			<?php

			if ( 'success' === $interim_login ) {
				$classes[] = 'interim-login-success';
			}
		}
		$classes[] = ' locale-' . sanitize_html_class( strtolower( str_replace( '_', '-', get_locale() ) ) );

		/**
		 * Filters the login page body classes.
		 *
		 * @param  array   $classes  An array of body classes.
		 * @param  string  $action  The action that brought the visitor to the login page.
		 *
		 * @since 3.5.0
		 */
		$classes = apply_filters( 'login_body_class', $classes, $action );

		?>
</head>

<body class="login <?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<?php
		/**
		 * Fires in the login page header after the body tag is opened.
		 *
		 * @since 4.6.0
		 */
		do_action( 'login_header' );
		?>
<div id="login">
		<?php if ( true === $show_logo ) { ?>
		<h1>
			<a id="otp-logo" href="<?php echo esc_url( $login_header_url ); ?>"
				title="<?php echo esc_attr( $login_header_title ); ?>" tabindex="-1"><?php bloginfo( 'name' ); ?></a>
		</h1>
			<?php
		}
		unset( $login_header_url, $login_header_title );
		/**
		 * Filters the message to display above the login form.
		 *
		 * @param  string  $message  Login message text.
		 *
		 * @since 2.1.0
		 */
		$message = apply_filters( 'login_message', $message );
		if ( ! empty( $message ) ) {
			echo wp_kses_post( $message );
		}

		// In case a plugin uses $error rather than the $wp_errors object.
		if ( ! empty( $error ) ) {
			$wp_error->add( 'error', $error );
			unset( $error );
		}

		if ( $wp_error->get_error_code() ) {
			$errors   = '';
			$messages = '';
			foreach ( $wp_error->get_error_codes() as $code ) {
				$severity = $wp_error->get_error_data( $code );
				foreach ( $wp_error->get_error_messages( $code ) as $error_message ) {
					if ( 'message' === $severity ) {
						$messages .= '	' . $error_message . "<br />\n";
					} else {
						$errors .= '	' . $error_message . "<br />\n";
					}
				}
			}
			if ( ! empty( $errors ) ) {
				/**
				 * Filters the error messages displayed above the login form.
				 *
				 * @param  string  $errors  Login error message.
				 *
				 * @since 2.1.0
				 */
				echo wp_kses_post( sprintf( '<div id="login_error" class="notice notice-error">%s</div>', apply_filters( 'login_errors', $errors ) ) );
			}
			if ( ! empty( $messages ) ) {
				/**
				 * Filters instructional messages displayed above the login form.
				 *
				 * @param  string  $messages  Login messages.
				 *
				 * @since 2.5.0
				 */
				echo wp_kses_post( sprintf( '<p class="message">%s</p>', apply_filters( 'login_messages', $messages ) ) );
			}
		}
	}
}

$show_logo = WP_Defender\Model\Setting\Two_Fa::CUSTOM_GRAPHIC_TYPE_NO !== $custom_graphic_type;
if ( isset( $interim_login ) && 'success' === $interim_login ) {
	login_header_otp( '', $message, $show_logo );

	$modal_close_script = <<<END
		<script>
		jQuery(function ($) {
			$('.wp-auth-check-close', window.parent.document).trigger('click');
		});
		</script>
END;

	add_action(
		'wp_print_footer_scripts',
		function () use ( $modal_close_script ) {
			/**
			 * Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			 * Why?
			 * We don't want to escape scripts here.
			 */
			echo $modal_close_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	);
} else {
	login_header_otp( '', '', $show_logo, $error );

	if ( ! empty( $providers ) ) {
		foreach ( $providers as $slug => $provider ) {
			?>
				<form method="post" class="wpdef-2fa-form" id="wpdef-2fa-form-<?php echo esc_attr( $slug ); ?>" action="
				<?php
				echo esc_url(
					add_query_arg(
						'action',
						'defender-verify-otp',
						site_url( 'wp-login.php', 'login_post' )
					)
				);
				?>
					">

				<?php $provider->authentication_form(); ?>

					<input type="hidden" name="auth_method" value="<?php echo esc_attr( $slug ); ?>"/>
					<input type="hidden" name="login_token" value="<?php echo esc_attr( $token ); ?>"/>
					<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>"/>
					<input type="hidden" name="password" value="<?php echo esc_attr( $password ); ?>"/>
					<input type="hidden" name="requested_user" value="<?php echo esc_attr( $user_id ); ?>"/>
				<?php
				if ( ! empty( defender_get_data_from_request( 'interim-login', 'r' ) ) ) {
					?>
						<input type="hidden" name="interim-login" value="1"/>
					<?php
				}
				wp_nonce_field( 'verify_otp' );
				?>
				</form>
				<?php
		}
		if ( ( is_array( $providers ) || $providers instanceof Countable ? count( $providers ) : 0 ) > 1 ) {
			?>
				<div id="wrap-nav">
					<p><?php esc_attr_e( 'Having problems? Try another way to log in', 'wpdef' ); ?></p>
					<ul id="nav">
					<?php foreach ( $providers as $slug => $provider ) { ?>
							<li class="wpdef-2fa-link" id="wpdef-2fa-link-<?php echo esc_attr( $slug ); ?>"
								data-slug="<?php echo esc_attr( $slug ); ?>">

								<?php echo $provider->get_login_label(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

							</li>
						<?php } ?>
					</ul>
					<img class="def-ajaxloader" src="<?php defender_asset_url( '/assets/img/spinner.svg', true ); ?>"/>
				</div>
			<?php } ?>
			<p class="notification"></p>
		<?php } ?>
		<?php if ( $custom_graphic ) { ?>
			<style type="text/css">
				body.login div#login h1 a {
					background-image: url("<?php echo esc_url_raw( $custom_graphic ); ?>");
				}
			</style>
		<?php } ?>
		<?php
		$totp_script = <<<END
		<script>
		jQuery(function ($) {
            function two_factor_providers( defaultSlug ) {
                // Hide all forms and show the one default.
                $('.wpdef-2fa-form').hide();
                $('#wpdef-2fa-form-' + defaultSlug).show();
                // Hide all links and show others except the default.
                if ( $('.wpdef-2fa-link').length > 0 ) {
                    $('.wpdef-2fa-link').hide();
                    $('.wpdef-2fa-link:not(#wpdef-2fa-link-'+ defaultSlug +')').each(function(){
                        $(this).show();
                    });
                }
                // Focus on the current input-field.
                if ($('#wpdef-2fa-form-' + defaultSlug).find('[name="otp"]').length > 0) {
                    $('#wpdef-2fa-form-' + defaultSlug).find('[name="otp"]').focus();
                }
            }
            var is_sent = false;
            // Logic for FallbackEmail method.
            function resend_code() {
                // Work with the button 'Resen Code'.
                var that = $('input[name="button_resend_code"]');
                if (is_sent === false) {
                    is_sent = true;
                }
                let data = {
                    data: JSON.stringify({
                        'token': '{$token}',
                        'requested_user': '{$user_id}'
                    })
                };
                $.ajax({
                    type: 'POST',
                    url: '{$action_fallback_email}',
                    data: data,
                    beforeSend: function () {
                        that.attr('disabled', 'disabled');
                        $('.def-ajaxloader').show();
                    },
                    success: function (data) {
                        that.removeAttr('disabled');
                        $('.def-ajaxloader').hide();
                        $('.notification').text(data.data.message);
                        is_sent = false;
                    }
                })
            }

            $('.def-ajaxloader').hide();
            // Hide all forms and show the one default.
            var defaultSlug = '{$default_slug}';
            if ( ! defaultSlug && 0 < $( 'form.wpdef-2fa-form' ).first().length ) {
                var twoFaFormId = $( 'form.wpdef-2fa-form' ).first().attr( 'id' );
                defaultSlug = twoFaFormId.replace( 'wpdef-2fa-form-', '' );
                if ( 'fallback-email' === defaultSlug ) {
                    resend_code();
                }
            }
            two_factor_providers( defaultSlug );
            // Work with links.
            $('body').on('click', '.wpdef-2fa-link', function () {
                $('#login_error').remove();
                // Clear any previous notification.
                $('.notification').empty();
                var slug = $(this).data('slug');
                two_factor_providers( slug );
                // Switch to 'Fallback Email' method.
                if ('fallback-email' === slug) {
                    resend_code();
                }
            });
            // Resend code.
            $('body').on('click', 'input[name="button_resend_code"]', function (e) {
                e.preventDefault();
                resend_code();
            })
        })
		</script>
END;
		add_action(
			'wp_print_footer_scripts',
			function () use ( $totp_script ) {
				/**
				 * Ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				 * Why?
				 * We don't want to escape scripts here.
				 */
				echo $totp_script; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		);
		?>
		<?php
}
?>

	<?php
	if ( ! function_exists( 'login_footer' ) ) {
		/**
		 * Outputs the footer for the login page.
		 *
		 * @param  string $input_id  Which input to auto-focus.
		 */
		function login_footer( $input_id = '' ) {
			global $interim_login;

			// Don't allow interim logins to navigate away from the page.
			if ( ! $interim_login ) :
				?>
		<p id="backtoblog"><a href="
					<?php
					echo esc_url( home_url( '/' ) );
					?>
		">
					<?php
					printf(
						/* translators: %s: site title */
						esc_html_x( '&larr; Back to %s', 'site', 'wpdef' ),
						esc_html( get_bloginfo( 'title', 'display' ) )
					);
					?>
			</a></p>
				<?php
		endif;
			?>

</div>

			<?php
			if ( ! empty( $input_id ) ) :
				?>
	<script type="text/javascript">
		try {
			document.getElementById('<?php echo esc_js( $input_id ); ?>').focus();
		} catch (e) {
		}
		if (typeof wpOnload == 'function') wpOnload();
	</script>
				<?php
	endif;
			?>

			<?php
			/**
			 * Fires in the login page footer.
			 *
			 * @since 3.1.0
			 */
			do_action( 'login_footer' );
			?>
<div class="clear"></div>
</body>

</html>
			<?php
		}
	}
	login_footer();
	?>