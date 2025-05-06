<?php
/**
 * This template is used to display 2FA fallback email field.
 *
 * @package WP_Defender
 */

?>
<p id="wpdef-2fa-email" class="<?php echo esc_attr( $class ); ?>">
	<input type="text" class="regular-text" name="def_2fa_backup_email"
			value="<?php echo esc_attr( $backup_email ); ?>"/>
</p>
<script type="text/javascript">
	jQuery(function ($) {
		$('body').on('click', '#field-fallback-email', function () {
			$('#wpdef-2fa-email').toggle();
		});
	})
</script>