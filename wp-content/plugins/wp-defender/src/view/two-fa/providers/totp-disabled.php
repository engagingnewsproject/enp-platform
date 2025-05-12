<?php
/**
 * This template is used to display Authenticator App for 2FA.
 *
 * @package WP_Defender
 */

?>
<div id="defender-totp" class="<?php echo esc_attr( $class ); ?>">
	<div class="card">
		<strong>
			<?php esc_attr_e( '1. Install the Verification app', 'wpdef' ); ?>
		</strong>
		<p>
			<?php
			esc_attr_e( 'Download and install the authenticator app on your device using the links below.', 'wpdef' );
			?>
			<select id="auth-app">
				<?php
				foreach ( $auth_apps as $key => $app ) {
					?>
					<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_attr( $app ); ?></option>
				<?php } ?>
			</select>
		</p>
		<a id="ios-app" target="_blank" href="https://itunes.apple.com/vn/app/google-authenticator/id388497605?mt=8">
			<img src="<?php defender_asset_url( '/assets/img/ios-download.svg', true ); ?>"/>
		</a>
		<a id="android-app" target="_blank"
			href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">
			<img src="<?php defender_asset_url( '/assets/img/android-download.svg', true ); ?> "/>
		</a>
		<div class="line"></div>
		<strong>
			<?php esc_attr_e( '2. Scan the QR code or enter the key', 'wpdef' ); ?>
		</strong>
		<?php if ( $is_success ) { ?>
			<p class="wd_text_wrap">
				<?php
				esc_attr_e(
					'Open the authenticator app, and scan the QR code below or manually enter the setup key to add your new site.',
					'wpdef'
				)
				?>
			</p>
			<div id="defender-qr-code"></div>
			<p class="wd_code_wrap">
				<code id="wd_clipboard"><?php echo esc_html( $secret_key ); ?></code>
				<button type="button" class="button" id="wd_copy_2fa_key"
						data-clipborad-action="copy" data-clipboard-target="#wd_clipboard">
					<?php esc_attr_e( 'Copy', 'wpdef' ); ?>
				</button>
			</p>
		<?php } else { ?>
			<p class="error_process"><?php echo esc_html( $secret_key ); ?></p>
		<?php } ?>
		<div class="line"></div>
		<strong>
			<?php esc_attr_e( '3. Enter passcode', 'wpdef' ); ?>
		</strong>
		<p>
			<?php
			esc_attr_e(
				'Enter the 6 digit passcode that is shown on your device into the input field below and hit "Verify".',
				'wpdef'
			)
			?>
		</p>
		<div class="well" style="width: auto;">
			<p class="error"></p>
			<input type="text" id="otp-code" class="def-small-text"
					placeholder="<?php esc_attr_e( 'Enter passcode', 'wpdef' ); ?>"/>
			<button type="button"
					class="button button-primary" <?php echo $is_success ? 'id="verify-otp"' : 'disabled'; ?>>
				<?php esc_attr_e( 'Verify', 'wpdef' ); ?>
			</button>
		</div>
	</div>
</div>
<script type="text/javascript">
	jQuery(function ($) {
		// Copy/past the key.
		new ClipboardJS('#wd_copy_2fa_key');
		// Toggle.
		$('body').on('click', '#field-totp', function () {
			$('#defender-totp').toggle();
		});

		var links = {
			'google-authenticator': {
				android: 'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2',
				ios: 'https://apps.apple.com/app/google-authenticator/id388497605'
			},
			'microsoft-authenticator': {
				android: 'https://play.google.com/store/apps/details?id=com.azure.authenticator',
				ios: 'https://apps.apple.com/app/microsoft-authenticator/id983156458'
			},
			'authy': {
				android: 'https://play.google.com/store/apps/details?id=com.authy.authy',
				ios: 'https://apps.apple.com/app/authy/id494168017'
			}
		};
		$('body').on('change', '#auth-app', function () {
			var value = $(this).val();
			var app = links[value];
			if (typeof app !== 'undefined') {
				$('#android-app').attr('href', app.android);
				$('#ios-app').attr('href', app.ios)
			}
		})

		function verifyOtp(el) {
			var data = {
				data: JSON.stringify({
					otp: $('#otp-code').val(),
					setup_key: $('#wd_clipboard').html(),
				})
			}
			$.ajax({
				type: 'POST',
				url: '<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
				data: data,
				beforeSend: function () {
					el.attr('disabled', 'disabled');
				},
				success: function (data) {
					if (data.success == true) {
						location.reload();
					} else {
						el.removeAttr('disabled');
						el.closest('.well').find('.error').text(data.data.message).show();
					}
				}
			});
		}

		function renderQrCode() {
			$('#defender-qr-code').empty().qrcode({
				text: '<?php echo esc_js( WP_Defender\Component\Two_Factor\Providers\Totp::generate_qr_code( $secret_key ) ); ?>',
				render: 'div',
				minVersion: 3,
				ecLevel: 'L',
				size: 180
			});
		}

		renderQrCode();

		$('body').on('click', '#verify-otp', function () {
			verifyOtp($(this));
		})

		$(window).on('keydown', function (event) {
			if (event.keyCode == 13) {
				if (jQuery(event.target).attr('id') === 'otp-code') {
					verifyOtp(jQuery(event.target))
				}
			}
		});
	})
</script>