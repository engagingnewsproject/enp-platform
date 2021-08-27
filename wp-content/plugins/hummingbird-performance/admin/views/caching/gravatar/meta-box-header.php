<?php
/**
 * Gravatar caching header meta box.
 *
 * @package Hummingbird
 *
 * @var string $title  Module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<div class="sui-actions-right">
	<button class="sui-button sui-button-ghost sui-tooltip sui-tooltip-top-right" aria-live="polite" data-tooltip="<?php esc_attr_e( 'Clear all locally cached Gravatars', 'wphb' ); ?>">
		<!-- Default State Content -->
		<span class="sui-button-text-default">
			<span class="sui-icon-update" aria-hidden="true"></span>
			<?php esc_html_e( 'Clear cache', 'wphb' ); ?>
		</span>

		<!-- Loading State Content -->
		<span class="sui-button-text-onload">
			<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
			<?php esc_html_e( 'Clearing cache', 'wphb' ); ?>
		</span>
	</button>
</div>
