<?php
/**
 * This template is used to display 2FA backup codes.
 *
 * @package WP_Defender
 */

?>
<p id="wpdef-2fa-backup-codes">
	<button type="button" class="button button-wpdef-2fa-backup-codes-generate button-secondary hide-if-no-js"
		<?php echo esc_attr( $attr ); ?>>
		<?php echo esc_html( $button_text ); ?>
	</button>
	<span class="wpdef-2fa-backup-codes-count <?php echo esc_attr( $class ); ?>">
		<?php echo esc_html( $number_of_codes ); ?>
		<input type="hidden" name="wpdef_need_notice" id="wpdef-need-notice"
				value="<?php echo esc_attr( $show_notice ); ?>"/>
	</span>
</p>
<?php if ( 'yes' === $show_notice ) { ?>
	<div id="backup-codes-notification" class="def-notification <?php echo esc_attr( $class ); ?>">
		<span class="dashicons dashicons-warning"></span>
		<?php esc_attr_e( 'Generate new backup codes by clicking the button above.', 'wpdef' ); ?>
	</div>
<?php } ?>
<div class="wpdef-2fa-backup-codes-wrapper" style="display: none">
	<p class="description">
		<?php
		esc_html_e(
			'Ensure your backup codes are saved in a safe and accessible place. You won’t be able to view these codes again.',
			'wpdef'
		);
		?>
	</p>
	<p class="download-button">
		<a class="button button-primary button-wpdef-2fa-backup-codes-download button-secondary hide-if-no-js"
			href="javascript:void(0);" id="wpdef-2fa-backup-codes-download-link"
			download="<?php echo esc_attr( $filename ); ?>"><?php esc_html_e( 'Download Codes', 'wpdef' ); ?></a>
	<p>
	<div class="wpdef-2fa-backup-codes-unused-codes"></div>
</div>
<script type="text/javascript">
	(function ($) {
		// Toggle.
		$('body').on('click', '#field-backup-codes', function () {
			var el = $('.button-wpdef-2fa-backup-codes-generate');
			var textWithCodes = $('.wpdef-2fa-backup-codes-count');
			// When 0 codes.
			var needNotice = $('#wpdef-need-notice').val();
			if (this.checked) {
				el.prop('disabled', false);
				textWithCodes.show();
				if (needNotice === 'yes') {
					$('.def-notification').show();
				}
			} else {
				el.prop('disabled', true);
				textWithCodes.hide();
				if (needNotice === 'yes') {
					$('.def-notification').hide();
				}
			}
		});
		//Generate backup codes.
		$('.button-wpdef-2fa-backup-codes-generate').on('click', function () {
			var el = $(this);
			$.ajax({
				method: 'POST',
				url: '<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
				data: {},
				dataType: 'JSON',
				success: function (response) {
					if (response.success == true) {
						// Remove old notification.
						if ($('#backup-codes-notification').length > 0) {
							$('#backup-codes-notification').hide();
						}
						// Change the button text.
						el.text(response.data.button_text);
						// Change description.
						$('.backup-codes-provider-text').text(response.data.description)
						var $codesList = $('.wpdef-2fa-backup-codes-unused-codes');
						$('.wpdef-2fa-backup-codes-wrapper').show();
						$codesList.html('');
						// Append the codes.
						for (i = 0; i < response.data.codes.length; i++) {
							$codesList.append('<p>' + response.data.codes[i] + '</p>');
						}
						// Update counter.
						$('.wpdef-2fa-backup-codes-count').html(response.data.count);
						// Build the download link.
						var txt_data = 'data:application/text;charset=utf-8,';
						txt_data += response.data.title + '\n';
						for (i = 0; i < response.data.codes.length; i++) {
							txt_data += response.data.codes[i] + '\n';
						}
						$('#wpdef-2fa-backup-codes-download-link').attr('href', encodeURI(txt_data));
					} else {
						// Error case.
					}
				}
			});
		});
	})(jQuery);
</script>