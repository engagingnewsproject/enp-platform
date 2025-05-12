<?php
/**
 * This template is used to display reset password form.
 *
 * @package WP_Defender
 */

$error_object = new WP_Error();

$post_data = defender_get_data_from_request( null, 'p' );
if ( isset( $post_data['pass1'], $post_data['pass2'] ) && $post_data['pass1'] !== $post_data['pass2'] ) {
	$error_object->add( 'password_reset_mismatch', esc_html__( 'The passwords do not match.', 'wpdef' ) );
}
if ( ( ! $error_object->has_errors() ) && ! empty( $post_data['pass1'] ) ) {
	reset_password( $user, $post_data['pass1'] );
	login_header(
		esc_html__( 'Password Reset', 'wpdef' ),
		sprintf(
			'<p class="message reset-pass">%1$s <a href="%2$s">%3$s</a></p>',
			esc_html__( 'Your password has been reset.', 'wpdef' ),
			esc_url( wp_login_url() ),
			esc_html__( 'Log in', 'wpdef' )
		)
	);
	login_footer();
	exit;
}

do_action( 'validate_password_reset', $error_object, $user );
$login_link_separator = apply_filters( 'login_link_separator', ' | ' );

wp_enqueue_script( 'utils' );
wp_enqueue_script( 'user-profile' );

do_action( 'wd_password_change_form', $user );
login_header(
	esc_html__( 'Reset Password', 'wpdef' ),
	'<p class="message reset-pass">' . esc_html__( 'Enter your new password below or generate one.', 'wpdef' ) . '</p>',
	$error_object
);
?>
	<form name="resetpassform" id="resetpassform"
			action="<?php echo esc_url_raw( network_site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>"
			method="post" autocomplete="off">
		<input type="hidden" id="user_login" value="<?php echo esc_attr( $user->user_login ); ?>" autocomplete="off"/>
		<div class="user-pass1-wrap">
			<p><label for="pass1"><?php esc_attr_e( 'New Password', 'wpdef' ); ?></label></p>
		</div>

		<div class="wp-pwd">
			<input type="password" data-reveal="1" data-pw="<?php echo esc_attr( wp_generate_password( 16 ) ); ?>"
					name="pass1" id="pass1" class="input password-input" size="24" value="" autocomplete="off"
					aria-describedby="pass-strength-result"/>

			<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0"
					aria-label="<?php esc_attr_e( 'Hide password', 'wpdef' ); ?>">
				<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
			</button>
			<div id="pass-strength-result" class="hide-if-no-js"
				aria-live="polite"><?php esc_attr_e( 'Strength indicator', 'wpdef' ); ?></div>
		</div>
		<div class="pw-weak">
			<input type="checkbox" name="pw_weak" id="pw-weak" class="pw-checkbox"/>
			<label for="pw-weak"><?php esc_attr_e( 'Confirm use of weak password', 'wpdef' ); ?></label>
		</div>

		<p class="user-pass2-wrap">
			<label for="pass2"><?php esc_attr_e( 'Confirm new password', 'wpdef' ); ?></label><br/>
			<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off"/>
		</p>

		<p class="description indicator-hint"><?php echo esc_html( wp_get_password_hint() ); ?></p>
		<br class="clear"/>
		<?php
		do_action( 'resetpass_form', $user );
		?>

		<p class="submit reset-pass-submit">
			<button type="button" class="button wp-generate-pw hide-if-no-js"
					aria-expanded="true"><?php esc_attr_e( 'Generate Password', 'wpdef' ); ?></button>
			<input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large"
					value="<?php esc_attr_e( 'Save Password', 'wpdef' ); ?>"/>
		</p>
	</form>
	<p id="nav">
		<a href="<?php echo esc_url( wp_login_url() ); ?>"><?php esc_attr_e( 'Log in', 'wpdef' ); ?></a>
		<?php

		if ( get_option( 'users_can_register' ) ) {
			$registration_url = sprintf(
				'<a href="%s">%s</a>',
				esc_url( wp_registration_url() ),
				esc_html__( 'Register', 'wpdef' )
			);

			echo esc_html( $login_link_separator );

			/** This filter is documented in wp-includes/general-template.php */
			echo esc_url_raw( apply_filters( 'register', $registration_url ) );
		}

		?>
	</p>
<?php

login_footer( 'user_pass' );