<?php
/**
 * Advanced general footer meta box.
 *
 * @package Hummingbird
 * @since 1.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-actions-right">
	<button type="submit" class="sui-button sui-button-blue" aria-live="polite">
		<!-- Default State Content -->
		<span class="sui-button-text-default"><?php esc_html_e( 'Save changes', 'wphb' ); ?></span>

		<!-- Loading State Content -->
		<span class="sui-button-text-onload">
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			<?php esc_html_e( 'Saving changes', 'wphb' ); ?>
		</span>
	</button>
</div>
