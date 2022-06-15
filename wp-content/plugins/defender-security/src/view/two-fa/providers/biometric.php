<?php
declare( strict_types = 1 );

foreach ( $notices as $notice ) { ?>
	<div class="wpdef-notice <?php echo $notice['type'] . ' ' . $notice['extra_class']; ?>"
		<?php echo $notice['style'] ? 'style="' . $notice['style'] . '"' : ''; ?>>
		<p>
			<span class="dashicons dashicons-<?php echo $notice['type']; ?>"></span>
			<span class="wpdef-notice-message"><?php echo $notice['message']; ?></span>
		</p>
	</div>
<?php } ?>

<script type="text/javascript">
	jQuery( function( $ ) {
		maybeShowAdditionalAuthMethodNotice();
		$( 'body' ).on( 'click', '.auth-methods-table .wpdef-ui-toggle', maybeShowAdditionalAuthMethodNotice );

		$( '.wpdef-notice.has-server-error .browser-incompatible-msg, .wpdef-notice.browser-notice' ).hide();
		if ( ! checkBrowserSupportForWebauthn() ) {
			disableWebauthnFeature();
			$( '.wpdef-notice.has-server-error .browser-incompatible-msg, .wpdef-notice.browser-notice' ).show();
		}

		function checkBrowserSupportForWebauthn() {
			return 'undefined' !== typeof PublicKeyCredential && ( 'credentials' in navigator );
		}

		function disableWebauthnFeature() {
			$( '.defender-biometric-wrap .wpdef-device-btn' ).attr( 'disabled', 'disabled' );
		}

		function maybeShowAdditionalAuthMethodNotice() {
			let $authTable = $( '.auth-methods-table' );

			if (
				1 === $authTable.find( '.wpdef-ui-toggle:checked' ).length &&
				1 === $authTable.find( '#field-webauthn:checked' ).length
			) {
				$authTable.find( '.wpdef-notice.additional-2fa-method' ).show();
			} else {
				$authTable.find( '.wpdef-notice.additional-2fa-method' ).hide();
			}
		}
	} );
</script>
