<?php
wp_nonce_field( 'wpdef_2fa_user_options', '_wpdef_2fa_nonce_user_options', false );
?>
<input type="hidden" name="<?php echo esc_attr( $enabled_providers_key ); ?>[]" value=""/>
<h2><?php _e( 'Security', 'wpdef' ) ?></h2>
<table class="form-table" id="defender-security">
	<tr class="user-sessions-wrap hide-if-no-js">
		<th>
			<?php _e( 'Two-Factor Authentication', 'wpdef' ); ?>
		</th>
		<td>
			<?php if ( $is_force_auth ) : ?>
				<div class="def-notification" style="margin-right: 0;margin-top: 0;">
					<i class="dashicons dashicons-warning" aria-hidden="true"></i>
					<?php echo ( ! empty( $force_auth_message ) ) ? $force_auth_message : $default_message; ?>
				</div>
			<?php endif; ?>
			<table class="auth-methods-table">
				<thead>
					<tr>
						<th class="col-enabled" scope="col"><?php esc_html_e( 'Default', 'two-factor' ); ?></th>
						<th class="col-primary" scope="col" colspan="2">
							<?php esc_html_e( '2FA Method', 'two-factor' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $all_providers as $slug => $object ) :
					/**
					 * Fires before user options are shown.
					 *
					 * @since 2.8.0
					 * @param WP_User $user The user.
					 */
					do_action( 'wd_2fa_init_provider_' . $slug, $user ); ?>
					<tr>
						<th scope="row" class="radio-button">
							<input type="radio" name="<?php echo esc_attr( $default_provider_key ); ?>"
						        value="<?php echo esc_attr( $slug ); ?>" <?php checked( $slug, $checked_def_provider_slug ); ?> />
						</th>
						<th scope="row" class="toggles">
							<input type="checkbox" class="wpdef-ui-toggle"
							       id="field-<?php echo esc_attr( $slug ); ?>"
							       name="<?php echo esc_attr( $enabled_providers_key ); ?>[]"
							       value="<?php echo esc_attr( $slug ); ?>"
								<?php checked( in_array( $slug, $checked_provider_slugs, true ) ); ?> />
						</th>
						<td>
							<strong>
								<?php
								// No use esc_html() because there might be links.
								echo $object->get_label();
								?>
							</strong>
							<p class="<?php echo esc_attr( $slug ); ?>-provider-text">
								<?php echo esc_html( $object->get_description() ); ?>
							</p>
							<?php
							/**
							 * Fires after user options are shown.
							 *
							 * @since 2.8.0
							 * @param WP_User $user The user.
							 */
							do_action( 'wd_2fa_user_options_' . $slug, $user );
							?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</td>
	</tr>
</table>
<script type="text/javascript">
    jQuery(function ($) {
	    <?php if ( $is_force_auth ) { ?>
            $('html, body').animate({scrollTop: $(".auth-methods-table").offset().top}, 1000);
	    <?php } ?>
    })
</script>
