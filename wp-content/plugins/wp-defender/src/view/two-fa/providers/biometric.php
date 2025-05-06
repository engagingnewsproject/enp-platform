<?php
/**
 * This template is used to display 2FA notices.
 *
 * @package WP_Defender
 */

foreach ( $notices as $notice ) {
	$class   = array( 'wpdef-notice', $notice['type'], $notice['extra_class'] );
	$class[] = ! empty( $notice['is_dismissible'] ) ? 'is-dismissible' : '';
	$class   = implode( ' ', array_filter( $class ) );
	?>
	<div class="<?php echo esc_attr( $class ); ?>" <?php echo $notice['style'] ? 'style="' . esc_attr( $notice['style'] ) . '"' : ''; ?>>
		<p>
			<span class="dashicons dashicons-<?php echo esc_attr( $notice['type'] ); ?>"></span>
			<span class="wpdef-notice-message"><?php echo wp_kses_post( $notice['message'] ); ?></span>
		</p>
		<?php
		if ( ! empty( $notice['is_dismissible'] ) ) {
			?>
			<button type="button" class="notice-dismiss">
				<span class="screen-reader-text"><?php esc_attr_e( 'Dismiss', 'wpdef' ); ?></span>
			</button>
			<?php
		}
		?>
	</div>
<?php } ?>

<script type="text/javascript">
	jQuery(function ($) {
		maybeShowAdditionalAuthMethodNotice();
		$('body').on('click', '.auth-methods-table .wpdef-ui-toggle', maybeShowAdditionalAuthMethodNotice);

		$('.wpdef-notice.has-server-error .browser-incompatible-msg, .wpdef-notice.browser-notice').hide();
		if (!checkBrowserSupportForWebauthn()) {
			disableWebauthnFeature();
			$('.wpdef-notice.has-server-error .browser-incompatible-msg, .wpdef-notice.browser-notice').show();
		}

		function checkBrowserSupportForWebauthn() {
			return 'undefined' !== typeof PublicKeyCredential && ('credentials' in navigator);
		}

		function disableWebauthnFeature() {
			$('.defender-biometric-wrap .wpdef-device-btn').attr('disabled', 'disabled');
		}

		function maybeShowAdditionalAuthMethodNotice() {
			let $authTable = $('.auth-methods-table');

			if (
				1 === $authTable.find('.wpdef-ui-toggle:checked').length &&
				1 === $authTable.find('#field-webauthn:checked').length
			) {
				$authTable.find('.wpdef-notice.additional-2fa-method').show();
			} else {
				$authTable.find('.wpdef-notice.additional-2fa-method').hide();
			}
		}
	});
</script>