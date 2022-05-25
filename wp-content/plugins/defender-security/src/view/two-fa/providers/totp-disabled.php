<div id="defender-totp" class="<?php echo esc_attr( $class ); ?>">
	<div class="card">
		<strong>
			<?php _e( '1. Install the Verification app', 'wpdef' ); ?>
		</strong>
		<p>
			<?php
			_e( 'Download and install the authenticator app on your device using the links below.', 'wpdef' );
			?>
			<select id="auth-app">
				<?php
				foreach ( $auth_apps as $key => $app ) { ?>
					<option value="<?php echo $key; ?>"><?php echo $app; ?></option>
				<?php } ?>
			</select>
		</p>
		<a id="ios-app" target="_blank" href="https://itunes.apple.com/vn/app/google-authenticator/id388497605?mt=8">
			<img src="<?php echo defender_asset_url( '/assets/img/ios-download.svg' ); ?>" />
		</a>
		<a id="android-app" target="_blank"
		   href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">
			<img src="<?php echo defender_asset_url( '/assets/img/android-download.svg' ); ?> "/>
		</a>
		<div class="line"></div>
		<strong>
			<?php _e( '2. Scan the QR code or enter the key', 'wpdef' ); ?>
		</strong>
		<p class="wd_text_wrap">
			<?php
			_e(
				'Open the authenticator app, and scan the QR code below or manually enter the setup key to add your new site.',
				'wpdef'
			)
			?>
		</p>
		<?php
		WP_Defender\Component\Two_Factor\Providers\Totp::generate_qr_code( $secret_key );
		?>
		<p class="wd_code_wrap">
			<code id="wd_clipboard"><?php echo esc_html( $secret_key ); ?></code>
			<button type="button" class="button" id="wd_copy_2fa_key"
			        data-clipborad-action="copy" data-clipboard-target="#wd_clipboard">
				<?php _e( 'Copy', 'wpdef' ); ?>
			</button>
		</p>
		<div class="line"></div>
		<strong>
			<?php _e( '3. Enter passcode', 'wpdef' ); ?>
		</strong>
		<p>
			<?php
			_e(
				'Enter the 6 digit passcode that is shown on your device into the input field below and hit "Verify".',
				'wpdef'
			)
			?>
		</p>
		<div class="well" style="width: auto;">
			<p class="error"></p>
			<input type="text" id="otp-code" class="def-small-text"
			       placeholder="<?php _e( 'Enter passcode', 'wpdef' ); ?>" />
			<button type="button" class="button button-primary" id="verify-otp">
				<?php _e( 'Verify', 'wpdef' ); ?>
			</button>
		</div>
	</div>
</div>
<script type="text/javascript">
    jQuery(function ($) {
        // Copy/past the key.
        new ClipboardJS('#wd_copy_2fa_key');
		// Toggle.
        $('body').on('click', '#field-totp', function(){
            $( '#defender-totp' ).toggle();
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

        function verify_otp(el) {
            var data = {
                data: JSON.stringify({
                    otp: $('#otp-code').val(),
                })
            }
            $.ajax({
                type: 'POST',
                url: '<?php echo $url; ?>',
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

        $('body').on('click', '#verify-otp', function () {
            verify_otp($(this));
        })

        $(window).on('keydown', function (event) {
            if (event.keyCode == 13) {
                if (jQuery(event.target).attr('id') === 'otp-code') {
                    verify_otp(jQuery(event.target))
                }
            }
        });
    })
</script>
