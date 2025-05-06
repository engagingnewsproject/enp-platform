<?php
/**
 * This template is used to display 2FA TOTP field and reset button.
 *
 * @package WP_Defender
 */

?>
<p class="error"></p>
<div id="defender-totp"></div>
<script type="text/javascript">
	jQuery(function ($) {
		// Toggle.
		$('body').on('click', '#field-totp', function () {
			var el = $('.reset-totp-keys');
			if (this.checked) {
				el.prop('disabled', false);
			} else {
				el.prop('disabled', true);
			}
		});
		// Reset settings.
		$('body').on('click', '.reset-totp-keys', function () {
			var el = $(this);
			$.ajax({
				type: 'POST',
				url: '<?php echo $url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>',
				data: {},
				dataType: 'JSON',
				beforeSend: function () {
					el.attr('disabled', 'disabled');
				},
				success: function (data) {
					if (data.success == true) {
						$('#field-totp').prop('checked', false);
						location.reload();
					} else {
						el.removeAttr('disabled');
						el.closest('.well').find('.error').text(data.data.message).show();
					}
				}
			});
		})
	})
</script>