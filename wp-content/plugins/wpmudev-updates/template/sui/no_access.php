<?php
/**
 * Restricted page template.
 *
 * @package templates
 */

?>
<div class="sui-notice sui-notice-error" style="margin-bottom: 10px;">
	<div class="sui-notice-content">
		<div class="sui-notice-message">
			<i class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></i>
			<p><?php esc_html_e( 'You do not have the permission to view this page', 'wpmudev' ); ?></p>
		</div>
	</div>
</div>
<?php exit; ?>