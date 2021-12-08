<style type="text/css">
	.def-notification {
		background-color: #D1F1EA;
		border-radius: 4px;
		padding: 10px 15px;
		font-style: italic;
		margin-bottom: 15px;
	}
	#defender-security .well .error {
		border-radius: 5px;
		background-color: #F9F9F9;
		padding: 10px 15px;
		margin-bottom: 5px;
		display: none;
		color: #FF6D6D;
		font-size: 12px !important;
		font-weight: 500;
	}
</style>
<h2><?php _e( 'Security', 'wpdef' ); ?></h2>
<table class="form-table" id="defender-security">
	<tbody>
	<tr class="user-sessions-wrap hide-if-no-js">
		<th><?php _e( 'Two Factor Authentication', 'wpdef' ); ?></th>
		<td  class="well" aria-live="assertive">
			<p class="error"></p>
			<div class="def-notification">
				<span class="dashicons dashicons-yes-alt"></span>
				<?php _e( 'Two factor authentication is active.', 'wpdef' ); ?>
			</div>
			<button type="button" class="button" id="disable-2fa">
				<?php _e( 'Disable', 'wpdef' ); ?>
			</button>
		</td>
	</tr>
	<tr class="user-sessions-wrap hide-if-no-js">
		<th><?php _e( 'Fallback email address', 'wpdef' ) ?></th>
		<td aria-live="assertive">
			<input type="text" class="regular-text" name="def_2fa_backup_email" value="<?php echo $backup_email; ?>"/>
			<p class="description">
				<?php
				_e( 'If you ever lose your device, you can send a fallback passcode to this email address.', 'wpdef' )
				?>
			</p>
		</td>
	</tr>
	</tbody>
</table>
<script type="text/javascript">
	jQuery(function ($) {
		$('body').on('click', '#disable-2fa', function () {
			var that = $(this);
			$.ajax({
				type: 'POST',
				url: '<?php echo $url; ?>',
				data: {},
				beforeSend: function () {
					that.attr('disabled', 'disabled');
				},
				success: function (data) {
					if (data.success === true) {
						location.reload();
					} else {
						that.removeAttr('disabled');
						that.closest('.well').find('.error').text(data.data.message).show();
					}
				}
			})
		})
	})
</script>