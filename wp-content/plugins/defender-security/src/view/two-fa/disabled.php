<style type="text/css">
    #defender-security .card {
        padding: 30px;
    }

    #defender-security .card strong {
        margin-bottom: 10px;
        display: inline-block;
    }

    #defender-security .card p {
        margin-bottom: 15px;
    }

    #defender-security .card .line {
        margin-bottom: 15px;
    }

    #defender-security .card p select {
        display: block;
        margin-top: 5px;
    }

    #defender-security .well {
        border-radius: 5px;
        background-color: #F9F9F9;
        padding: 20px 15px;
    }

    #defender-security .well .error {
        display: none;
        color: #FF6D6D;
        font-size: 12px !important;
        font-weight: 500;
    }
</style>
<h2><?php
	_e( 'Security', 'wpdef' ) ?></h2>
<table class="form-table" id="defender-security">
    <tbody>
    <tr class="user-sessions-wrap hide-if-no-js">
        <th>
			<?php
			_e( 'Two Factor Authentication', 'wpdef' )
			?>
        </th>
        <td aria-live="assertive">
			<?php
			if ( $is_force_auth ) :
				?>
                <div class="def-warning">
                    <i class="dashicons dashicons-warning" aria-hidden="true"></i>
					<?php
					echo ( ! empty( $force_auth_message ) ) ? $force_auth_message : $default_message;
					?>
                </div>
			<?php
			endif;
			?>
            <div id="def2">
                <div class="destroy-sessions">
                    <button type="button" class="button" id="defender-2fa-toggle">
						<?php
						_e( 'Enable', 'wpdef' )
						?>
                    </button>
                </div>
                <p class="description">
					<?php
					_e( 'Use the authenticator apps mentioned below to sign in and verify the passcode.', 'wpdef' )
					?>
                </p>
            </div>
            <div id="defender-2fa">
                <div class="card">
                    <strong>
						<?php
						_e( '1. Install the Verification app', 'wpdef' )
						?>
                    </strong>
                    <p>
						<?php
						_e(
							'Download and install the authenticator app on your device using the links below.',
							'wpdef'
						)
						?>
                        <select id="auth-app">
                            <option value="google-authenticator">Google Authenticator</option>
                            <option value="microsoft-authenticator">Microsoft Authenticator</option>
                            <option value="authy">Authy</option>
                        </select>
                    </p>
                    <a id="ios-app" target="_blank" href="https://itunes.apple.com/vn/app/google-authenticator/id388497605?mt=8">
                        <img src="
						<?php
						echo defender_asset_url( '/assets/img/ios-download.svg' )
						?>
						"/>
                    </a>
                    <a id="android-app" target="_blank"
                       href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">
                        <img src="
						<?php
						echo defender_asset_url( '/assets/img/android-download.svg' )
						?>
						"/>
                    </a>
                    <div class="line"></div>
                    <strong>
						<?php
						_e( '2. Scan the barcode', 'wpdef' )
						?>
                    </strong>
                    <p>
						<?php
						_e(
							"Open the authenticator app you just downloaded, and after adding your new site, use your phone's camera to scan the barcode below.",
							'wpdef'
						)
						?>
                    </p>
					<?php
					\WP_Defender\Component\Two_Fa::generate_qr_code()
					?>
                    <div class="line"></div>
                    <strong>
						<?php
						_e( '3. Enter passcode', 'wpdef' )
						?>
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
                        <input type="text" id="otp-code" class="def-small-text" placeholder="<?php
						_e( 'Enter passcode', 'wpdef' )
						?>
						">
                        <button type="button" class="button button-primary" id="verify-otp">
							<?php
							_e( 'Verify', 'wpdef' )
							?>
                        </button>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    </tbody>
</table>
<script type="text/javascript">
    jQuery(function ($) {
        $('#defender-2fa').hide();
        $('body').on('click', '#defender-2fa-toggle', function () {
            var el = $('#defender-2fa');
            if (el.is(':visible')) {
                el.hide();
                $(this).text('Enable')
            } else {
                el.show();
                $(this).text('Cancel')
            }
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
                url: '<?php echo $url ?>',
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
<?php
if ( $is_force_auth ) :
	?>
    <script type="text/javascript">
        jQuery(function ($) {
            $('html, body').animate({scrollTop: $("#defender-2fa-toggle").offset().top}, 1000);
        });
    </script>
<?php
endif;
?>
