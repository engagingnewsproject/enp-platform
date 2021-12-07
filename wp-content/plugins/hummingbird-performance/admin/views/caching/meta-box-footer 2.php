<?php
/**
 * Page caching common box footer for saving settings.
 *
 * @since 1.8.1
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-actions-right">
	<button type="submit" class="sui-button sui-button-blue" aria-live="polite">
		<!-- Default State Content -->
		<span class="sui-button-text-default"><?php esc_html_e( 'Save Settings', 'wphb' ); ?></span>

		<!-- Loading State Content -->
		<span class="sui-button-text-onload">
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			<?php esc_html_e( 'Saving Settings', 'wphb' ); ?>
		</span>
	</button>
</div>
